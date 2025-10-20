<?php
class Staff {
    private $conn;
    private $table_name = "staff";

    public $id;
    public $name;
    public $username;
    public $password;
    public $department_id;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $username, $password, $department_id, $role = 'staff') {
        $query = "INSERT INTO " . $this->table_name . " (name, username, password, department_id, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Use 'i' for department_id since it can be null
        $stmt->bind_param("sssis", $name, $username, $hashed_password, $department_id, $role);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function find_by_username($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function increment_failed_attempts($staff_id) {
        $query = "UPDATE " . $this->table_name . " SET failed_attempts = failed_attempts + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $staff_id);
        return $stmt->execute();
    }

    public function reset_failed_attempts($staff_id) {
        $query = "UPDATE " . $this->table_name . " SET failed_attempts = 0, lockout_until = NULL WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $staff_id);
        return $stmt->execute();
    }

    public function lock_account($staff_id, $minutes) {
        $lockout_time = date('Y-m-d H:i:s', time() + ($minutes * 60));
        $query = "UPDATE " . $this->table_name . " SET lockout_until = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $lockout_time, $staff_id);
        return $stmt->execute();
    }

    public function update_last_login($staff_id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $staff_id);
        return $stmt->execute();
    }

    public function log_audit_action($staff_id, $action, $details = null, $ip_address = null, $user_agent = null) {
        $query = "INSERT INTO admin_audit_log (staff_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issss", $staff_id, $action, $details, $ip_address, $user_agent);
        return $stmt->execute();
    }

    public function update_twofa_secret($staff_id, $secret) {
        $query = "UPDATE " . $this->table_name . " SET twofa_secret = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $secret, $staff_id);
        return $stmt->execute();
    }

    public function update_ip_whitelist($staff_id, $ip_list) {
        $json_ips = json_encode($ip_list);
        $query = "UPDATE " . $this->table_name . " SET ip_whitelist = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $json_ips, $staff_id);
        return $stmt->execute();
    }

    // Admin methods for user management
    public function get_all_staff() {
        $query = "SELECT s.*, d.name as department_name FROM " . $this->table_name . " s
                  LEFT JOIN departments d ON s.department_id = d.id
                  WHERE s.role IN ('staff', 'receptionist')
                  ORDER BY s.name ASC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function update_staff($staff_id, $name, $username, $department_id, $role) {
        $query = "UPDATE " . $this->table_name . " SET name = ?, username = ?, department_id = ?, role = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssisi", $name, $username, $department_id, $role, $staff_id);
        return $stmt->execute();
    }

    public function delete_staff($staff_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND role IN ('staff', 'receptionist')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function create_staff($name, $username, $password, $department_id, $role) {
        // Only allow creating staff or receptionist accounts
        if (!in_array($role, ['staff', 'receptionist'])) {
            return false;
        }
        return $this->create($name, $username, $password, $department_id, $role);
    }

    public function find_by_id($staff_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>
