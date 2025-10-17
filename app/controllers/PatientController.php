<?php
session_start();
require_once '../../config/config.php';
require_once '../models/Patient.php';
require_once '../models/Doctor.php';
require_once '../models/Department.php';
require_once '../models/QueueHistory.php';
require_once '../services/SmsService.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'];

        if ($action == 'register') {
            $first_name = $_POST['first_name'];
            $middle_name = $_POST['middle_name'] ?? '';
            $last_name = $_POST['last_name'];
            $birthdate = $_POST['birthdate'];
            $contact_number = $_POST['contact_number'];
            $reason_for_visit = $_POST['reason_for_visit'];
            $parent_guardian = $_POST['parent_guardian'];
            $department_id = $_POST['department_id'];
            $doctor_id = $_POST['doctor_id'];
            $address = $_POST['address'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $civil_status = $_POST['civil_status'] ?? null;
            $registration_datetime = $_POST['registration_datetime'] ?? null;
            $bp = $_POST['bp'] ?? null;
            $temp = $_POST['temp'] ?? null;
            $cr_pr = $_POST['cr_pr'] ?? null;
            $rr = $_POST['rr'] ?? null;
            $wt = $_POST['wt'] ?? null;
            $o2sat = $_POST['o2sat'] ?? null;

            $patient = new Patient($conn);
            $queue_number = $patient->get_next_queue_number($department_id);

            if ($patient->create($first_name, $middle_name, $last_name, $birthdate, $contact_number, $reason_for_visit, $parent_guardian, $queue_number, $department_id, $doctor_id, $address, $gender, $civil_status, $registration_datetime, $bp, $temp, $cr_pr, $rr, $wt, $o2sat)) {
                // Log the registration activity
                $queue_history = new QueueHistory($conn);
                $patient_id = $conn->insert_id; // Get the newly created patient ID
                $queue_history->log_activity($patient_id, 'registered', null, 'waiting', $department_id, $doctor_id, $_SESSION['staff_id'] ?? null);

                // Send welcome SMS notification
                $sms_service = new SmsService();
                $department_model = new Department($conn);
                $department = $department_model->get_by_id($department_id);
                $department_name = $department ? $department['name'] : 'Department';

                $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
                $sms_service->send_welcome_message($full_name, $contact_number, $queue_number, $department_name);

                header("Location: ../../public/dashboard.php?message=Patient registered successfully. Queue number: " . $queue_number);
            } else {
                header("Location: ../../public/dashboard.php?error=Failed to register patient.");
            }

        } else if ($action == 'update_patient') {
            $id = $_POST['id'];
            $first_name = $_POST['first_name'];
            $middle_name = $_POST['middle_name'] ?? '';
            $last_name = $_POST['last_name'];
            $birthdate = $_POST['birthdate'];
            $contact_number = $_POST['contact_number'];
            $reason_for_visit = $_POST['reason_for_visit'];
            $parent_guardian = $_POST['parent_guardian'];
            $department_id = $_POST['department_id'];
            $doctor_id = $_POST['doctor_id'];
            $address = $_POST['address'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $civil_status = $_POST['civil_status'] ?? null;
            $registration_datetime = $_POST['registration_datetime'] ?? null;
            $bp = $_POST['bp'] ?? null;
            $temp = $_POST['temp'] ?? null;
            $cr_pr = $_POST['cr_pr'] ?? null;
            $rr = $_POST['rr'] ?? null;
            $wt = $_POST['wt'] ?? null;
            $o2sat = $_POST['o2sat'] ?? null;

            $patient = new Patient($conn);
            if ($patient->update($id, $first_name, $middle_name, $last_name, $birthdate, $contact_number, $reason_for_visit, $parent_guardian, $department_id, $doctor_id, $address, $gender, $civil_status, $registration_datetime, $bp, $temp, $cr_pr, $rr, $wt, $o2sat)) {
                header("Location: ../../public/dashboard.php?message=Patient updated successfully.");
            } else {
                header("Location: ../../public/dashboard.php?error=Failed to update patient.");
            }
        }
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $action = $_GET['action'];

        if ($action == 'delete') {
            header('Content-Type: application/json');
            try {
                $id = $_GET['id'];
                error_log("Delete request received for patient ID: " . $id);

                if (!is_numeric($id)) {
                    error_log("Invalid patient ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
                    exit();
                }

                $patient = new Patient($conn);
                $queue_history = new QueueHistory($conn);

                $patient_data = $patient->get_by_id($id);
                if ($patient_data) {
                    error_log("Patient found: " . json_encode($patient_data));

                    // Log activity BEFORE updating the patient status
                    $log_result = $queue_history->log_activity($id, 'removed', $patient_data['status'], 'cancelled', $patient_data['department_id'], $patient_data['doctor_id'], $_SESSION['staff_id'] ?? null);
                    error_log("Activity logged: " . ($log_result ? 'true' : 'false'));

                    // Update patient status to cancelled instead of deleting
                    $update_result = $patient->update_status($id, 'cancelled');
                    error_log("Status update result: " . ($update_result ? 'true' : 'false'));

                    if ($update_result) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update patient status']);
                    }
                } else {
                    error_log("Patient not found for ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Patient not found']);
                }
            } catch (Exception $e) {
                error_log("Exception in delete action: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            }
            exit();
        } else if ($action == 'update_status') {
            $id = $_GET['id'];
            $status = $_GET['status'];
            $patient = new Patient($conn);
            $queue_history = new QueueHistory($conn);

            // Get the current patient to retrieve department_id and doctor_id
            $patient_data = $patient->get_by_id($id);
            if ($patient_data) {
                $old_status = $patient_data['status'];
                if ($patient->update_status($id, $status)) {
                    $queue_history->log_activity($id, 'status_changed', $old_status, $status, $patient_data['department_id'], $patient_data['doctor_id'], $_SESSION['staff_id'] ?? null);
                    header("Location: ../../public/dashboard.php?message=Patient status updated successfully.");
                } else {
                    header("Location: ../../public/dashboard.php?error=Failed to update patient status.");
                }
            } else {
                header("Location: ../../public/dashboard.php?error=Patient not found.");
            }
            exit();
        } else if ($action == 'delete_history') {
            $patient_ids = explode(',', $_GET['ids']);
            $queue_history = new QueueHistory($conn);
            $deleted_count = 0;
            foreach ($patient_ids as $patient_id) {
                if (is_numeric($patient_id)) {
                    // Delete all history entries for this patient
                    $query = "DELETE FROM queue_history WHERE patient_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $patient_id);
                    if ($stmt->execute()) {
                        $deleted_count += $stmt->affected_rows;
                    }
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'deleted' => $deleted_count]);
            exit();
        } else if ($action == 'clear_history') {
            $queue_history = new QueueHistory($conn);
            $success = $queue_history->clear_all();
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit();
        } else if ($action == 'reset_queue') {
            header('Content-Type: application/json');
            $department_id = $_GET['department_id'];
            $patient = new Patient($conn);

            if (is_numeric($department_id)) {
                if ($patient->reset_queue_numbers($department_id)) {
                    echo json_encode(['success' => true, 'message' => 'Queue reset successfully. Numbers reassigned starting from 1.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to reset queue.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid department ID.']);
            }
            exit();
        } else if ($action == 'get_latest_patient') {
            header('Content-Type: application/json');
            $department_id = $_GET['department_id'];
            $patient = new Patient($conn);

            if (is_numeric($department_id)) {
                $latest_patient = $patient->get_latest_by_department($department_id);
                if ($latest_patient) {
                    echo json_encode($latest_patient);
                } else {
                    echo json_encode(null);
                }
            } else {
                echo json_encode(['error' => 'Invalid department ID']);
            }
            exit();
        } else if ($action == 'requeue_patient') {
            header('Content-Type: application/json');
            $patient_id = $_GET['patient_id'];

            if (!is_numeric($patient_id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
                exit();
            }

            $patient = new Patient($conn);
            $queue_history = new QueueHistory($conn);

            // Get patient data
            $patient_data = $patient->get_by_id($patient_id);
            if (!$patient_data) {
                echo json_encode(['success' => false, 'message' => 'Patient not found']);
                exit();
            }

            // Get next queue number for the department
            $next_queue_number = $patient->get_next_queue_number($patient_data['department_id']);

            // Update patient status to waiting and assign new queue number
            $update_query = "UPDATE patients SET status = 'waiting', queue_number = ?, check_in_time = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $next_queue_number, $patient_id);

            if ($stmt->execute()) {
                // Log the requeue activity
                $queue_history->log_activity(
                    $patient_id,
                    'requeued',
                    $patient_data['status'],
                    'waiting',
                    $patient_data['department_id'],
                    $patient_data['doctor_id'],
                    $_SESSION['staff_id'] ?? null
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Patient requeued successfully',
                    'queue_number' => $next_queue_number
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to requeue patient']);
            }
            exit();
        }
}
?>
