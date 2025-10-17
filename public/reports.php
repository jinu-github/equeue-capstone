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

$report_model = new Report($conn);
$department_model = new Department($conn);
$patient_model = new Patient($conn);
$departments = $department_model->get_all();

// Get staff's department
$staff_department_id = $_SESSION['department_id'] ?? null;

// Get filter parameters
$period = $_GET['period'] ?? 'day';
$date = $_GET['date'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - eQueue</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            width: 100%;
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

        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
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

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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

        .report-section {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
            border: 1px solid var(--border-color);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px var(--shadow);
            text-align: center;
        }

        .chart-container h3 {
            margin-bottom: 1rem;
            color: var(--text-color);
            font-size: 1.125rem;
            font-weight: 600;
        }

        .chart-container canvas {
            max-width: 100%;
            height: auto !important;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-waiting {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-in-consultation {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-done {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        textarea, select, input {
            background: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        textarea:focus, select:focus, input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            header h1 {
                font-size: 1.5rem;
            }

            .header-nav {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }

            .header-nav a {
                padding: 0.5rem;
                text-align: center;
            }

            .form-section {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .table-responsive {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }

            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .status-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-chart-bar"></i> Reports</h1>
        <div class="header-nav">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="../app/controllers/StaffController.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    <div class="container">
        <main>
            <!-- Filters -->
            <div class="form-section">
                <h2>Filter Reports</h2>
                <form method="GET" action="reports.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="period">Period</label>
                            <select id="period" name="period">
                                <option value="day" <?php echo $period == 'day' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>This Month</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date">Date (for Daily Summary)</label>
                            <input type="date" id="date" name="date" value="<?php echo $date; ?>">
                        </div>
                    </div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
            <form method="POST" action="export_report.php" style="margin-top: 1rem;">
                <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                <label for="export_format">Export Format:</label>
                <select id="export_format" name="export_format" required>
                    <option value="pdf">PDF</option>
                    <option value="doc">Document (DOCX)</option>
                </select>
                <button type="submit" class="btn btn-secondary">Export Report</button>
            </form>
        </div>

            

            <!-- Charts Section -->
            <div class="report-section">
                <h2>Visual Analytics</h2>
                <div class="charts-grid">
                    <!-- Patient Status Distribution -->
                    <div class="chart-container">
                        <h3>Patient Status Distribution</h3>
                        <canvas id="statusChart" width="300" height="200"></canvas>
                    </div>

                    <!-- Daily Patient Trends -->
                    <div class="chart-container">
                        <h3>Daily Patient Trends</h3>
                        <canvas id="trendsChart" width="300" height="200"></canvas>
                    </div>

                    <!-- Hourly Distribution -->
                    <div class="chart-container">
                        <h3>Hourly Patient Distribution</h3>
                        <canvas id="hourlyChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Patient Details Report -->
            <div class="report-section">
                <h2>Patient Details Report</h2>
                <h3>Patients for <?php echo ucfirst($period); ?> (<?php echo $period == 'day' ? date('M d, Y') : ($period == 'week' ? 'Last 7 days' : 'Last 30 days'); ?>)</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Contact</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Reason for Visit</th>
                                <th>Queue Number</th>
                                <th>Check-in Time</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $patients = $report_model->get_patients_for_period($period, $staff_department_id);
                            $has_patients = false;
                            foreach ($patients as $patient):
                                $has_patients = true;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient_model->combineNames($patient['first_name'], $patient['middle_name'], $patient['last_name'])); ?></td>
                                    <td><?php echo $patient['age']; ?></td>
                                    <td><?php echo htmlspecialchars($patient['contact_number']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($patient['doctor_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo htmlspecialchars($patient['reason_for_visit'] ?? 'N/A'); ?></td>
                                    <td><?php echo $patient['queue_number']; ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($patient['check_in_time'])); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($patient['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$has_patients): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No patients found for the selected period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', function() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        });

        function updateThemeIcon() {
            const themeToggle = document.getElementById('theme-toggle');
            const currentTheme = document.body.getAttribute('data-theme');
            themeToggle.innerHTML = currentTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        updateThemeIcon();

        // Chart data from PHP
        const period = '<?php echo $period; ?>';
        const statusData = <?php echo json_encode($report_model->get_patient_status_distribution($period, $staff_department_id)); ?>;
        const trendsData = <?php echo json_encode($report_model->get_daily_patient_trends($period, $staff_department_id)); ?>;
        const hourlyData = <?php echo json_encode($report_model->get_hourly_patient_distribution($period, $staff_department_id)); ?>;

        // Status Distribution Pie Chart
        const statusLabels = statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
        const statusCounts = statusData.map(item => item.count);
        const statusColors = {
            'Waiting': '#fbbf24',
            'In-consultation': '#3b82f6',
            'Done': '#10b981',
            'Cancelled': '#ef4444'
        };
        const statusChartColors = statusLabels.map(label => statusColors[label] || '#6b7280');

        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: statusChartColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.parsed / total) * 100);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Daily Trends Line Chart
        const trendsLabels = trendsData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const trendsCounts = trendsData.map(item => item.count);

        new Chart(document.getElementById('trendsChart'), {
            type: 'line',
            data: {
                labels: trendsLabels,
                datasets: [{
                    label: 'Patients',
                    data: trendsCounts,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Hourly Distribution Bar Chart
        const hourlyLabels = Array.from({length: 24}, (_, i) => {
            const hour = i % 12 || 12;
            const ampm = i < 12 ? 'AM' : 'PM';
            return hour + ' ' + ampm;
        });
        const hourlyCounts = Array(24).fill(0);
        hourlyData.forEach(item => {
            hourlyCounts[item.hour] = item.count;
        });

        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: hourlyLabels,
                datasets: [{
                    label: 'Patients',
                    data: hourlyCounts,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
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
