<?php
class Report {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function get_total_served($period = 'day') {
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "AND DATE(created_at) = CURDATE()";
        }
        $query = "SELECT COUNT(*) as total FROM patients WHERE status = 'done' " . $date_condition;
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    public function get_average_waiting_time($department_id = null) {
        $dept_condition = $department_id ? "AND p.department_id = $department_id" : '';
        $query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, p.created_at, qh.created_at)) as avg_wait
                  FROM patients p
                  JOIN queue_history qh ON p.id = qh.patient_id AND qh.action = 'completed'
                  WHERE p.status = 'done' $dept_condition";
        $result = $this->conn->query($query);
        $avg = $result->fetch_assoc()['avg_wait'];
        return $avg ? round($avg, 2) : 0;
    }

    public function get_no_shows($period = 'day') {
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "AND DATE(created_at) = CURDATE()";
        }
        $query = "SELECT COUNT(*) as total FROM patients WHERE status = 'waiting' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > 2 " . $date_condition;
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    public function get_cancelled_count($period = 'day') {
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "AND DATE(created_at) = CURDATE()";
        }
        $query = "SELECT COUNT(*) as total FROM patients WHERE status = 'cancelled' " . $date_condition;
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    public function get_patients_per_department($period = 'day') {
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "AND DATE(p.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND DATE(p.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "AND DATE(p.created_at) = CURDATE()";
        }
        $query = "SELECT d.name as department, COUNT(p.id) as count
                  FROM patients p
                  JOIN departments d ON p.department_id = d.id
                  WHERE p.status = 'done' $date_condition
                  GROUP BY d.id, d.name";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_daily_summary($date = null) {
        $date_condition = $date ? "AND DATE(qh.created_at) = '$date'" : "AND DATE(qh.created_at) = CURDATE()";
        $query = "SELECT d.name as department, COUNT(*) as total_activities
                  FROM queue_history qh
                  JOIN departments d ON qh.department_id = d.id
                  WHERE 1 $date_condition
                  GROUP BY d.id, d.name
                  ORDER BY total_activities DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_patients_for_period($period = 'day', $department_id = null) {
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "AND DATE(p.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND DATE(p.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "AND DATE(p.created_at) = CURDATE()";
        }
        $dept_condition = $department_id ? "AND p.department_id = $department_id" : '';
        $query = "SELECT p.*, d.name as department_name, doc.name as doctor_name
                  FROM patients p
                  LEFT JOIN departments d ON p.department_id = d.id
                  LEFT JOIN doctors doc ON p.doctor_id = doc.id
                  WHERE 1 $date_condition $dept_condition
                  ORDER BY p.created_at DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_patient_status_distribution($period = 'day', $department_id = null) {
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "AND DATE(created_at) = CURDATE()";
        }
        $dept_condition = $department_id ? "AND department_id = $department_id" : '';
        $query = "SELECT status, COUNT(*) as count
                  FROM patients
                  WHERE 1 $date_condition $dept_condition
                  GROUP BY status
                  ORDER BY count DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_daily_patient_trends($period = 'week', $department_id = null) {
        $date_condition = '';
        $group_by = '';
        switch ($period) {
            case 'month':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                $group_by = "DATE(created_at)";
                break;
            default: // week
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                $group_by = "DATE(created_at)";
        }
        $dept_condition = $department_id ? "AND department_id = $department_id" : '';
        $query = "SELECT $group_by as date, COUNT(*) as count
                  FROM patients
                  WHERE 1 $date_condition $dept_condition
                  GROUP BY $group_by
                  ORDER BY $group_by";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_hourly_patient_distribution($period = 'day', $department_id = null) {
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "AND DATE(created_at) = CURDATE()";
        }
        $dept_condition = $department_id ? "AND department_id = $department_id" : '';
        $query = "SELECT HOUR(created_at) as hour, COUNT(*) as count
                  FROM patients
                  WHERE 1 $date_condition $dept_condition
                  GROUP BY HOUR(created_at)
                  ORDER BY hour";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>