<?php
class AdminSecurityService {
    private function base32_decode($data) {
        $data = strtoupper($data);
        $data = str_replace('=', '', $data);
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';

        $bits = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $val = strpos($chars, $data[$i]);
            if ($val === false) {
                return false;
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        for ($i = 0; $i < strlen($bits); $i += 8) {
            $byte = substr($bits, $i, 8);
            if (strlen($byte) < 8) {
                break;
            }
            $output .= chr(bindec($byte));
        }

        return $output;
    }

    public function generate_totp_secret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    public function verify_totp($secret, $code) {
        $secret = $this->base32_decode($secret);
        if ($secret === false) {
            return false;
        }

        $time = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            $time_window = $time + $i;
            $time_bytes = pack('N*', 0) . pack('N*', $time_window);
            $hash = hash_hmac('sha1', $time_bytes, $secret, true);
            $offset = ord($hash[19]) & 0xf;
            $code_generated = (
                ((ord($hash[$offset]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % 1000000;
            $code_generated = str_pad($code_generated, 6, '0', STR_PAD_LEFT);

            if ($code_generated === $code) {
                return true;
            }
        }
        return false;
    }

    public function get_totp_uri($secret, $username, $issuer = 'eQueue System') {
        return 'otpauth://totp/' . urlencode($issuer) . ':' . urlencode($username) . '?secret=' . $secret . '&issuer=' . urlencode($issuer);
    }

    public function check_rate_limit($ip, $max_attempts = 5, $time_window = 900) { // 15 minutes
        // This would require a rate limiting table in the database
        // For now, we'll use a simple session-based approach
        $key = 'rate_limit_' . md5($ip);
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $time_window];
        }

        if (time() > $_SESSION[$key]['reset_time']) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $time_window];
        }

        if ($_SESSION[$key]['count'] >= $max_attempts) {
            return false;
        }

        $_SESSION[$key]['count']++;
        return true;
    }

    public function validate_session_timeout() {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            if (isset($_SESSION['admin_timeout']) && time() > $_SESSION['admin_timeout']) {
                // Session expired, force logout
                session_unset();
                session_destroy();
                header("Location: ../../public/login.php?error=Session expired. Please login again.");
                exit();
            }
            // Extend session on activity
            $_SESSION['admin_timeout'] = time() + (30 * 60);
        }
    }
}
?>
