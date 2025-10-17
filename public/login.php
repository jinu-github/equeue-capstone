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
            <h2>Staff Login</h2>
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

            <!-- START GRID -->
            <div class="form-grid">

                <div class="login-form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                </div>

                <div class="login-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

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

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        updateThemeIcon();
    </script>
</body>
</html>
