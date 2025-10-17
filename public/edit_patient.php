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

$patient_model = new Patient($conn);
$patient = $patient_model->get_by_id($_GET['id']);

$department_model = new Department($conn);
$departments = $department_model->get_all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components/edit_patient.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Patient Information</h1>
            <div class="header-nav">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="../app/controllers/StaffController.php?action=logout">Logout</a>
            </div>
        </header>
        
        <main>
            <div class="form-section">
                <h2>Update Patient Details</h2>
                
                <form action="../app/controllers/PatientController.php" method="POST">
                    <input type="hidden" name="action" value="update_patient">
                    <input type="hidden" name="id" value="<?php echo $patient['id']; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>" required placeholder="Enter first name">
                        </div>

                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($patient['middle_name'] ?? ''); ?>" placeholder="Enter middle name (optional)">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>" required placeholder="Enter last name">
                        </div>
                        
                        <div class="form-group">
                            <label for="birthdate">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($patient['birthdate'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" value="<?php echo $patient['age']; ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($patient['contact_number']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" placeholder="Enter patient address"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">-- Select Gender --</option>
                                <option value="male" <?php echo ($patient['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($patient['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="preferred not to say" <?php echo ($patient['gender'] ?? '') == 'preferred not to say' ? 'selected' : ''; ?>>Preferred not to say</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="civil_status">Civil Status</label>
                            <select id="civil_status" name="civil_status">
                                <option value="">-- Select Civil Status --</option>
                                <option value="single" <?php echo ($patient['civil_status'] ?? '') == 'single' ? 'selected' : ''; ?>>Single</option>
                                <option value="married" <?php echo ($patient['civil_status'] ?? '') == 'married' ? 'selected' : ''; ?>>Married</option>
                                <option value="widow" <?php echo ($patient['civil_status'] ?? '') == 'widow' ? 'selected' : ''; ?>>Widow</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="registration_datetime">Registration Date & Time</label>
                            <input type="datetime-local" id="registration_datetime" name="registration_datetime" value="<?php echo htmlspecialchars($patient['registration_datetime'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="department_id">Department</label>
                            <select id="department_id" name="department_id" required>
                                <?php while ($row = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>" <?php if($row['id'] == $patient['department_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="reason_for_visit">Reason for Visit</label>
                            <select id="reason_for_visit" name="reason_for_visit" required>
                                <option value="">-- Select Reason --</option>
                                <option value="Check-up" <?php if($patient['reason_for_visit'] == 'Check-up') echo 'selected'; ?>>Check-up</option>
                                <option value="Follow-up" <?php if($patient['reason_for_visit'] == 'Follow-up') echo 'selected'; ?>>Follow-up</option>
                                <option value="Prescription" <?php if($patient['reason_for_visit'] == 'Prescription') echo 'selected'; ?>>Prescription</option>
                                <option value="Laboratory" <?php if($patient['reason_for_visit'] == 'Laboratory') echo 'selected'; ?>>Laboratory</option>
                                <option value="Vaccination" <?php if($patient['reason_for_visit'] == 'Vaccination') echo 'selected'; ?>>Vaccination</option>
                                <option value="Consultation" <?php if($patient['reason_for_visit'] == 'Consultation') echo 'selected'; ?>>Consultation</option>
                                <option value="Emergency / Urgent Care" <?php if($patient['reason_for_visit'] == 'Emergency / Urgent Care') echo 'selected'; ?>>Emergency / Urgent Care</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="parent_guardian">Parent/Guardian</label>
                            <input type="text" id="parent_guardian" name="parent_guardian" value="<?php echo htmlspecialchars($patient['parent_guardian'] ?? ''); ?>" placeholder="Enter parent or guardian name">
                        </div>

                        <div class="form-group">
                            <label for="doctor_id">Assign Doctor</label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">Loading doctors...</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label>Vital Signs</label>
                        </div>

                        <div class="form-group">
                            <label for="bp">BP (Blood Pressure)</label>
                            <input type="text" id="bp" name="bp" value="<?php echo htmlspecialchars($patient['bp'] ?? ''); ?>" placeholder="e.g., 120/80">
                        </div>

                        <div class="form-group">
                            <label for="temp">TEMP (Temperature)</label>
                            <input type="text" id="temp" name="temp" value="<?php echo htmlspecialchars($patient['temp'] ?? ''); ?>" placeholder="e.g., 36.5°C">
                        </div>

                        <div class="form-group">
                            <label for="cr_pr">CR/PR (Cardiac Rate/Pulse Rate)</label>
                            <input type="text" id="cr_pr" name="cr_pr" value="<?php echo htmlspecialchars($patient['cr_pr'] ?? ''); ?>" placeholder="e.g., 80 bpm">
                        </div>

                        <div class="form-group">
                            <label for="rr">RR (Respiratory Rate)</label>
                            <input type="text" id="rr" name="rr" value="<?php echo htmlspecialchars($patient['rr'] ?? ''); ?>" placeholder="e.g., 16 breaths/min">
                        </div>

                        <div class="form-group">
                            <label for="wt">WT (Weight)</label>
                            <input type="text" id="wt" name="wt" value="<?php echo htmlspecialchars($patient['wt'] ?? ''); ?>" placeholder="e.g., 70 kg">
                        </div>

                        <div class="form-group">
                            <label for="o2sat">O2SAT (Oxygen Saturation)</label>
                            <input type="text" id="o2sat" name="o2sat" value="<?php echo htmlspecialchars($patient['o2sat'] ?? ''); ?>" placeholder="e.g., 98%">
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-4">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Patient</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    window.addEventListener("pageshow", function(event) {
        if (event.persisted) {
            // If page was loaded from bfcache, force reload
            window.location.reload();
        }
    });
    </script>

    <script>
    $(document).ready(function() {
        var initialDepartmentId = $('#department_id').val();
        var initialDoctorId = <?php echo json_encode($patient['doctor_id']); ?>;

        function loadDoctors(departmentId, selectedDoctorId) {
            if (departmentId) {
                $.ajax({
                    url: 'get_doctors.php',
                    type: 'GET',
                    data: { department_id: departmentId },
                    dataType: 'json',
                    success: function(doctors) {
                        var doctorSelect = $('#doctor_id');
                        doctorSelect.empty().append('<option value="">-- Select Doctor --</option>');
                        $.each(doctors, function(key, doctor) {
                            var option = $('<option></option>').attr('value', doctor.id).text(doctor.name);
                            if (doctor.id == selectedDoctorId) {
                                option.attr('selected', 'selected');
                            }
                            doctorSelect.append(option);
                        });
                    },
                    error: function() {
                        alert('Failed to load doctors.');
                    }
                });
            } else {
                $('#doctor_id').empty().append('<option value="">-- Select Department First --</option>');
            }
        }

        // Load doctors on page load
        loadDoctors(initialDepartmentId, initialDoctorId);

        // Reload doctors when department changes
        $('#department_id').change(function() {
            var departmentId = $(this).val();
            loadDoctors(departmentId, null); // No doctor selected by default on change
        });

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
        });

        // Calculate age on page load if birthdate is set
        const initialBirthdate = $('#birthdate').val();
        if (initialBirthdate) {
            const initialAge = calculateAge(initialBirthdate);
            $('#age').val(initialAge);
            updateParentGuardianField(initialAge);
        }

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
    });
    </script>
</body>
</html>