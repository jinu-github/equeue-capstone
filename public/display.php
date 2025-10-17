<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eQueue - Live Queue Display</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="css/components/display.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🏥 Live Queue Status</h1>
            <div class="header-nav">
                <span id="last-updated">Loading...</span>
            </div>
        </header>
        
        <main>
            <div id="queue-display" class="queue-grid">
                <div class="loading">
                    <div class="spinner"></div>
                    Loading queue data...
                </div>
            </div>

            <!-- Demo controls for testing -->
            <div class="controls" style="display: none;" id="demo-controls">
                <h3 style="color: white; margin-bottom: 20px;">Demo Controls (for testing)</h3>
                <button class="btn" onclick="simulateQueueUpdate('emergency')">Update Emergency</button>
                <button class="btn" onclick="simulateQueueUpdate('cardiology')">Update Cardiology</button>
                <button class="btn" onclick="simulateQueueUpdate('orthopedics')">Update Orthopedics</button>
                <button class="btn" onclick="simulateQueueUpdate('neurology')">Update Neurology</button>
                <button class="btn" onclick="simulateQueueUpdate('pediatrics')">Update Pediatrics</button>
                <button class="btn" onclick="simulateQueueUpdate('general')">Update General</button>
                <button class="btn" style="background: #e74c3c;" onclick="toggleDemoMode()">Hide Demo Controls</button>
            </div>
        </main>
    </div>

    <!-- Notification container -->
    <div id="notification" class="notification"></div>

    <script>
        // Demo queue data for fallback
        let demoQueueData = {
            emergency: { letter: 'A', number: 23, status: 'active' },
            cardiology: { letter: 'B', number: 15, status: 'waiting' },
            orthopedics: { letter: 'C', number: 8, status: 'active' },
            neurology: { letter: 'D', number: 12, status: 'waiting' },
            pediatrics: { letter: 'E', number: 7, status: 'active' },
            general: { letter: 'F', number: 31, status: 'waiting' }
        };

        let demoMode = false;

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function generateDemoQueueHTML() {
            const departments = [
                { id: 'emergency', name: 'Emergency', class: 'emergency' },
                { id: 'cardiology', name: 'Cardiology', class: 'cardiology' },
                { id: 'orthopedics', name: 'Orthopedics', class: 'orthopedics' },
                { id: 'neurology', name: 'Neurology', class: 'neurology' },
                { id: 'pediatrics', name: 'Pediatrics', class: 'pediatrics' },
                { id: 'general', name: 'General Medicine', class: 'general' }
            ];

            let html = '';
            departments.forEach(dept => {
                const queue = demoQueueData[dept.id];
                const queueNumber = `${queue.letter}-${queue.number.toString().padStart(3, '0')}`;
                const statusClass = queue.status;
                const statusText = queue.status.charAt(0).toUpperCase() + queue.status.slice(1);

                html += `
                    <div class="department-card ${dept.class}" id="${dept.id}-card">
                        <div class="department-name">${dept.name}</div>
                        <div class="queue-label">Now Serving</div>
                        <div class="queue-number" id="${dept.id}-queue">${queueNumber}</div>
                        <div class="status ${statusClass}">${statusText}</div>
                    </div>
                `;
            });

            return html;
        }

        function simulateQueueUpdate(department) {
            const queue = demoQueueData[department];
            queue.number += 1;
            
            const queueNumber = `${queue.letter}-${queue.number.toString().padStart(3, '0')}`;
            const queueElement = document.getElementById(department + '-queue');
            
            if (queueElement) {
                // Animation effect
                queueElement.style.transform = 'scale(1.1)';
                queueElement.style.color = 'var(--danger)';
                queueElement.textContent = queueNumber;

                setTimeout(() => {
                    queueElement.style.transform = 'scale(1)';
                    queueElement.style.color = 'var(--text-color)';
                }, 300);

                // Update status randomly
                const statusElement = queueElement.parentElement.querySelector('.status');
                const statuses = ['active', 'waiting', 'called'];
                const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
                queue.status = randomStatus;
                
                statusElement.className = `status ${randomStatus}`;
                statusElement.textContent = randomStatus.charAt(0).toUpperCase() + randomStatus.slice(1);

                // Add pulse effect to card
                const card = document.getElementById(department + '-card');
                card.classList.add('pulse');
                setTimeout(() => card.classList.remove('pulse'), 2000);

                showNotification(`${department.charAt(0).toUpperCase() + department.slice(1)} queue updated to ${queueNumber}`);
            }
        }

        function toggleDemoMode() {
            demoMode = !demoMode;
            const controls = document.getElementById('demo-controls');
            controls.style.display = demoMode ? 'block' : 'none';
            
            if (demoMode) {
                $('#queue-display').html(generateDemoQueueHTML());
                showNotification('Demo mode activated - you can now test queue updates');
            } else {
                loadQueue();
                showNotification('Demo mode deactivated - returning to live data');
            }
        }

        $(document).ready(function() {
            function loadQueue() {
                if (demoMode) return; // Don't load real data in demo mode

                $.ajax({
                    url: 'get_queue_data.php',
                    type: 'GET',
                    timeout: 10000,
                    success: function(data) {
                        if (data && data.trim() !== '') {
                            $('#queue-display').html(data);
                            $('#last-updated').text('Last updated: ' + new Date().toLocaleTimeString());
                        } else {
                            // Fallback to demo data if no real data
                            $('#queue-display').html(generateDemoQueueHTML());
                            $('#last-updated').text('Demo mode - ' + new Date().toLocaleTimeString());
                            $('#demo-controls').show();
                            demoMode = true;
                        }
                    },
                    error: function() {
                        // Fallback to demo data on error
                        $('#queue-display').html(generateDemoQueueHTML());
                        $('#last-updated').text('Demo mode - ' + new Date().toLocaleTimeString());
                        $('#demo-controls').show();
                        demoMode = true;
                        
                        showNotification('Unable to connect to server. Running in demo mode.', 'error');
                    }
                });
            }

            // Initial load
            loadQueue();
            
            // Refresh every 5 seconds (only if not in demo mode)
            setInterval(function() {
                if (!demoMode) {
                    loadQueue();
                }
            }, 5000);

            // Auto-demo updates when in demo mode
            setInterval(function() {
                if (demoMode && Math.random() > 0.7) {
                    const departments = Object.keys(demoQueueData);
                    const randomDept = departments[Math.floor(Math.random() * departments.length)];
                    simulateQueueUpdate(randomDept);
                }
            }, 8000);

            // Double-click to toggle demo mode
            $(document).dblclick(function() {
                toggleDemoMode();
            });
        });
    </script>
</body>
</html>