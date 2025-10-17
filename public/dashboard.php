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
require_once '../app/models/Department.php';
require_once '../app/models/Patient.php';

$department_model = new Department($conn);
$patient_model = new Patient($conn);

// Check user role
$user_role = $_SESSION['role'] ?? 'staff'; // Default to staff if not set
$is_receptionist = ($user_role === 'receptionist');

// For receptionists, get all departments; for staff, get only their assigned department
if ($is_receptionist) {
    $departments = $department_model->get_all()->fetch_all(MYSQLI_ASSOC);
    $staff_department = null; // Receptionists don't have a fixed department
} else {
    $staff_department = $department_model->get_by_id($_SESSION['department_id']);
    $departments = [$staff_department]; // Make it an array for compatibility
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eQueue - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components/dashboard.css">
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
        <h1><i class="fas fa-tachometer-alt"></i> eQueue - Dashboard</h1>
        <div class="header-nav">
            <?php if (!$is_receptionist): ?>
                <a href="display.php" target="_blank"><i class="fas fa-tv"></i> Display Page</a>
                <a href="sms.php"><i class="fas fa-sms"></i> Send SMS</a>
                <a href="queue_history.php"><i class="fas fa-history"></i> Queue History</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <a href="../app/controllers/StaffController.php?action=logout" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    <div class="container">
        <main>
            <!-- Patient Registration Section - Only show for receptionists -->
            <?php if ($is_receptionist): ?>
                <div class="form-section">
                    <h2>Patient Information Form</h2>

                    <?php if(isset($_GET['message'])): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($_GET['message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="../app/controllers/PatientController.php" method="POST">
                        <input type="hidden" name="action" value="register">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required placeholder="Enter first name">
                            </div>

                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" placeholder="Enter middle name (optional)">
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required placeholder="Enter last name">
                            </div>

                            <div class="form-group">
                                <label for="birthdate">Birthdate</label>
                                <input type="date" id="birthdate" name="birthdate" required>
                            </div>

                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" readonly>
                            </div>

                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" required>
                            </div>

                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" placeholder="Enter patient's address"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="preferred not to say">Preferred not to say</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="civil_status">Civil Status</label>
                                <select id="civil_status" name="civil_status">
                                    <option value="">Select Civil Status</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="widow">Widow</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="registration_datetime">Registration Date & Time</label>
                                <input type="datetime-local" id="registration_datetime" name="registration_datetime">
                            </div>

                            <div class="form-group full-width">
                                <label for="reason_for_visit">Reason for Visit</label>
                                <select id="reason_for_visit" name="reason_for_visit" required>
                                    <option value="">Select Reason</option>
                                    <option value="Check-up">Check-up</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Prescription">Prescription</option>
                                    <option value="Laboratory">Laboratory</option>
                                    <option value="Vaccination">Vaccination</option>
                                    <option value="Consultation">Consultation</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="parent_guardian">Parent/Guardian</label>
                                <input type="text" id="parent_guardian" name="parent_guardian" placeholder="Enter parent or guardian name">
                            </div>

                            <div class="form-group full-width">
                                <label>Vital Signs</label>
                            </div>

                            <div class="form-group">
                                <label for="bp">BP (Blood Pressure)</label>
                                <input type="text" id="bp" name="bp" placeholder="e.g., 120/80">
                            </div>

                            <div class="form-group">
                                <label for="temp">TEMP (Temperature)</label>
                                <input type="text" id="temp" name="temp" placeholder="e.g., 36.5°C">
                            </div>

                            <div class="form-group">
                                <label for="cr_pr">CR/PR (Cardiac Rate/Pulse Rate)</label>
                                <input type="text" id="cr_pr" name="cr_pr" placeholder="e.g., 80 bpm">
                            </div>

                            <div class="form-group">
                                <label for="rr">RR (Respiratory Rate)</label>
                                <input type="text" id="rr" name="rr" placeholder="e.g., 16 breaths/min">
                            </div>

                            <div class="form-group">
                                <label for="wt">WT (Weight)</label>
                                <input type="text" id="wt" name="wt" placeholder="e.g., 70 kg">
                            </div>

                            <div class="form-group">
                                <label for="o2sat">O2SAT (Oxygen Saturation)</label>
                                <input type="text" id="o2sat" name="o2sat" placeholder="e.g., 98%">
                            </div>

                            <div class="form-group">
                                <label for="department_id">Department *</label>
                                <select id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?> Department</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="doctor_id">Assign Doctor</label>
                                <select id="doctor_id" name="doctor_id" required>
                                    <option value="">Select Department First</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-4">
                            <button type="submit" class="btn btn-primary">Add Patient</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php $departments_copy = $departments; ?>

            <!-- Patient Queues Section - Only show for staff -->
            <?php if (!$is_receptionist): ?>
                <div class="queue-sections">
                    <div class="section-header">
                        <h2>Department Queues</h2>
                    </div>

                    <?php foreach ($departments_copy as $dept): ?>
                        <div class="table-section">
                            <div class="table-header">
                                <h3 style="color: white;"><?php echo $dept['name']; ?> Department</h3>
                                <div class="header-actions">
                                    <button class="btn btn-warning reset-queue-btn" data-department-id="<?php echo $dept['id']; ?>" data-department-name="<?php echo $dept['name']; ?>">
                                        <i class="fas fa-redo"></i> Reset Queue
                                    </button>
                                </div>
                            </div>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Queue</th>
                                        <th>Patient Name</th>
                                        <th>Status</th>
                                        <th>Check-in Time</th>
                                        <th>Doctor</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $patients = $patient_model->get_all_by_department($dept['id']);
                                    $has_patients = false;
                                    $previous_patient_completed = true; // First patient can always start
                                    while ($patient = $patients->fetch_assoc()):
                                        $has_patients = true;
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="queue-number"><?php echo $patient['queue_number']; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($patient_model->combineNames($patient['first_name'], $patient['middle_name'], $patient['last_name'])); ?></strong>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $patient['status'])); ?>">
                                                    <?php echo $patient['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('g:i A', strtotime($patient['check_in_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($patient['doctor_name'] ?? 'Not Assigned'); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <button type="button" id="view-patient-form-btn" class="btn btn-secondary" data-department-id="<?php echo $staff_department['id']; ?>"><strong>View Patient Summary</strong></button>
                                                    <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-secondary">Edit</a>
                                                    <?php if($patient['status'] !== 'in consultation' && $previous_patient_completed): ?>
                                                        <a href="../app/controllers/PatientController.php?action=update_status&id=<?php echo $patient['id']; ?>&status=in consultation"
                                                           class="btn btn-warning">Start</a>
                                                    <?php endif; ?>
                                                    <?php if($patient['status'] === 'in consultation'): ?>
                                                        <a href="../app/controllers/PatientController.php?action=update_status&id=<?php echo $patient['id']; ?>&status=done"
                                                           class="btn btn-success">Complete</a>
                                                    <?php endif; ?>
                                                    <a href="#" class="btn btn-danger remove-patient" data-id="<?php echo $patient['id']; ?>">Remove</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        // Update for next patient: can start only if current patient is done
                                        $previous_patient_completed = ($patient['status'] === 'done');
                                    endwhile; ?>

                                    <?php if (!$has_patients): ?>
                                        <tr>
                                            <td colspan="6" class="text-center" style="color: #6b7280; padding: 2rem;">
                                                No patients in queue for this department
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php if (!$is_receptionist): ?>
    <!-- Modal for viewing patient summary (staff only) -->
    <div id="patient-form-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Patient Information Summary</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div id="patient-summary-content">
                    <!-- Patient summary will be populated here -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    $(document).ready(function() {
        // Only initialize doctor loading if the form is present (for receptionists)
        if ($('#doctor_id').length > 0) {
            // Load doctors based on department selection
            function loadDoctors(departmentId) {
                var doctorSelect = $('#doctor_id');

                if (departmentId) {
                    // Show loading state
                    doctorSelect.html('<option value="">Loading doctors...</option>');

                    $.ajax({
                        url: 'get_doctors.php',
                        type: 'GET',
                        data: { department_id: departmentId },
                        dataType: 'json',
                        success: function(doctors) {
                            doctorSelect.empty().append('<option value=""> Select Doctor </option>');
                            $.each(doctors, function(key, doctor) {
                                doctorSelect.append('<option value="' + doctor.id + '">' + doctor.name + '</option>');
                            });
                        },
                        error: function() {
                            doctorSelect.html('<option value="">Error loading doctors</option>');
                            alert('Failed to load doctors. Please check the connection.');
                        }
                    });
                } else {
                    doctorSelect.empty().append('<option value=""> Select Department First </option>');
                }
            }

            // Load doctors for the staff's department on page load (for staff) - but since form is hidden for staff, this won't run
            var initialDepartmentId = $('input[name="department_id"]').val();
            if (initialDepartmentId) {
                loadDoctors(initialDepartmentId);
            }

            // Handle department change for receptionists
            $('#department_id').change(function() {
                var selectedDepartmentId = $(this).val();
                loadDoctors(selectedDepartmentId);
            });
        }

        // Handle remove patient button clicks
        $(document).on('click', '.remove-patient', function(e) {
            e.preventDefault();

            var patientId = $(this).data('id');
            var patientRow = $(this).closest('tr');

            // Remove the row immediately
            patientRow.fadeOut(300, function() {
                $(this).remove();
                // Check if table is empty and show no patients message if needed
                var tbody = patientRow.closest('tbody');
                if (tbody.find('tr').length === 1 && tbody.find('tr').text().includes('No patients')) {
                    // Already has no patients message
                } else if (tbody.find('tr').length === 0) {
                    tbody.append('<tr><td colspan="6" class="text-center" style="color: #6b7280; padding: 2rem;">No patients in queue for this department</td></tr>');
                }
            });

            // Send AJAX request to delete from database
            $.ajax({
                url: '../app/controllers/PatientController.php',
                type: 'GET',
                data: {
                    action: 'delete',
                    id: patientId
                },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        // If deletion failed, show error but row is already removed
                        alert('Failed to remove patient from database: ' + (response.message || 'Unknown error'));
                        console.log('Delete response:', response);
                        // Optionally, you could reload the page or add the row back
                        location.reload();
                    } else {
                        console.log('Patient deleted successfully');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error Details:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        readyState: xhr.readyState
                    });
                    alert('Error removing patient. Check console for details. Page will reload.');
                    location.reload();
                }
            });
        });

        // Handle reset queue button clicks
        $(document).on('click', '.reset-queue-btn', function(e) {
            e.preventDefault();

            var button = $(this);
            var departmentId = button.data('department-id');
            var departmentName = button.data('department-name');

            if (confirm('Are you sure you want to reset the queue for ' + departmentName + ' department? This will reassign queue numbers starting from 1 for all active patients.')) {
                // Disable button and show loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Resetting...');

                // Send AJAX request to reset queue
                $.ajax({
                    url: '../app/controllers/PatientController.php',
                    type: 'GET',
                    data: {
                        action: 'reset_queue',
                        department_id: departmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success !== false) {
                            // Success - reload page to show updated queue numbers
                            alert('Queue reset successfully! Queue numbers have been reassigned starting from 1.');
                            location.reload();
                        } else {
                            // Error
                            alert('Failed to reset queue: ' + (response.message || 'Unknown error'));
                            button.prop('disabled', false).html('<i class="fas fa-redo"></i> Reset Queue');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error Details:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            readyState: xhr.readyState
                        });
                        alert('Error resetting queue. Check console for details.');
                        button.prop('disabled', false).html('<i class="fas fa-redo"></i> Reset Queue');
                    }
                });
            }
        });

        // Theme toggle functionality (only if theme toggle button exists)
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const body = document.body;
                const currentTheme = body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                body.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon();
            });

            function updateThemeIcon() {
                const currentTheme = document.body.getAttribute('data-theme');
                themeToggle.innerHTML = currentTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            }

            // Load saved theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            updateThemeIcon();
        }

        // Function to calculate age from birthdate
        function calculateAge(birthdate) {
            if (!birthdate) return '';
            const birth = new Date(birthdate);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        }

        // Calculate age on birthdate change
        $('#birthdate').change(function() {
            const birthdate = $(this).val();
            const age = calculateAge(birthdate);
            $('#age').val(age);
            updateParentGuardianField(age);
        });

        // Function to update Parent/Guardian field based on age
        function updateParentGuardianField(age) {
            const parentGuardianField = $('#parent_guardian');
            if (age <= 17) {
                parentGuardianField.prop('required', true);
                parentGuardianField.val('');
                parentGuardianField.attr('placeholder', 'Enter parent or guardian name (required)');
            } else if (age > 18) {
                parentGuardianField.prop('required', false);
                parentGuardianField.val('N/A');
                parentGuardianField.attr('placeholder', 'N/A (optional to enter parent/guardian name)');
            } else {
                // Age is 18
                parentGuardianField.prop('required', false);
                parentGuardianField.val('');
                parentGuardianField.attr('placeholder', 'Optional: Enter parent or guardian name');
            }
        }

        // Update Parent/Guardian field on age change
        $('#age').change(function() {
            const age = parseInt($(this).val());
            updateParentGuardianField(age);
        });

        // Auto-add °C to temperature field
        $('#temp').on('input', function() {
            let value = $(this).val();
            // Remove any existing °C and non-numeric characters except decimal point
            value = value.replace(/[^0-9.]/g, '');
            // If there's a valid number, add °C
            if (value && !isNaN(value)) {
                $(this).val(value + '°C');
            }
        });

        // Form validation on submit
        $('form[action*="PatientController.php"]').on('submit', function(e) {
            const age = parseInt($('#age').val());
            const parentGuardian = $('#parent_guardian').val().trim();
            if (age <= 17) {
                if (parentGuardian === '' || parentGuardian.toLowerCase() === 'n/a') {
                    e.preventDefault();
                    alert('Parent/Guardian name is required for patients 17 years or younger and cannot be "N/A".');
                    $('#parent_guardian').focus();
                    return false;
                }
            }
        });

        // Modal functionality for staff to view patient summary
        $('#view-patient-form-btn').on('click', function() {
            var departmentId = $(this).data('department-id');

            // Fetch latest patient data for the department
            $.ajax({
                url: '../app/controllers/PatientController.php',
                type: 'GET',
                data: {
                    action: 'get_latest_patient',
                    department_id: departmentId
                },
                dataType: 'json',
                success: function(patient) {
                    var summaryContent = $('#patient-summary-content');

                    if (patient) {
                        // Build patient summary HTML
                        var summaryHTML = `
                            <div class="patient-summary">
                                <div class="summary-section">
                                    <h3>Personal Information</h3>
                                    <div class="summary-grid">
                                        <div class="summary-item">
                                            <strong>Full Name:</strong> ${patient.first_name || 'N/A'} ${patient.middle_name || ''} ${patient.last_name || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Birthdate:</strong> ${patient.birthdate ? new Date(patient.birthdate).toLocaleDateString() : 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Age:</strong> ${patient.age || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Gender:</strong> ${patient.gender || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Civil Status:</strong> ${patient.civil_status || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Contact Number:</strong> ${patient.contact_number || 'N/A'}
                                        </div>
                                        <div class="summary-item full-width">
                                            <strong>Address:</strong> ${patient.address || 'N/A'}
                                        </div>
                                    </div>
                                </div>

                                <div class="summary-section">
                                    <h3>Visit Information</h3>
                                    <div class="summary-grid">
                                        <div class="summary-item">
                                            <strong>Department:</strong> ${patient.department_name || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Doctor:</strong> ${patient.doctor_name || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Reason for Visit:</strong> ${patient.reason_for_visit || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Parent/Guardian:</strong> ${patient.parent_guardian || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Registration Date:</strong> ${patient.registration_datetime ? new Date(patient.registration_datetime).toLocaleString() : 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Queue Number:</strong> ${patient.queue_number || 'N/A'}
                                        </div>
                                    </div>
                                </div>

                                <div class="summary-section">
                                    <h3>Vital Signs</h3>
                                    <div class="summary-grid">
                                        <div class="summary-item">
                                            <strong>BP:</strong> ${patient.bp || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Temperature:</strong> ${patient.temp || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>CR/PR:</strong> ${patient.cr_pr || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>RR:</strong> ${patient.rr || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>Weight:</strong> ${patient.wt || 'N/A'}
                                        </div>
                                        <div class="summary-item">
                                            <strong>O2SAT:</strong> ${patient.o2sat || 'N/A'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        summaryContent.html(summaryHTML);
                    } else {
                        summaryContent.html('<p class="no-patient">No patient information available for this department.</p>');
                    }
                    $('#patient-form-modal').show();
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching patient data:', error);
                    alert('Failed to load patient information. Please try again.');
                }
            });
        });

        $('.close-modal').on('click', function() {
            $('#patient-form-modal').hide();
        });

        // Close modal when clicking outside
        $(window).on('click', function(event) {
            if (event.target == $('#patient-form-modal')[0]) {
                $('#patient-form-modal').hide();
            }
        });
    });
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
