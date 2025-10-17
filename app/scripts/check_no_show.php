<?php
// Script to check and update patients who have been inactive for more than 30 minutes
// This script should be run periodically via a scheduled task (Windows Task Scheduler)

require_once '../../config/config.php';
require_once '../models/Patient.php';
require_once '../models/QueueHistory.php';

// Initialize models
$patient_model = new Patient($conn);
$queue_history_model = new QueueHistory($conn);

// Check and update no-show patients
$updated_count = $patient_model->check_and_update_no_show_patients($queue_history_model);

// Log the result
$log_message = date('Y-m-d H:i:s') . " - No-show check completed. Updated $updated_count patients.\n";
file_put_contents('no_show_log.txt', $log_message, FILE_APPEND);

// Optional: Output to console for debugging
echo "No-show check completed. Updated $updated_count patients.\n";

?>
