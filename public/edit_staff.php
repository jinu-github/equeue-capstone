<?php
session_start();

// Prevent browser from caching or showing this page from history
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in or not admin
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

require_once '../config/config.php';
require_once '../app/models/Staff.php';
require_once '../app/models/Department.php';

$staff_model = new Staff($conn);
$department_model = new Department($conn);

// Get staff ID from URL
$staff_id = $_GET['id'] ?? null;
if (!$staff_id) {
    header("Location: dashboard.php");
    exit();
}

// Get staff data
$staff_data = $staff_model->find_by_id($staff_id);
if (!$staff_data) {
    header("Location: dashboard.php");
    exit();
}

// Get all departments for dropdown
$departments = $department_model->get_all()->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $department_id = ($role === 'receptionist') ? null : $_POST['department_id'];

    // Validate inputs
    if (empty($name) || empty($username) || empty($role)) {
        $error = "All fields are required.";
    } elseif ($role === 'staff' && empty($department_id)) {
        $error = "Department is required for staff members.";
    } else {
        // Check if username exists for another user
        $existing_user = $staff_model->find_by_username($username);
        if ($existing_user && $existing_user['id'] != $staff_id) {
            $error = "Username already exists.";
        } else {
            // Update staff
            if ($staff_model->update_staff($staff_id, $name, $username, $department_id, $role)) {
                $staff_model->log_audit_action($_SESSION['staff_id'], 'staff_updated', "Updated {$role} account: {$username}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
                $success = "Staff account updated successfully.";
                // Refresh staff data
                $staff_data = $staff_model->find_by_id($staff_id);
            } else {
                $error = "Failed to update staff account.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-section {
            max-width: 600px;
            margin: 2rem auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .flex {
            display: flex;
        }
        .justify-between {
            justify-content: space-between;
        }
        .items-center {
            align-items: center;
        }
        .mt-4 {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-users-cog"></i> Edit Staff - eQueue</h1>
        <div class="header-nav">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="../app/controllers/StaffController.php?action=logout" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="form-section">
            <h2>Edit Staff Account</h2>

            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($staff_data['name']); ?>" placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo htmlspecialchars($staff_data['username']); ?>" placeholder="Enter username">
                    </div>

                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="staff" <?php echo $staff_data['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="receptionist" <?php echo $staff_data['role'] === 'receptionist' ? 'selected' : ''; ?>>Receptionist</option>
                        </select>
                    </div>

                    <div class="form-group" id="department-group" style="<?php echo $staff_data['role'] === 'receptionist' ? 'display: none;' : ''; ?>">
                        <label for="department_id">Department *</label>
                        <select id="department_id" name="department_id" <?php echo $staff_data['role'] === 'staff' ? 'required' : ''; ?>>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $staff_data['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?> Department
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-4">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Show/hide department field based on role selection
        $('#role').change(function() {
            var selectedRole = $(this).val();
            var departmentGroup = $('#department-group');
            var departmentSelect = $('#department_id');

            if (selectedRole === 'receptionist') {
                departmentGroup.hide();
                departmentSelect.prop('required', false);
            } else {
                departmentGroup.show();
                departmentSelect.prop('required', true);
            }
        });
    });
    </script>
</body>
</html>
