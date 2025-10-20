<?php
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USERNAME;
        $this->mailer->Password = SMTP_PASSWORD;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = SMTP_PORT;

        // Default sender
        $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    }

    public function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
        try {
            // Recipients
            $this->mailer->addAddress($toEmail, $toName);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset - eQueue System';

            $htmlContent = $this->getPasswordResetEmailTemplate($toName, $resetLink);
            $this->mailer->Body = $htmlContent;

            $textContent = $this->getPasswordResetEmailText($toName, $resetLink);
            $this->mailer->AltBody = $textContent;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    private function getPasswordResetEmailTemplate($name, $resetLink) {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>eQueue System - Password Reset</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$name},</h2>
                    <p>You have requested to reset your password for the eQueue system.</p>
                    <p>Please click the button below to reset your password:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' class='button'>Reset Password</a>
                    </p>
                    <p><strong>Important:</strong> This link will expire in 24 hours for security reasons.</p>
                    <p>If you did not request this password reset, please ignore this email. Your password will remain unchanged.</p>
                    <p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
                    <p><a href='{$resetLink}'>{$resetLink}</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the eQueue System. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " eQueue System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getPasswordResetEmailText($name, $resetLink) {
        return "Hello {$name},

You have requested to reset your password for the eQueue system.

Please click the following link to reset your password:
{$resetLink}

Important: This link will expire in 24 hours for security reasons.

If you did not request this password reset, please ignore this email. Your password will remain unchanged.

This is an automated message from the eQueue System. Please do not reply to this email.

© " . date('Y') . " eQueue System. All rights reserved.";
    }
}
?>
