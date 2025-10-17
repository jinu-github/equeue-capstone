<?php
class Doctor {
    private $conn;
    private $table_name = "doctors";

    public $id;
    public $name;
    public $department_id;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function find_available_by_department($department_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE department_id = ? AND status = 'available' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function get_all_by_department($department_id) {
    $query = "SELECT * FROM " . $this->table_name . " WHERE department_id = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    return $stmt->get_result();
}
}
?>