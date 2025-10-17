<?php
class Department {
    private $conn;
    private $table_name = "departments";

    public $id;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function get_all() {
        $query = "SELECT * FROM " . $this->table_name;
        $result = $this->conn->query($query);
        return $result;
    }

    public function get_by_id($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>