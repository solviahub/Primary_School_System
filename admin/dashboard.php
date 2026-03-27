<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Dashboard';

// Get statistics
$stats = [];

// Total students
$query = "SELECT COUNT(*) as total FROM students WHERE status = 'active'";
$result = mysqli_query($conn, $query);
$stats['students'] = mysqli_fetch_assoc($result)['total'];

// Total teachers
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND status = 'active'";
$result = mysqli_query($conn, $query);
$stats['teachers'] = mysqli_fetch_assoc($result)['total'];

// Total classes
$query = "SELECT COUNT(*) as total FROM classes";
$result = mysqli_query($conn, $query);
$stats['classes'] = mysqli_fetch_assoc($result)['total'];

// Total books
$query = "SELECT SUM(total_copies) as total FROM books";
$result = mysqli_query($conn, $query);
$stats['books'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Today's attendance
$today = date('Y-m-d');
$query = "SELECT COUNT(*) as present FROM attendance WHERE date = '$today' AND status = 'present'";
$result = mysqli_query($conn, $query);
$present = mysqli_fetch_assoc($result)['present'];

$total_students = $stats['students'];
$attendance_rate = $total_students > 0 ? round(($present / $total_students) * 100) : 0;

// Get weekly attendance data for chart
$weekly_attendance = [];
for ($i = 3; $i >= 0; $i--) {
    $week_date = date('Y-m-d', strtotime("-$i weeks"));
    $week_start = date('Y-m-d', strtotime('monday this week', strtotime($week_date)));
    $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($week_date)));

    $query = "SELECT COUNT(DISTINCT student_id) as attended 
              FROM attendance 
              WHERE date BETWEEN '$week_start' AND '$week_end' 
              AND status = 'present'";
    $result = mysqli_query($conn, $query);
    $attended = mysqli_fetch_assoc($result)['attended'];

    $weekly_rate = $total_students > 0 ? round(($attended / $total_students) * 100) : 0;
    $weekly_attendance[] = $weekly_rate;
}

// Get recent announcements
$query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$announcements = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f7fb;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #eef2f6;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        /* Main Container */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.2rem 1.5rem;
        }

        /* Welcome & Stats Combined Card */
        .welcome-stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafcff 100%);
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        /* Welcome Section inside card */
        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eef2f8;
        }
        .welcome-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(120deg, #1e293b, #2d3e5f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }
        .welcome-section p {
            color: #64748b;
            font-size: 0.85rem;
            margin: 0;
        }
        .welcome-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(59,130,246,0.2);
        }

        /* Stats Grid - 4 boxes horizontal */
        .stats-grid-horizontal {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        
        .stat-card-compact {
            background: white;
            border-radius: 16px;
            padding: 0.8rem 1rem;
            transition: all 0.2s;
            border: 1px solid #eef2f8;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .stat-card-compact:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            border-color: #e2e8f0;
        }
        .stat-icon-compact {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-icon-compact.students { background: #eff6ff; color: #3b82f6; }
        .stat-icon-compact.teachers { background: #fef3c7; color: #f59e0b; }
        .stat-icon-compact.classes { background: #dcfce7; color: #10b981; }
        .stat-icon-compact.books { background: #fae8ff; color: #a855f7; }
        .stat-info-compact {
            flex: 1;
        }
        .stat-title-compact {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.5px;
        }
        .stat-value-compact {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-top: 0.2rem;
        }
        .stat-trend-compact {
            font-size: 0.65rem;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 0.25rem;
        }

        /* Two Column Layout */
        .two-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }

        /* Modern Cards */
        .modern-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #eef2f8;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .card-header {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .card-header i {
            font-size: 1.1rem;
            color: #3b82f6;
        }
        .card-header h6 {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0;
            color: #334155;
        }
        .card-body {
            padding: 1.2rem;
        }

        /* Chart Container */
        .chart-wrapper {
            position: relative;
            height: 260px;
            width: 100%;
        }

        /* Attendance Stats */
        .attendance-stats {
            text-align: center;
        }
        .attendance-percent {
            font-size: 2.5rem;
            font-weight: 800;
            color: #3b82f6;
            line-height: 1;
        }
        .progress-bar-custom {
            background: #e2e8f0;
            border-radius: 20px;
            height: 8px;
            overflow: hidden;
            margin: 1rem 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            height: 100%;
            border-radius: 20px;
            transition: width 0.6s ease;
        }
        .attendance-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            margin: 1rem 0;
        }
        .present-box, .absent-box {
            padding: 0.8rem;
            border-radius: 14px;
            text-align: center;
        }
        .present-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
        .absent-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
        .present-box h3, .absent-box h3 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0.25rem 0;
        }
        .present-box h3 { color: #10b981; }
        .absent-box h3 { color: #ef4444; }
        .present-box small, .absent-box small {
            font-size: 0.7rem;
            color: #64748b;
        }

        /* Announcements List */
        .announcement-list {
            max-height: 380px;
            overflow-y: auto;
        }
        .announcement-item {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        .announcement-item:hover {
            background: #fafcff;
        }
        .announcement-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        .announcement-content {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        .announcement-meta {
            display: flex;
            gap: 0.8rem;
            align-items: center;
        }
        .role-badge {
            background: #eff6ff;
            color: #3b82f6;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .date-badge {
            font-size: 0.65rem;
            color: #94a3b8;
        }

        /* Quick Actions Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.7rem;
        }
        .action-btn {
            background: #f8fafc;
            border: 1px solid #eef2f8;
            border-radius: 14px;
            padding: 0.7rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        .action-btn:hover {
            background: white;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            text-decoration: none;
        }
        .action-icon {
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #3b82f6;
        }
        .action-info strong {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1e293b;
            display: block;
        }
        .action-info small {
            font-size: 0.65rem;
            color: #94a3b8;
        }

        /* Footer */
        .dashboard-footer {
            margin-top: 1.5rem;
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #eef2f8;
            font-size: 0.7rem;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid-horizontal {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            .stats-grid-horizontal {
                grid-template-columns: 1fr;
            }
            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            .chart-wrapper {
                height: 220px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    
    <!-- Combined Welcome & Stats Card -->
    <div class="welcome-stats-card">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div>
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>! 👋</h2>
                <p>Here's what's happening in your school today</p>
            </div>
            <div class="welcome-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>
        
        <!-- Statistics Cards - 4 Boxes Horizontal -->
        <div class="stats-grid-horizontal">
            <div class="stat-card-compact">
                <div class="stat-icon-compact students">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info-compact">
                    <div class="stat-title-compact">Total Students</div>
                    <div class="stat-value-compact"><?php echo number_format($stats['students']); ?></div>
                    <div class="stat-trend-compact">
                        <i class="fas fa-arrow-up"></i> +12%
                    </div>
                </div>
            </div>
            
            <div class="stat-card-compact">
                <div class="stat-icon-compact teachers">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <div class="stat-info-compact">
                    <div class="stat-title-compact">Total Teachers</div>
                    <div class="stat-value-compact"><?php echo number_format($stats['teachers']); ?></div>
                    <div class="stat-trend-compact">
                        <i class="fas fa-arrow-up"></i> +5%
                    </div>
                </div>
            </div>
            
            <div class="stat-card-compact">
                <div class="stat-icon-compact classes">
                    <i class="fas fa-chalkboard"></i>
                </div>
                <div class="stat-info-compact">
                    <div class="stat-title-compact">Total Classes</div>
                    <div class="stat-value-compact"><?php echo number_format($stats['classes']); ?></div>
                    <div class="stat-trend-compact">
                        <i class="fas fa-plus-circle"></i> +2
                    </div>
                </div>
            </div>
            
            <div class="stat-card-compact">
                <div class="stat-icon-compact books">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info-compact">
                    <div class="stat-title-compact">Library Books</div>
                    <div class="stat-value-compact"><?php echo number_format($stats['books']); ?></div>
                    <div class="stat-trend-compact">
                        <i class="fas fa-plus"></i> +45
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="two-columns">
        <!-- Chart Card -->
        <div class="modern-card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i>
                <h6>Attendance Trend (Last 4 Weeks)</h6>
            </div>
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Today's Attendance Card -->
        <div class="modern-card">
            <div class="card-header">
                <i class="fas fa-calendar-check"></i>
                <h6>Today's Attendance</h6>
            </div>
            <div class="card-body attendance-stats">
                <div class="attendance-percent"><?php echo $attendance_rate; ?>%</div>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0.5rem;">Overall Attendance Rate</p>
                
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?php echo $attendance_rate; ?>%;"></div>
                </div>
                
                <div class="attendance-box">
                    <div class="present-box">
                        <i class="fas fa-user-check" style="color: #10b981;"></i>
                        <h3><?php echo $present; ?></h3>
                        <small>Present</small>
                    </div>
                    <div class="absent-box">
                        <i class="fas fa-user-clock" style="color: #ef4444;"></i>
                        <h3><?php echo $total_students - $present; ?></h3>
                        <small>Absent</small>
                    </div>
                </div>
                
                <a href="attendance_reports.php" style="display: inline-block; margin-top: 0.5rem; font-size: 0.8rem; color: #3b82f6; text-decoration: none; font-weight: 500;">
                    View Detailed Report <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Announcements & Quick Actions -->
    <div class="two-columns">
        <!-- Announcements Card -->
        <div class="modern-card">
            <div class="card-header">
                <i class="fas fa-bullhorn" style="color: #f59e0b;"></i>
                <h6>Recent Announcements</h6>
            </div>
            <div class="announcement-list">
                <?php if (mysqli_num_rows($announcements) > 0): ?>
                    <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
                        <div class="announcement-item">
                            <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            <div class="announcement-content">
                                <?php echo htmlspecialchars(substr($announcement['content'], 0, 90)); ?>
                                <?php if (strlen($announcement['content']) > 90): ?>...<?php endif; ?>
                            </div>
                            <div class="announcement-meta">
                                <span class="role-badge">
                                    <i class="fas fa-tag"></i> <?php echo ucfirst($announcement['target_role']); ?>
                                </span>
                                <span class="date-badge">
                                    <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-bullhorn fa-2x" style="color: #cbd5e1; margin-bottom: 0.5rem;"></i>
                        <p style="color: #94a3b8; font-size: 0.85rem;">No announcements yet</p>
                        <a href="announcements.php" style="font-size: 0.8rem; color: #3b82f6;">Create Announcement</a>
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding: 0.8rem 1rem; border-top: 1px solid #f1f5f9;">
                <a href="announcements.php" style="text-decoration: none; font-size: 0.8rem; font-weight: 500; color: #3b82f6;">
                    View all announcements <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="modern-card">
            <div class="card-header">
                <i class="fas fa-bolt" style="color: #f59e0b;"></i>
                <h6>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="actions-grid">
                    <a href="manage_users.php" class="action-btn">
                        <div class="action-icon"><i class="fas fa-users"></i></div>
                        <div class="action-info">
                            <strong>Manage Users</strong>
                            <small>Add, edit, remove</small>
                        </div>
                    </a>
                    <a href="manage_students.php" class="action-btn">
                        <div class="action-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="action-info">
                            <strong>Register Students</strong>
                            <small>New enrollments</small>
                        </div>
                    </a>
                    <a href="manage_classes.php" class="action-btn">
                        <div class="action-icon"><i class="fas fa-chalkboard"></i></div>
                        <div class="action-info">
                            <strong>Manage Classes</strong>
                            <small>Organize sections</small>
                        </div>
                    </a>
                    <a href="manage_subjects.php" class="action-btn">
                        <div class="action-icon"><i class="fas fa-book"></i></div>
                        <div class="action-info">
                            <strong>Manage Subjects</strong>
                            <small>Curriculum setup</small>
                        </div>
                    </a>
                    <a href="calendar.php" class="action-btn">
                        <div class="action-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="action-info">
                            <strong>School Calendar</strong>
                            <small>Events & holidays</small>
                        </div>
                    </a>
                    <a href="financial_reports.php" class="action-btn">
                        <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="action-info">
                            <strong>Financial Reports</strong>
                            <small>Payments & fees</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-footer">
        <i class="fas fa-shield-alt"></i> Secure Admin Dashboard · Real-time Updates
    </div>
</div>

<script>
    // Attendance Chart
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var weeklyData = <?php echo json_encode(array_reverse($weekly_attendance)); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Attendance Rate',
                    data: weeklyData,
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderColor: '#3b82f6',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + '% Attendance';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: '#eef2f6',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            stepSize: 25,
                            font: { size: 10 }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
?>