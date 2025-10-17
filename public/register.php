<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components/register.css">
</head>
<body>
    <div class="register-container">
    <form action="../app/controllers/StaffController.php" method="POST">
        <h2>Staff Registration</h2>
        <input type="hidden" name="action" value="register">

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error mb-3 full-width">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['message'])): ?>
            <div class="alert alert-success mb-3 full-width">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <!-- ✅ START GRID -->
        <div class="form-grid">

            <div class="register-form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name">
            </div>

            <div class="register-form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username">
            </div>

            <div class="register-form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Create a password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}" title="Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character">
            </div>

            <div class="register-form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
            </div>

            <div class="register-form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required onchange="toggleDepartmentField()">
                    <option value="">Select Role</option>
                    <option value="staff">Staff</option>
                    <option value="receptionist">Receptionist</option>
                </select>
            </div>

            <div class="register-form-group" id="department_field">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id">
                    <option value=""> Select Department </option>
                    <?php
                    require_once '../config/config.php';
                    require_once '../app/models/Department.php';
                    $department_model = new Department($conn);
                    $departments = $department_model->get_all();
                    while ($dept = $departments->fetch_assoc()) {
                        echo '<option value="' . $dept['id'] . '">' . htmlspecialchars($dept['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- ✅ Make button span both columns -->
            <button type="submit" class="register-btn full-width">Create Account</button>

            <p class="login-link full-width">
                Already have an account? <a href="login.php">Sign in</a>
            </p>

        </div>
        <!-- ✅ END GRID -->

    </form>
</div>


    <script>
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const themeToggle = document.querySelector('.theme-toggle-login');
            const currentTheme = document.body.getAttribute('data-theme');
            themeToggle.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
        }

        function toggleDepartmentField() {
            const roleSelect = document.getElementById('role');
            const departmentField = document.getElementById('department_field');
            const departmentSelect = document.getElementById('department_id');

            if (roleSelect.value === 'receptionist') {
                departmentField.style.display = 'none';
                departmentSelect.required = false;
                departmentSelect.value = '';
            } else if (roleSelect.value === 'staff') {
                departmentField.style.display = 'block';
                departmentSelect.required = true;
            }
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        updateThemeIcon();

        // Initialize department field visibility
        document.addEventListener('DOMContentLoaded', function() {
            toggleDepartmentField();
        });
    </script>
</body>
</html>
