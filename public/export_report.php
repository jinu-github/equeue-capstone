<?php
session_start();

// Prevent browser from caching or showing this page from history
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['staff_id'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

require_once '../config/config.php';
require_once '../app/models/Report.php';
require_once '../app/models/Department.php';
require_once '../app/models/Patient.php';

// Include the libraries
require_once __DIR__ . '/../vendor/autoload.php';
 // Commented out - dependencies not installed

use Dompdf\Dompdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Get parameters
$period = $_POST['period'] ?? 'day';
$date = $_POST['date'] ?? date('Y-m-d');
$export_format = $_POST['export_format'] ?? 'pdf';

$report_model = new Report($conn);
$department_model = new Department($conn);
$patient_model = new Patient($conn);

// Get staff's department
$staff_department_id = $_SESSION['department_id'] ?? null;

// Get report data
$patients = $report_model->get_patients_for_period($period, $staff_department_id);
$status_distribution = $report_model->get_patient_status_distribution($period, $staff_department_id);
$daily_trends = $report_model->get_daily_patient_trends($period, $staff_department_id);
$hourly_distribution = $report_model->get_hourly_patient_distribution($period, $staff_department_id);

// Generate filename
$filename = 'eQueue_Report_' . ucfirst($period) . '_' . date('Y-m-d_H-i-s');

// Check if dependencies are available
if (!class_exists('Dompdf\Dompdf')) {
    die('Export functionality requires Composer dependencies. Please run "composer install" in the project root.');
}

if ($export_format === 'pdf') {
    generatePDF($patients, $status_distribution, $daily_trends, $hourly_distribution, $period, $filename, $patient_model);
} elseif ($export_format === 'doc') {
    generateDOCX($patients, $status_distribution, $daily_trends, $hourly_distribution, $period, $filename, $patient_model);
}

function generatePDF($patients, $status_distribution, $daily_trends, $hourly_distribution, $period, $filename, $patient_model) {
    $html = generateReportHTML($patients, $status_distribution, $daily_trends, $hourly_distribution, $period, $patient_model);

    // Create PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    // Output PDF
    $dompdf->stream($filename . '.pdf', array('Attachment' => true));
}

function generateDOCX($patients, $status_distribution, $daily_trends, $hourly_distribution, $period, $filename, $patient_model) {
    // Create Word document
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    // Add title
    $section->addTitle('eQueue Report - ' . ucfirst($period), 1);
    $section->addText('Generated on: ' . date('Y-m-d H:i:s'));
    $section->addText('Report Period: ' . ($period == 'day' ? 'Today' : ($period == 'week' ? 'Last 7 days' : 'Last 30 days')));
    $section->addTextBreak(2);

    // Summary Statistics
    $section->addTitle('Summary Statistics', 2);
    $section->addText('Total Patients: ' . count($patients));

    $status_counts = [];
    foreach ($status_distribution as $status) {
        $status_counts[$status['status']] = $status['count'];
    }

    $section->addText('Waiting: ' . ($status_counts['waiting'] ?? 0));
    $section->addText('In Consultation: ' . ($status_counts['in-consultation'] ?? 0));
    $section->addText('Completed: ' . ($status_counts['done'] ?? 0));
    $section->addText('Cancelled: ' . ($status_counts['cancelled'] ?? 0));
    $section->addTextBreak(2);

    // Patient Details Table
    $section->addTitle('Patient Details', 2);
    $table = $section->addTable();
    $table->addRow();
    $table->addCell(1000)->addText('ID');
    $table->addCell(2000)->addText('Name');
    $table->addCell(1000)->addText('Age');
    $table->addCell(1500)->addText('Contact');
    $table->addCell(1500)->addText('Department');
    $table->addCell(1500)->addText('Doctor');
    $table->addCell(2000)->addText('Reason for Visit');
    $table->addCell(1000)->addText('Queue Number');
    $table->addCell(1200)->addText('Status');
    $table->addCell(1500)->addText('Check-in Time');
    $table->addCell(1500)->addText('Created At');

    foreach ($patients as $patient) {
        $table->addRow();
        $table->addCell(1000)->addText($patient['id']);
        $table->addCell(2000)->addText($patient_model->combineNames($patient['first_name'], $patient['middle_name'], $patient['last_name']));
        $table->addCell(1000)->addText($patient['age']);
        $table->addCell(1500)->addText($patient['contact_number']);
        $table->addCell(1500)->addText($patient['department_name'] ?? 'N/A');
        $table->addCell(1500)->addText($patient['doctor_name'] ?? 'Not Assigned');
        $table->addCell(2000)->addText($patient['reason_for_visit'] ?? 'N/A');
        $table->addCell(1000)->addText($patient['queue_number']);
        $table->addCell(1200)->addText(ucfirst($patient['status']));
        $table->addCell(1500)->addText(date('M d, Y g:i A', strtotime($patient['check_in_time'])));
        $table->addCell(1500)->addText(date('M d, Y g:i A', strtotime($patient['created_at'])));
    }

    $section->addTextBreak(2);

    // Status Distribution
    $section->addTitle('Patient Status Distribution', 2);
    $statusTable = $section->addTable();
    $statusTable->addRow();
    $statusTable->addCell(2000)->addText('Status');
    $statusTable->addCell(1000)->addText('Count');

    foreach ($status_distribution as $status) {
        $statusTable->addRow();
        $statusTable->addCell(2000)->addText(ucfirst($status['status']));
        $statusTable->addCell(1000)->addText($status['count']);
    }

    // Output DOCX
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '.docx"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
}

function generateReportHTML($patients, $status_distribution, $daily_trends, $hourly_distribution, $period, $patient_model) {
    $html = "<h1>eQueue Report - " . ucfirst($period) . "</h1>";
    $html .= "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>";
    $html .= "<p>Report Period: " . ($period == 'day' ? 'Today' : ($period == 'week' ? 'Last 7 days' : 'Last 30 days')) . "</p>";

    // Summary Statistics
    $html .= "<h2>Summary Statistics</h2>";
    $html .= "<ul>";
    $html .= "<li>Total Patients: " . count($patients) . "</li>";

    $status_counts = [];
    foreach ($status_distribution as $status) {
        $status_counts[$status['status']] = $status['count'];
    }

    $html .= "<li>Waiting: " . ($status_counts['waiting'] ?? 0) . "</li>";
    $html .= "<li>In Consultation: " . ($status_counts['in-consultation'] ?? 0) . "</li>";
    $html .= "<li>Completed: " . ($status_counts['done'] ?? 0) . "</li>";
    $html .= "<li>Cancelled: " . ($status_counts['cancelled'] ?? 0) . "</li>";
    $html .= "</ul>";

    // Patient Details Table
    $html .= "<h2>Patient Details</h2>";
    $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    $html .= "<thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Contact</th><th>Department</th><th>Doctor</th><th>Reason for Visit</th><th>Queue Number</th><th>Status</th><th>Check-in Time</th><th>Created At</th></tr></thead>";
    $html .= "<tbody>";

    foreach ($patients as $patient) {
        $html .= "<tr>";
        $html .= "<td>" . $patient['id'] . "</td>";
        $html .= "<td>" . htmlspecialchars($patient_model->combineNames($patient['first_name'], $patient['middle_name'], $patient['last_name'])) . "</td>";
        $html .= "<td>" . $patient['age'] . "</td>";
        $html .= "<td>" . htmlspecialchars($patient['contact_number']) . "</td>";
        $html .= "<td>" . htmlspecialchars($patient['department_name'] ?? 'N/A') . "</td>";
        $html .= "<td>" . htmlspecialchars($patient['doctor_name'] ?? 'Not Assigned') . "</td>";
        $html .= "<td>" . htmlspecialchars($patient['reason_for_visit'] ?? 'N/A') . "</td>";
        $html .= "<td>" . $patient['queue_number'] . "</td>";
        $html .= "<td>" . ucfirst($patient['status']) . "</td>";
        $html .= "<td>" . date('M d, Y g:i A', strtotime($patient['check_in_time'])) . "</td>";
        $html .= "<td>" . date('M d, Y g:i A', strtotime($patient['created_at'])) . "</td>";
        $html .= "</tr>";
    }

    $html .= "</tbody></table>";

    // Status Distribution
    $html .= "<h2>Patient Status Distribution</h2>";
    $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    $html .= "<thead><tr><th>Status</th><th>Count</th></tr></thead>";
    $html .= "<tbody>";

    foreach ($status_distribution as $status) {
        $html .= "<tr>";
        $html .= "<td>" . ucfirst($status['status']) . "</td>";
        $html .= "<td>" . $status['count'] . "</td>";
        $html .= "</tr>";
    }

    $html .= "</tbody></table>";

    // Daily Trends
    if (!empty($daily_trends)) {
        $html .= "<h2>Daily Patient Trends</h2>";
        $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $html .= "<thead><tr><th>Date</th><th>Patient Count</th></tr></thead>";
        $html .= "<tbody>";

        foreach ($daily_trends as $trend) {
            $html .= "<tr>";
            $html .= "<td>" . $trend['date'] . "</td>";
            $html .= "<td>" . $trend['count'] . "</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
    }

    // Hourly Distribution
    if (!empty($hourly_distribution)) {
        $html .= "<h2>Hourly Patient Distribution</h2>";
        $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $html .= "<thead><tr><th>Hour</th><th>Patient Count</th></tr></thead>";
        $html .= "<tbody>";

        foreach ($hourly_distribution as $hour) {
            $hour_label = $hour['hour'] % 12 ?: 12;
            $ampm = $hour['hour'] < 12 ? 'AM' : 'PM';
            $html .= "<tr>";
            $html .= "<td>" . $hour_label . ' ' . $ampm . "</td>";
            $html .= "<td>" . $hour['count'] . "</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
    }

    return $html;
}
?>
<script>
window.addEventListener("pageshow", function(event) {
    if (event.persisted) {
        // If page was loaded from bfcache, force reload
        window.location.reload();
    }
});
</script>
