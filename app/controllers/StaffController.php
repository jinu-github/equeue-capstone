<?php
require_once '../../config/config.php';
require_once '../models/Staff.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'register') {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'] ?? 'staff'; // Default to staff if not provided

        // For receptionists, department_id is not required and can be null
        $department_id = ($role === 'receptionist') ? null : ($_POST['department_id'] ?? null);

        if ($password !== $confirm_password) {
            header("Location: ../../public/register.php?error=Passwords do not match");
            exit();
        }

        // Validate password strength
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

        $staff = new Staff($conn);
        $user = $staff->find_by_username($username);

        if ($user && password_verify($password, $user['password'])) {
            // Auto-set department_id based on role: staff use their assigned department, receptionist use null
            $department_id = ($user['role'] === 'receptionist') ? null : $user['department_id'];

            $_SESSION['staff_id'] = $user['id'];
            $_SESSION['department_id'] = $department_id;
            $_SESSION['role'] = $user['role'];
            header("Location: ../../public/dashboard.php");
        } else {
            header("Location: ../../public/login.php?error=Invalid credentials.");
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_start();
    session_unset();
    session_destroy();
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: ../../public/login.php");
    exit();
}
?>