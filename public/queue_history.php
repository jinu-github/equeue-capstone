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
require_once '../app/models/QueueHistory.php';
require_once '../app/models/Department.php';

$queue_history_model = new QueueHistory($conn);
$department_model = new Department($conn);

// Get filter parameters
$department_id = $_GET['department'] ?? null;
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;

// Get grouped activities based on filters
if ($department_id) {
    $grouped_activities = $queue_history_model->get_grouped_activities($department_id);
} else {
    $grouped_activities = $queue_history_model->get_grouped_activities();
}

$departments = $department_model->get_all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue History - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --text-secondary: #6b7280;
            --border-color: #e2e8f0;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-color: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --primary: #60a5fa;
            --primary-hover: #3b82f6;
            --danger: #f87171;
            --danger-hover: #ef4444;
            --success: #34d399;
            --warning: #fbbf24;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: linear-gradient(135deg, var(--bg-color) 0%, rgba(59, 130, 246, 0.05) 100%);
            min-height: 100vh;
        }

        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 0;
            margin-bottom: 0.5rem;
            box-shadow: 0 8px 32px var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            padding-top: 1rem;
        }

        main {
            padding: 1rem;
        }

        header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .header-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .header-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px var(--shadow);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-hover) 100%);
            color: white;
            border: none;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        .table-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-bg);
        }

        thead {
            background-color: rgba(0, 0, 0, 0.05);
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
    </style>
</head>
<body>
    <header>
        <h1>Queue History</h1>
        <div class="header-nav">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="../app/controllers/StaffController.php?action=logout">Logout</a>
        </div>
    </header>
    <div class="container">
        <?php if ($department_id): ?>
            <?php
            // Get department name for display
            $departments->data_seek(0); // Reset pointer
            $selected_dept_name = '';
            while ($dept = $departments->fetch_assoc()) {
                if ($dept['id'] == $department_id) {
                    $selected_dept_name = $dept['name'];
                    break;
                }
            }
            ?>
            <div style="text-align: center; padding: 0.5rem 0; background: rgba(59, 130, 246, 0.1); border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(59, 130, 246, 0.2);">
                <h2 style="color: var(--primary); margin: 0; font-size: 1.2rem; font-weight: 500;">
                    <?php echo htmlspecialchars($selected_dept_name); ?> Department
                </h2>
            </div>
        <?php endif; ?>
        <main>
            <!-- Filters Section -->
            <div class="form-section">
                <h2>Filter Activities</h2>
                <form method="GET" action="queue_history.php" class="filter-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">All Departments</option>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($department_id == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="queue_history.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>

            <!-- Activities Table -->
            <div class="table-section">
                <div class="table-header" style="padding: 1rem 2rem; margin-bottom: 1rem;">
                    <h2 style="color: white; font-weight: bold; border-bottom: none; padding-bottom: 0;">All Queue History</h2>
                </div>

                <div class="table-actions" style="padding: 0 1rem; margin-bottom: 1rem;">
                    <button id="delete-selected" class="btn btn-danger" disabled>Remove Selected</button>
                    <button id="clear-all" class="btn btn-danger">Clear All History</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>Patient</th>
                            <th>Check-in Time</th>
                            <th>Department</th>
                            <th>Doctor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_activities = false;
                        foreach ($grouped_activities as $patient_group):
                            $has_activities = true;
                            $patient_id = $patient_group['patient_id'];
                            $patient_name = htmlspecialchars($patient_group['patient_name'] ?? 'N/A');
                            $check_in_time = $patient_group['patient_check_in_time'] ? date('g:i A', strtotime($patient_group['patient_check_in_time'])) : 'N/A';
                            $department_name = htmlspecialchars($patient_group['department_name']);
                            $doctor_name = htmlspecialchars($patient_group['doctor_name'] ?? 'Not Assigned');
                            $actions = $patient_group['actions'];
                        ?>
                            <tr>
                                <td><input type="checkbox" class="patient-checkbox" value="<?php echo $patient_id; ?>"></td>
                                <td><strong><?php echo $patient_name; ?></strong></td>
                                <td><?php echo $check_in_time; ?></td>
                                <td><?php echo $department_name; ?></td>
                                <td><?php echo $doctor_name; ?></td>
                                <td>
                                    <details class="action-details">
                                        <summary class="action-summary">View Actions (<?php echo count($actions); ?>)</summary>
                                        <div class="action-list">
                                            <?php foreach ($actions as $action): ?>
                                                <div class="action-item">
                                                    <span class="action-time"><?php echo date('M j, Y g:i A', strtotime($action['created_at'])); ?></span>
                                                    <span class="action-badge <?php
                                                        $action_display = '';
                                                        $action_class = '';
                                                        if ($action['action'] == 'registered') {
                                                            $action_display = 'Registered';
                                                            $action_class = 'action-registered';
                                                        } elseif ($action['action'] == 'status_changed') {
                                                            if ($action['new_status'] == 'in consultation') {
                                                                $action_display = 'Started';
                                                                $action_class = 'action-started';
                                                            } elseif ($action['new_status'] == 'done') {
                                                                $action_display = 'Completed';
                                                                $action_class = 'action-completed';
                                                            } elseif ($action['new_status'] == 'cancelled') {
                                                                $action_display = 'Cancelled';
                                                                $action_class = 'action-cancelled';
                                                            } elseif ($action['new_status'] == 'no show') {
                                                                $action_display = 'No Show';
                                                                $action_class = 'action-no-show';
                                                            } else {
                                                                $action_display = 'Status Changed';
                                                                $action_class = 'action-status-changed';
                                                            }
                                                        } elseif ($action['action'] == 'removed') {
                                                            $action_display = 'Cancelled';
                                                            $action_class = 'action-cancelled';
                                                        } elseif ($action['action'] == 'requeued') {
                                                            $action_display = 'Requeued';
                                                            $action_class = 'action-requeued';
                                                        } else {
                                                            $action_display = ucwords(str_replace('_', ' ', $action['action']));
                                                            $action_class = 'action-' . strtolower(str_replace(' ', '-', $action['action']));
                                                        }
                                                        echo $action_class;
                                                    ?>">
                                                        <?php echo htmlspecialchars($action_display); ?>
                                                    </span>
                                                    <span class="action-dept"><?php echo htmlspecialchars($action['department_name']); ?></span>
                                                    <?php if (($action['action'] == 'status_changed' && ($action['new_status'] == 'no show' || $action['new_status'] == 'cancelled')) || $action['action'] == 'removed'): ?>
                                                        <button class="btn btn-primary btn-sm requeue-btn" data-patient-id="<?php echo $patient_id; ?>" data-action-id="<?php echo $action['id']; ?>">
                                                            Requeue
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$has_activities): ?>
                            <tr>
                                <td colspan="6" class="text-center" style="color: #6b7280; padding: 2rem;">
                                    No queue history found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <style>
        .action-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .action-registered { background-color: #d1fae5; color: #065f46; }
        .action-started { background-color: #dbeafe; color: #1e40af; }
        .action-completed { background-color: #d1fae5; color: #065f46; }
        .action-cancelled { background-color: #fee2e2; color: #991b1b; }
        .action-deleted { background-color: #fee2e2; color: #991b1b; }
        .action-status-changed { background-color: #fef3c7; color: #92400e; }
        .action-no-show { background-color: #fef3c7; color: #92400e; }
        .action-requeued { background-color: #dbeafe; color: #1e40af; }

        .action-details {
            cursor: pointer;
        }

        .action-summary {
            font-weight: 500;
            color: var(--primary);
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .action-summary:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }

        .action-list {
            margin-top: 1rem;
            padding: 1rem;
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .action-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .action-item:last-child {
            border-bottom: none;
        }

        .action-time {
            font-size: 0.875rem;
            color: var(--text-secondary);
            min-width: 120px;
        }

        .action-dept {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 4px;
        }

        .form-section {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
            border: 1px solid var(--border-color);
            animation: slideIn 0.6s ease-out;
        }

        .filter-form {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-group select,
        .form-group input {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-color);
            font-size: 0.875rem;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .items-center {
            align-items: center;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const selectAll = document.getElementById('select-all');
                        const checkboxes = document.querySelectorAll('.patient-checkbox');
                        const deleteSelectedBtn = document.getElementById('delete-selected');
                        const clearAllBtn = document.getElementById('clear-all');

                        // Select all functionality
                        selectAll.addEventListener('change', function() {
                            checkboxes.forEach(cb => cb.checked = this.checked);
                            deleteSelectedBtn.disabled = !this.checked;
                        });

                        // Individual checkbox change
                        checkboxes.forEach(cb => {
                            cb.addEventListener('change', function() {
                                const checkedCount = document.querySelectorAll('.patient-checkbox:checked').length;
                                selectAll.checked = checkedCount === checkboxes.length;
                                deleteSelectedBtn.disabled = checkedCount === 0;
                            });
                        });

                        // Delete selected - Note: This now deletes entire patient history groups
                        deleteSelectedBtn.addEventListener('click', function() {
                            const selectedIds = Array.from(checkboxes)
                                .filter(cb => cb.checked)
                                .map(cb => cb.value)
                                .join(',');

                            if (selectedIds && confirm('Are you sure you want to delete the selected patient history groups? This will remove all history entries for these patients.')) {
                                fetch('../app/controllers/PatientController.php?action=delete_history&ids=' + selectedIds, {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert('Failed to delete selected history.');
                                    }
                                })
                                .catch(error => {
                                    alert('An error occurred while deleting.');
                                });
                            }
                        });

                        // Clear all
                        clearAllBtn.addEventListener('click', function() {
                            if (confirm('Are you sure you want to clear all history? This action cannot be undone.')) {
                                fetch('../app/controllers/PatientController.php?action=clear_history', {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert('Failed to clear history.');
                                    }
                                })
                                .catch(error => {
                                    alert('An error occurred while clearing history.');
                                });
                            }
                        });

                        // Requeue functionality
                        document.addEventListener('click', function(e) {
                            if (e.target.classList.contains('requeue-btn')) {
                                const patientId = e.target.getAttribute('data-patient-id');
                                if (confirm('Are you sure you want to requeue this patient?')) {
                                    fetch('../app/controllers/PatientController.php?action=requeue_patient&patient_id=' + patientId, {
                                        method: 'GET',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert('Patient requeued successfully with queue number: ' + data.queue_number);
                                            location.reload();
                                        } else {
                                            alert('Failed to requeue patient: ' + data.message);
                                        }
                                    })
                                    .catch(error => {
                                        alert('An error occurred while requeuing the patient.');
                                    });
                                }
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
