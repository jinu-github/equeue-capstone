<?php
class Patient {
    private $conn;
    private $table_name = "patients";

    public $id;
    public $age;
    public $contact_number;
    public $queue_number;
    public $department_id;
    public $status;
    public $created_at;
    public $check_in_time;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($first_name, $middle_name, $last_name, $birthdate, $contact_number, $reason_for_visit, $parent_guardian, $queue_number, $department_id, $doctor_id, $address = null, $gender = null, $civil_status = null, $registration_datetime = null, $bp = null, $temp = null, $cr_pr = null, $rr = null, $wt = null, $o2sat = null) {
        $age = $this->calculateAge($birthdate);
        $query = "INSERT INTO " . $this->table_name . " (first_name, middle_name, last_name, birthdate, age, contact_number, reason_for_visit, parent_guardian, queue_number, department_id, doctor_id, address, gender, civil_status, registration_datetime, bp, temp, cr_pr, rr, wt, o2sat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssisssiiissssssssss", $first_name, $middle_name, $last_name, $birthdate, $age, $contact_number, $reason_for_visit, $parent_guardian, $queue_number, $department_id, $doctor_id, $address, $gender, $civil_status, $registration_datetime, $bp, $temp, $cr_pr, $rr, $wt, $o2sat);


        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function get_next_queue_number($department_id) {
        $query = "SELECT MAX(queue_number) as max_queue FROM " . $this->table_name . " WHERE department_id = ? AND status NOT IN ('done', 'cancelled')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['max_queue'] ? $result['max_queue'] + 1 : 1;
    }

    public function get_all_by_department($department_id) {
    $query = "SELECT p.*, d.name as doctor_name
              FROM " . $this->table_name . " p
              LEFT JOIN doctors d ON p.doctor_id = d.id
              WHERE p.department_id = ? AND p.status NOT IN ('done', 'cancelled')
              ORDER BY p.queue_number ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    return $stmt->get_result();
}

    public function get_all_by_department_with_names($department_id) {
    $query = "SELECT p.id, p.first_name, p.middle_name, p.last_name, p.age, p.contact_number, p.queue_number, p.department_id, p.doctor_id, p.status, p.created_at, p.check_in_time, p.birthdate, p.reason_for_visit, p.parent_guardian, d.name as doctor_name
              FROM " . $this->table_name . " p
              LEFT JOIN doctors d ON p.doctor_id = d.id
              WHERE p.department_id = ? AND p.status NOT IN ('done', 'cancelled')
              ORDER BY p.queue_number ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    return $stmt->get_result();
}

    public function get_all() {
    $query = "SELECT p.*, d.name as doctor_name, dep.name as department_name
              FROM " . $this->table_name . " p
              LEFT JOIN doctors d ON p.doctor_id = d.id
              LEFT JOIN departments dep ON p.department_id = dep.id
              ORDER BY p.created_at DESC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->get_result();
}

    public function update_status($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            $error = "Failed to prepare delete statement: " . $this->conn->error;
            error_log($error);
            return ['success' => false, 'error' => $error];
        }
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        if (!$result) {
            $error = "Failed to execute delete statement: " . $stmt->error;
            error_log($error);
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
        $stmt->close();
        return ['success' => true, 'error' => ''];
    }
    public function get_by_id($id) {
    $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

    public function update($id, $first_name, $middle_name, $last_name, $birthdate, $contact_number, $reason_for_visit, $parent_guardian, $department_id, $doctor_id, $address = null, $gender = null, $civil_status = null, $registration_datetime = null, $bp = null, $temp = null, $cr_pr = null, $rr = null, $wt = null, $o2sat = null) {
        $age = $this->calculateAge($birthdate);
        $query = "UPDATE " . $this->table_name . " SET first_name = ?, middle_name = ?, last_name = ?, birthdate = ?, age = ?, contact_number = ?, reason_for_visit = ?, parent_guardian = ?, department_id = ?, doctor_id = ?, address = ?, gender = ?, civil_status = ?, registration_datetime = ?, bp = ?, temp = ?, cr_pr = ?, rr = ?, wt = ?, o2sat = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssisssiisssssssssss", $first_name, $middle_name, $last_name, $birthdate, $age, $contact_number, $reason_for_visit, $parent_guardian, $department_id, $doctor_id, $address, $gender, $civil_status, $registration_datetime, $bp, $temp, $cr_pr, $rr, $wt, $o2sat, $id);
        return $stmt->execute();
    }

    private function calculateAge($birthdate) {
        if (!$birthdate) return 0;
        $birth = new DateTime($birthdate);
        $today = new DateTime();
        $age = $today->diff($birth)->y;
        return $age;
    }

    // Get number of patients ahead in queue for a specific patient
    public function get_patients_ahead_count($patient_id, $department_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                  WHERE department_id = ? AND queue_number < (
                      SELECT queue_number FROM " . $this->table_name . " WHERE id = ?
                  ) AND status IN ('waiting', 'in consultation')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $department_id, $patient_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }

    // Get patients waiting in a department
    public function get_waiting_patients($department_id) {
        $query = "SELECT p.*, d.name as doctor_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN doctors d ON p.doctor_id = d.id
                  WHERE p.department_id = ? AND p.status = 'waiting'
                  ORDER BY p.queue_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get next patient in queue
    public function get_next_patient($department_id) {
        $query = "SELECT p.*, d.name as doctor_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN doctors d ON p.doctor_id = d.id
                  WHERE p.department_id = ? AND p.status = 'waiting'
                  ORDER BY p.queue_number ASC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Get patients who are almost next (within 3 positions)
    public function get_almost_next_patients($department_id, $limit = 3) {
        $query = "SELECT p.*, d.name as doctor_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN doctors d ON p.doctor_id = d.id
                  WHERE p.department_id = ? AND p.status = 'waiting'
                  ORDER BY p.queue_number ASC
                  LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $department_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Reset queue numbers for a department starting from 1
    public function reset_queue_numbers($department_id) {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE department_id = ? AND status NOT IN ('done', 'cancelled')
                  ORDER BY queue_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row['id'];
        }

        // Reset queue numbers starting from 1
        $success = true;
        $queue_number = 1;
        foreach ($patients as $patient_id) {
            $update_query = "UPDATE " . $this->table_name . " SET queue_number = ? WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bind_param("ii", $queue_number, $patient_id);
            if (!$update_stmt->execute()) {
                $success = false;
                break;
            }
            $queue_number++;
        }

        return $success;
    }

    // Helper method to combine separate name fields into full name
    public function combineNames($first_name, $middle_name, $last_name) {
        $name_parts = array_filter([$first_name, $middle_name, $last_name]);
        return implode(' ', $name_parts);
    }

    // Helper method to split full name into separate components
    public function splitName($full_name) {
        $parts = explode(' ', trim($full_name));
        $first_name = $parts[0] ?? '';
        $last_name = $parts[count($parts) - 1] ?? '';
        $middle_name = '';

        if (count($parts) > 2) {
            $middle_parts = array_slice($parts, 1, -1);
            $middle_name = implode(' ', $middle_parts);
        }

        return [
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name
        ];
    }

    // Get the latest patient added to a department
    public function get_latest_by_department($department_id) {
        $query = "SELECT p.*, d.name as doctor_name, dep.name as department_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN doctors d ON p.doctor_id = d.id
                  LEFT JOIN departments dep ON p.department_id = dep.id
                  WHERE p.department_id = ?
                  ORDER BY p.created_at DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Check and update patients who have been inactive for more than 30 minutes
    public function check_and_update_no_show_patients($queue_history_model) {
        // Get patients who are waiting or in consultation and haven't had activity in the last 30 minutes
        $query = "SELECT p.id, p.status, p.check_in_time, p.created_at,
                         MAX(qh.created_at) as last_activity
                  FROM " . $this->table_name . " p
                  LEFT JOIN queue_history qh ON p.id = qh.patient_id
                  WHERE p.status IN ('waiting', 'in consultation')
                  GROUP BY p.id
                  HAVING last_activity IS NULL OR TIMESTAMPDIFF(MINUTE, last_activity, NOW()) > 30";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $updated_count = 0;
        while ($patient = $result->fetch_assoc()) {
            // Update patient status to 'no show'
            $update_query = "UPDATE " . $this->table_name . " SET status = 'no show' WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bind_param("i", $patient['id']);
            $update_stmt->execute();

            // Log the status change in queue history
            $queue_history_model->log_activity(
                $patient['id'],
                'status_changed',
                $patient['status'],
                'no show',
                null, // department_id will be determined from patient
                null, // doctor_id
                null  // staff_id
            );

            $updated_count++;
        }

        return $updated_count;
    }
}
?>
