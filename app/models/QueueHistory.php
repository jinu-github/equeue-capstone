<?php
class QueueHistory {
    private $conn;
    private $table_name = "queue_history";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function log_activity($patient_id, $action, $old_status, $new_status, $department_id, $doctor_id = null, $staff_id = null) {
        // If department_id is null, get it from the patient record
        if ($department_id === null) {
            $patient_query = "SELECT department_id FROM patients WHERE id = ?";
            $patient_stmt = $this->conn->prepare($patient_query);
            $patient_stmt->bind_param("i", $patient_id);
            $patient_stmt->execute();
            $patient_result = $patient_stmt->get_result();
            $patient = $patient_result->fetch_assoc();
            $department_id = $patient['department_id'] ?? null;
        }

        $query = "INSERT INTO " . $this->table_name . " (patient_id, action, old_status, new_status, department_id, doctor_id, staff_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isssiii", $patient_id, $action, $old_status, $new_status, $department_id, $doctor_id, $staff_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function get_all_activities() {
        $query = "SELECT qh.*, CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.middle_name, ''), ' ', COALESCE(p.last_name, '')) as patient_name, p.check_in_time as patient_check_in_time, d.name as department_name, doc.name as doctor_name, s.name as staff_name
                  FROM " . $this->table_name . " qh
                  LEFT JOIN patients p ON qh.patient_id = p.id
                  LEFT JOIN departments d ON qh.department_id = d.id
                  LEFT JOIN doctors doc ON qh.doctor_id = doc.id
                  LEFT JOIN staff s ON qh.staff_id = s.id
                  ORDER BY qh.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function get_activities_by_department($department_id) {
        $query = "SELECT qh.*, CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.middle_name, ''), ' ', COALESCE(p.last_name, '')) as patient_name, p.check_in_time as patient_check_in_time, d.name as department_name, doc.name as doctor_name, s.name as staff_name
                  FROM " . $this->table_name . " qh
                  LEFT JOIN patients p ON qh.patient_id = p.id
                  LEFT JOIN departments d ON qh.department_id = d.id
                  LEFT JOIN doctors doc ON qh.doctor_id = doc.id
                  LEFT JOIN staff s ON qh.staff_id = s.id
                  WHERE qh.department_id = ?
                  ORDER BY qh.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function delete_by_id($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function clear_all() {
        $query = "DELETE FROM " . $this->table_name;
        return $this->conn->query($query);
    }

    public function get_grouped_activities($department_id = null) {
        $where_clause = "";
        $params = [];
        $types = "";

        if ($department_id) {
            $where_clause = "WHERE qh.department_id = ?";
            $params[] = $department_id;
            $types = "i";
        }

        $query = "SELECT
                    p.id as patient_id,
                    CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.middle_name, ''), ' ', COALESCE(p.last_name, '')) as patient_name,
                    p.check_in_time as patient_check_in_time,
                    d.name as department_name,
                    doc.name as doctor_name,
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'id', qh.id,
                            'action', qh.action,
                            'old_status', qh.old_status,
                            'new_status', qh.new_status,
                            'created_at', qh.created_at,
                            'department_name', d2.name,
                            'doctor_name', doc2.name
                        )
                        ORDER BY qh.created_at DESC
                        SEPARATOR '|||'
                    ) as actions_json
                  FROM patients p
                  LEFT JOIN " . $this->table_name . " qh ON p.id = qh.patient_id
                  LEFT JOIN departments d ON p.department_id = d.id
                  LEFT JOIN doctors doc ON p.doctor_id = doc.id
                  LEFT JOIN departments d2 ON qh.department_id = d2.id
                  LEFT JOIN doctors doc2 ON qh.doctor_id = doc2.id
                  $where_clause
                  GROUP BY p.id, p.first_name, p.middle_name, p.last_name, p.check_in_time, d.name, doc.name
                  HAVING COUNT(qh.id) > 0
                  ORDER BY p.check_in_time DESC";

        $stmt = $this->conn->prepare($query);
        if ($department_id) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $grouped_data = [];
        while ($row = $result->fetch_assoc()) {
            $actions = [];
            if ($row['actions_json']) {
                $action_strings = explode('|||', $row['actions_json']);
                foreach ($action_strings as $action_str) {
                    $actions[] = json_decode($action_str, true);
                }
            }
            $row['actions'] = $actions;
            unset($row['actions_json']);
            $grouped_data[] = $row;
        }

        return $grouped_data;
    }
}
?>
