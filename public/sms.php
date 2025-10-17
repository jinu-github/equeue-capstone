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
require_once '../app/models/Patient.php';
require_once '../app/models/Department.php';
require_once '../app/services/SmsService.php';

$patient_model = new Patient($conn);
$department_model = new Department($conn);

// Get staff's department
$staff_department_id = $_SESSION['department_id'] ?? null;

// Get all patients
$patients = $patient_model->get_all();
$departments = $department_model->get_all();

// Handle SMS sending and patient removal
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'remove_patients') {
        $removed_ids = $_POST['removed_ids'] ?? [];
        if (!isset($_SESSION['removed_patients'])) {
            $_SESSION['removed_patients'] = [];
        }
        $_SESSION['removed_patients'] = array_unique(array_merge($_SESSION['removed_patients'], $removed_ids));
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action == 'send_sms') {
        $patient_ids = $_POST['patient_ids'] ?? [];
        $custom_message = trim($_POST['message']);
        $recipient_type = $_POST['recipient_type'];

        if (empty($custom_message)) {
            $message = "Please enter a message to send.";
            $message_type = "error";
        } elseif (empty($patient_ids) && $recipient_type == 'selected') {
            $message = "Please select at least one patient.";
            $message_type = "error";
        } else {
            $sms_service = new SmsService();
            $sent_count = 0;
            $failed_count = 0;

            if ($recipient_type == 'selected') {
                // Send to selected patients
                foreach ($patient_ids as $patient_id) {
                    $patient = $patient_model->get_by_id($patient_id);
                    if ($patient && $sms_service->send_sms($patient['contact_number'], $custom_message)) {
                        $sent_count++;
                    } else {
                        $failed_count++;
                    }
                }
            } else {
                // Send to all patients in selected department
                $department_id = $_POST['department_id'];
                $dept_patients = $patient_model->get_all_by_department($department_id);

                while ($patient = $dept_patients->fetch_assoc()) {
                    if ($sms_service->send_sms($patient['contact_number'], $custom_message)) {
                        $sent_count++;
                    } else {
                        $failed_count++;
                    }
                }
            }

            if ($sent_count > 0) {
                $message = "SMS sent successfully to {$sent_count} patient(s).";
                if ($failed_count > 0) {
                    $message .= " {$failed_count} message(s) failed to send.";
                }
                $message_type = "success";
            } else {
                $message = "Failed to send SMS messages.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send SMS - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components/sms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        header { margin-bottom: 0.5rem; }
        .container { padding-top: 1rem; }
        main { padding-top: 1rem; }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-sms"></i> eQueue - Send SMS</h1>
        <div class="header-nav">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="../app/controllers/StaffController.php?action=logout" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    <div class="container">
        <main>
            <div class="form-section">
                <h2>Send Manual SMS Messages</h2>

                <?php if(!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="sms.php" method="POST">
                    <input type="hidden" name="action" value="send_sms" id="action_input">

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="recipient_type">Send to:</label>
                            <select id="recipient_type" name="recipient_type" required>
                                <option value="selected">Selected Patients</option>
                                <option value="department">All Patients in Department</option>
                            </select>
                        </div>

                        <div class="form-group full-width" id="patient_selection" style="display: block;">
                            <label>Select Patients:</label>
                            <div class="patient-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem;">
                                <?php
                                $current_dept = '';
                                while ($patient = $patients->fetch_assoc()):
                                    if ($current_dept != $patient['department_name']):
                                        if ($current_dept != '') echo '</div>';
                                        $current_dept = $patient['department_name'];
                                        echo "<div class='department-header'>{$current_dept} Department</div><div>";
                                    endif;
                                ?>
                                    <div class="patient-card" data-patient-id="<?php echo $patient['id']; ?>">
                                        <input type="checkbox" name="patient_ids[]" value="<?php echo $patient['id']; ?>">
                                        <div class="patient-info">
                                            <div class="patient-name"><?php echo htmlspecialchars($patient_model->combineNames($patient['first_name'], $patient['middle_name'], $patient['last_name'])); ?></div>
                                            <div class="patient-details"><?php echo htmlspecialchars($patient['contact_number']); ?> - Queue #<?php echo $patient['queue_number']; ?></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                <?php if ($current_dept != '') echo '</div>'; ?>
                            </div>
                            <div class="flex justify-start items-center mt-2 gap-2" id="delete_buttons" style="display: block;">
                                <button type="button" class="btn btn-danger" onclick="submitRemoveSelected()"><i class="fas fa-trash"></i> Remove Selected</button>
                                <button type="button" class="btn btn-danger" onclick="submitClearAll()"><i class="fas fa-trash-alt"></i> Clear All</button>
                            </div>
                        </div>

                        <div class="form-group full-width" id="department_selection" style="display: none;">
                            <label for="department_id">Select Department:</label>
                            <select id="department_id" name="department_id">
                                <option value="">-- Select Department --</option>
                                <?php
                                $departments->data_seek(0); // Reset pointer
                                while ($dept = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?> Department</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="message">Message:</label>
                            <textarea id="message" name="message" rows="4" placeholder="Enter your SMS message here..." required maxlength="160"></textarea>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                <small style="color: #6b7280; font-size: 0.875rem;">Maximum 160 characters. <span id="char_count">0</span>/160</small>
                                <button type="button" class="btn btn-secondary" onclick="clearMessage()" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">Clear Message</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-4">
                        <button type="button" class="btn btn-secondary" onclick="selectAll()"><i class="fas fa-check-square"></i> Select All Patients</button>
                        <button type="button" class="btn btn-secondary" onclick="clearSelection()"><i class="fas fa-times"></i> Clear Selection</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send SMS</button>
                    </div>
                </form>
            </div>

            <!-- Quick Message Templates -->
            <div class="form-section">
                <h3>Quick Message Templates</h3>
                <div class="template-buttons" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('Update: There are [X] patients ahead of you. Please prepare to proceed to the waiting area.')"><i class="fas fa-info-circle"></i> Queue Status Update</button>
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('It\'s your turn now. Please proceed to the consultation room.')"><i class="fas fa-bell"></i> Your Turn Notification</button>
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('You missed your turn. Please check in at reception to be re-queued.')"><i class="fas fa-exclamation-triangle"></i> Missed Turn Notice</button>
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('Please arrive 15 minutes before your scheduled appointment time.')"><i class="fas fa-calendar-check"></i> Appointment Reminder</button>
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('Your consultation is running 10 minutes behind schedule. Thank you for your patience.')"><i class="fas fa-clock"></i> Delay Notification</button>
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('Please bring your ID and any previous medical records to your appointment.')"><i class="fas fa-file-alt"></i> Required Documents</button>
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('Thank you for choosing our clinic. We look forward to serving you.')"><i class="fas fa-heart"></i> Thank You Message</button>
                    <button type="button" class="btn btn-secondary" onclick="useTemplate('We apologize for any inconvenience. Your health and safety are our top priority.')"><i class="fas fa-sad-tear"></i> Apology Message</button>
                </div>
            </div>
        </main>
    </div>

    <script>
    $(document).ready(function() {
        // Character counter
        $('#message').on('input', function() {
            var charCount = $(this).val().length;
            $('#char_count').text(charCount);
            if (charCount > 160) {
                $('#char_count').css('color', 'red');
            } else {
                $('#char_count').css('color', '#6b7280');
            }
        });

        // Toggle recipient type
        $('#recipient_type').change(function() {
            if ($(this).val() == 'selected') {
                $('#patient_selection').show();
                $('#delete_buttons').show();
                $('#department_selection').hide();
            } else {
                $('#patient_selection').hide();
                $('#delete_buttons').hide();
                $('#department_selection').show();
            }
        });

        // Hide removed patients on page load
        var removedPatients = <?php echo json_encode($_SESSION['removed_patients'] ?? []); ?>;
        removedPatients.forEach(function(id) {
            var patientCard = document.querySelector('.patient-card[data-patient-id="' + id + '"]');
            if (patientCard) {
                patientCard.style.display = 'none';
            }
        });
        // Clean up empty department sections on page load
        var departmentHeaders = document.querySelectorAll('.department-header');
        departmentHeaders.forEach(function(header) {
            var patientContainer = header.nextElementSibling;
            if (patientContainer) {
                var patientCards = patientContainer.querySelectorAll('.patient-card');
                var allHidden = patientCards.length > 0 && Array.from(patientCards).every(function(card) {
                    return card.style.display === 'none';
                });
                if (allHidden || patientCards.length === 0) {
                    header.remove();
                    patientContainer.remove();
                }
            }
        });
    });

    function selectAll() {
        $('input[name="patient_ids[]"]').prop('checked', true);
    }

    function clearSelection() {
        $('input[name="patient_ids[]"]').prop('checked', false);
    }

    function useTemplate(template) {
        $('#message').val(template);
        $('#message').trigger('input');
    }

    function clearMessage() {
        $('#message').val('');
        $('#message').trigger('input');
    }

    function submitRemoveSelected() {
        var selected = document.querySelectorAll('input[name="patient_ids[]"]:checked');
        if (selected.length === 0) {
            alert('Please select at least one patient to remove from view.');
            return;
        }
        if (confirm('Are you sure you want to remove the selected patients from this view? This will persist across page refreshes.')) {
            var removedIds = [];
        selected.forEach(function(checkbox) {
            removedIds.push(checkbox.value);
            var patientCard = checkbox.closest('.patient-card');
            if (patientCard) {
                patientCard.remove();
            }
        });
        // Clean up empty department sections
        var departmentHeaders = document.querySelectorAll('.department-header');
        departmentHeaders.forEach(function(header) {
            var patientContainer = header.nextElementSibling;
            if (patientContainer && patientContainer.children.length === 0) {
                header.remove();
                patientContainer.remove();
            }
        });
        // Send removed IDs to server
            $.post('sms.php', { action: 'remove_patients', removed_ids: removedIds }, function(response) {
                // Clear all selections after removal
                clearSelection();
                // Show success message
                showAlert('Selected patients have been removed from view.', 'success');
            }).fail(function() {
                showAlert('Failed to save removal. Please try again.', 'error');
            });
        }
    }

    function submitClearAll() {
        var allPatients = document.querySelectorAll('.patient-card');
        if (allPatients.length === 0) {
            alert('No patients to remove.');
            return;
        }
        if (confirm('Are you sure you want to remove ALL patients from this view? This will persist across page refreshes.')) {
            var removedIds = [];
        allPatients.forEach(function(card) {
            var checkbox = card.querySelector('input[name="patient_ids[]"]');
            if (checkbox) {
                removedIds.push(checkbox.value);
            }
            card.remove();
        });
        // Clean up empty department sections
        var departmentHeaders = document.querySelectorAll('.department-header');
        departmentHeaders.forEach(function(header) {
            var patientContainer = header.nextElementSibling;
            if (patientContainer && patientContainer.children.length === 0) {
                header.remove();
                patientContainer.remove();
            }
        });
        // Send removed IDs to server
            $.post('sms.php', { action: 'remove_patients', removed_ids: removedIds }, function(response) {
                // Clear all selections after removal
                clearSelection();
                // Show success message
                showAlert('All patients have been removed from view.', 'success');
            }).fail(function() {
                showAlert('Failed to save removal. Please try again.', 'error');
            });
        }
    }

    function showAlert(message, type) {
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type;
        alertDiv.innerHTML = message;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.maxWidth = '400px';

        document.body.appendChild(alertDiv);

        // Remove alert after 3 seconds
        setTimeout(function() {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 3000);
    }
    </script>
<script>
window.addEventListener("pageshow", function(event) {
    if (event.persisted) {
        // If page was loaded from bfcache, force reload
        window.location.reload();
    }
});
</script>
</body>
</html>
