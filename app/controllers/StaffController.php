<?php
require_once '../../config/config.php';
require_once '../models/Staff.php';
require_once '../services/AdminSecurityService.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'register') {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'] ?? 'staff'; // Default to staff if not provided

        // Prevent public admin registration
        if ($role === 'admin') {
            header("Location: ../../public/register.php?error=Admin accounts cannot be created through public registration");
            exit();
        }

        // For receptionists, department_id is not required and can be null
        $department_id = ($role === 'receptionist') ? null : ($_POST['department_id'] ?? null);

        if ($password !== $confirm_password) {
            header("Location: ../../public/register.php?error=Passwords do not match");
            exit();
        }

        // Validate password strength - stricter for admin (though not applicable here)
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            header("Location: ../../public/register.php?error=Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character");
            exit();
        }

        // Validate department selection for staff
        if ($role === 'staff' && empty($department_id)) {
            header("Location: ../../public/register.php?error=Department is required for staff members");
            exit();
        }

        $staff = new Staff($conn);
        if ($staff->find_by_username($username)) {
            echo "Username already exists.";
        } else {
            if ($staff->create($name, $username, $password, $department_id, $role)) {
                header("Location: ../../public/login.php");
            } else {
                echo "Error: Could not register.";
            }
        }
    } else if ($action == 'login') {
        session_start();
        $username = $_POST['username'];
        $password = $_POST['password'];
        $twofa_code = $_POST['twofa_code'] ?? null;

        $staff = new Staff($conn);
        $user = $staff->find_by_username($username);

        // Check if account is locked out
        if ($user && $user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            $remaining_time = ceil((strtotime($user['lockout_until']) - time()) / 60);
            header("Location: ../../public/login.php?error=Account locked. Try again in {$remaining_time} minutes.");
            exit();
        }

        if ($user && password_verify($password, $user['password'])) {
            // Check IP whitelist for admin
            if ($user['role'] === 'admin' && !empty($user['ip_whitelist'])) {
                $client_ip = $_SERVER['REMOTE_ADDR'];
                $allowed_ips = json_decode($user['ip_whitelist'], true) ?? [];
                if (!in_array($client_ip, $allowed_ips)) {
                    $staff->log_audit_action($user['id'], 'login_denied_ip', "IP {$client_ip} not in whitelist", $client_ip, $_SERVER['HTTP_USER_AGENT'] ?? '');
                    header("Location: ../../public/login.php?error=Access denied from this IP address.");
                    exit();
                }
            }

            // Handle 2FA for admin
            if ($user['role'] === 'admin' && !empty($user['twofa_secret'])) {
                if (empty($twofa_code)) {
                    // Show 2FA form
                    $_SESSION['pending_admin_login'] = $user['id'];
                    header("Location: ../../public/login.php?require_2fa=1&username=" . urlencode($username));
                    exit();
                } else {
                    // Verify 2FA code
                    $security_service = new AdminSecurityService();
                    if (!$security_service->verify_totp($user['twofa_secret'], $twofa_code)) {
                        $staff->increment_failed_attempts($user['id']);
                        $staff->log_audit_action($user['id'], 'login_failed_2fa', 'Invalid 2FA code', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
                        header("Location: ../../public/login.php?error=Invalid 2FA code.&require_2fa=1&username=" . urlencode($username));
                        exit();
                    }
                }
            }

            // Reset failed attempts on successful login
            $staff->reset_failed_attempts($user['id']);
            $staff->update_last_login($user['id']);

            // Auto-set department_id based on role: staff use their assigned department, receptionist use null, admin use null
            $department_id = ($user['role'] === 'receptionist' || $user['role'] === 'admin') ? null : $user['department_id'];

            $_SESSION['staff_id'] = $user['id'];
            $_SESSION['department_id'] = $department_id;
            $_SESSION['role'] = $user['role'];

            // Set session timeout for admin (30 minutes)
            if ($user['role'] === 'admin') {
                $_SESSION['admin_timeout'] = time() + (30 * 60);
            }

            $staff->log_audit_action($user['id'], 'login_success', 'Successful login', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
            header("Location: ../../public/dashboard.php");
        } else {
            // Handle failed login
            if ($user) {
                $staff->increment_failed_attempts($user['id']);
                // Lock account after 5 failed attempts for 15 minutes
                if ($user['failed_attempts'] >= 4) {
                    $staff->lock_account($user['id'], 15);
                    $staff->log_audit_action($user['id'], 'account_locked', 'Account locked due to failed attempts', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
                    header("Location: ../../public/login.php?error=Account locked due to multiple failed attempts. Try again in 15 minutes.");
                } else {
                    $staff->log_audit_action($user['id'], 'login_failed', 'Invalid password', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
                    header("Location: ../../public/login.php?error=Invalid credentials.");
                }
            } else {
                header("Location: ../../public/login.php?error=Invalid credentials.");
            }
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_start();
    if (isset($_SESSION['staff_id'])) {
        $staff = new Staff($conn);
        $staff->log_audit_action($_SESSION['staff_id'], 'logout', 'User logged out', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
    }
    session_unset();
    session_destroy();
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: ../../public/login.php");
    exit();
}

// Admin-only endpoints for user management
if ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['admin_action']) || isset($_GET['action']))) {
    session_start();

    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
        exit();
    }

    $staff = new Staff($conn);
    $action = $_GET['admin_action'] ?? $_GET['action'];

    if ($action == 'list_staff' || $action == 'get_all_staff') {
        $staff_list = $staff->get_all_staff();
        echo json_encode($staff_list);
        exit();
    }
}

// Admin-only POST endpoints
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_action'])) {
    session_start();

    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
        exit();
    }

    $staff = new Staff($conn);
    $action = $_POST['admin_action'];

    if ($action == 'create_staff') {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $department_id = ($role === 'receptionist') ? null : $_POST['department_id'];

        // Validate inputs
        if (empty($name) || empty($username) || empty($password) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        if ($role === 'staff' && empty($department_id)) {
            echo json_encode(['success' => false, 'message' => 'Department is required for staff members.']);
            exit();
        }

        // Check if username exists
        if ($staff->find_by_username($username)) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit();
        }

        // Validate password strength
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.']);
            exit();
        }

        if ($staff->create_staff($name, $username, $password, $department_id, $role)) {
            $staff->log_audit_action($_SESSION['staff_id'], 'staff_created', "Created new {$role} account: {$username}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
            echo json_encode(['success' => true, 'message' => 'Staff account created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create staff account.']);
        }
        exit();
    }

    if ($action == 'update_staff') {
        $staff_id = $_POST['staff_id'];
        $name = $_POST['name'];
        $username = $_POST['username'];
        $role = $_POST['role'];
        $department_id = ($role === 'receptionist') ? null : $_POST['department_id'];

        // Validate inputs
        if (empty($name) || empty($username) || empty($role) || empty($staff_id)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        if ($role === 'staff' && empty($department_id)) {
            echo json_encode(['success' => false, 'message' => 'Department is required for staff members.']);
            exit();
        }

        // Check if username exists for another user
        $existing_user = $staff->find_by_username($username);
        if ($existing_user && $existing_user['id'] != $staff_id) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit();
        }

        if ($staff->update_staff($staff_id, $name, $username, $department_id, $role)) {
            $staff->log_audit_action($_SESSION['staff_id'], 'staff_updated', "Updated {$role} account: {$username}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
            echo json_encode(['success' => true, 'message' => 'Staff account updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update staff account.']);
        }
        exit();
    }

    if ($action == 'delete_staff') {
        $staff_id = $_POST['staff_id'];

        if (empty($staff_id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Staff ID is required.']);
            exit();
        }

        if ($staff->delete_staff($staff_id)) {
            $staff->log_audit_action($_SESSION['staff_id'], 'staff_deleted', "Deleted staff account ID: {$staff_id}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Staff account deleted successfully.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to delete staff account.']);
        }
        exit();
    }
}

// Handle POST delete action for AJAX calls from dashboard
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    session_start();

    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
        exit();
    }

    $staff = new Staff($conn);
    $staff_id = intval($_POST['id']);

    if (empty($staff_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Staff ID is required.']);
        exit();
    }

    if ($staff->delete_staff($staff_id)) {
        $staff->log_audit_action($_SESSION['staff_id'], 'staff_deleted', "Deleted staff account ID: {$staff_id}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Staff account deleted successfully.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete staff account.']);
    }
    exit();
}
?>
