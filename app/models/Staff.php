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
}
?>