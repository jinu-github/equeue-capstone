<?php
require_once '../config/config.php';
require_once '../app/models/Department.php';
require_once '../app/models/Patient.php';

$department_model = new Department($conn);
$departments = $department_model->get_all();

$patient_model = new Patient($conn);

while ($dept = $departments->fetch_assoc()) {
    echo "<div class='department-queue'>";
    echo "<h3>" . $dept['name'] . " Department</h3>";
    $patients = $patient_model->get_all_by_department($dept['id']);
    $all_patients = [];

    while ($patient = $patients->fetch_assoc()) {
        $all_patients[] = $patient;
    }

    // Sort patients by queue number
    usort($all_patients, function($a, $b) {
        return $a['queue_number'] <=> $b['queue_number'];
    });

    if (empty($all_patients)) {
        echo "<p>No patients in queue.</p>";
    } else {
        echo "<ul>";
        foreach ($all_patients as $patient) {
            $status_class = 'status-' . strtolower(str_replace(' ', '-', $patient['status']));
            $patient_text = '<span class="queue-number">Queue #' . $patient['queue_number'] . '</span>';
            echo "<li class='" . $status_class . "'>" . $patient_text . " <span class='status'>[" . ucfirst($patient['status']) . "]</span></li>";
        }
        echo "</ul>";
    }
    echo "</div>";
}
?>
