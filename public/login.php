<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Staff Login - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components/login.css">
</head>
<body>
    <div class="login-container">
        <form action="../app/controllers/StaffController.php" method="POST">
            <h2>Login</h2>
            <input type="hidden" name="action" value="login">

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

            <?php if(isset($_GET['require_2fa'])): ?>
                <div class="alert alert-info mb-3 full-width">
                    Two-factor authentication required. Enter the 6-digit code from your authenticator app.
                </div>
            <?php endif; ?>

            <!-- START GRID -->
            <div class="form-grid">

                <div class="login-form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username" value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>">
                </div>

                <div class="login-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <span class="toggle-password" onclick="togglePassword('password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>

                <?php if(isset($_GET['require_2fa'])): ?>
                <div class="login-form-group">
                    <label for="twofa_code">2FA Code</label>
                    <input type="text" id="twofa_code" name="twofa_code" required placeholder="Enter 6-digit code" pattern="[0-9]{6}" maxlength="6">
                </div>
                <?php endif; ?>

                <!-- Make button span both columns -->
                <button type="submit" class="login-btn full-width">Login</button>

                <p class="register-link full-width">
                    Don't have an account? <a href="register.php">Create one</a>
                </p>

            </div>
            <!-- END GRID -->
        </form>
    </div>

    <script>
        // Prevent browser back navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };

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

        function togglePassword(fieldId, element) {
            const input = document.getElementById(fieldId);
            if (input.type === 'password') {
                input.type = 'text';
                element.innerHTML = '<svg viewBox="0 0 24 24"><path d="M2.999 3l18 18-1.5 1.5L1.5 1.5 2.999 3zM12 4.5c-4.14 0-7.5 3.36-7.5 7.5 0 1.83.66 3.5 1.74 4.78L3.5 16.5C2.05 14.83 1.5 12.78 1.5 10.5 1.5 5.81 5.31 2 10 2c2.28 0 4.33.55 6 1.5l-1.74 1.74C13.5 5.16 12.83 4.5 12 4.5zM12 7c-.83 0-1.5.67-1.5 1.5v1.17l1.5 1.5V8.5c0-.28.22-.5.5-.5s.5.22.5.5v.67l1.5 1.5V8.5c0-.83-.67-1.5-1.5-1.5zM12 15.5c.83 0 1.5-.67 1.5-1.5v-1.17l-1.5-1.5V14c0 .28-.22.5-.5.5s-.5-.22-.5-.5v-.67l-1.5-1.5V14c0 .83.67 1.5 1.5 1.5zM12 19.5c4.14 0 7.5-3.36 7.5-7.5 0-1.83-.66-3.5-1.74-4.78l1.74-1.74c1.45 1.67 2 3.72 2 5.52 0 4.69-3.81 8.5-8.5 8.5-2.28 0-4.33-.55-6-1.5l1.74-1.74c1.34.84 2.83 1.24 4.26 1.24z"></path></svg>';
            } else {
                input.type = 'password';
                element.innerHTML = '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            }
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        updateThemeIcon();
    </script>
</body>
</html>
