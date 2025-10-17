<?php
require_once '../config/config.php';
require_once '../app/models/Doctor.php';

if (isset($_GET['department_id'])) {
    $doctor_model = new Doctor($conn);
    $doctors = $doctor_model->get_all_by_department($_GET['department_id']);
    
    $doctor_list = [];
    while ($row = $doctors->fetch_assoc()) {
        $doctor_list[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($doctor_list);
}
?>