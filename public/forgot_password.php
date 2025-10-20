<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Forgot Password - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components/login.css">
</head>
<body>
    <div class="login-container">
        <form action="../app/controllers/StaffController.php" method="POST">
            <h2>Forgot Password</h2>
            <input type="hidden" name="action" value="forgot_password">

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

            <div class="form-grid">
                <div class="login-form-group">
                    <label for="username_or_email">Username or Email</label>
                    <input type="text" id="username_or_email" name="username_or_email" required placeholder="Enter your username or email address" value="<?php echo htmlspecialchars($_GET['username_or_email'] ?? ''); ?>">
                </div>

                <button type="submit" class="login-btn full-width">Send Reset Link</button>

                <p class="register-link full-width">
                    <a href="login.php">Back to Login</a>
                </p>
            </div>
        </form>
    </div>

    <script>
        // Prevent browser back navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>
</html>
