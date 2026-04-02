<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['student']);

$page_title = 'My Attendance';
$user_id = $_SESSION['user_id'];

// Get student details
$student_query = "SELECT s.*, u.full_name, c.class_name, c.id as class_id 
                  FROM students s 
                  JOIN users u ON s.user_id = u.id 
                  JOIN classes c ON s.class_id = c.id 
                  WHERE s.user_id = $user_id";
$student = mysqli_fetch_assoc(mysqli_query($conn, $student_query));

if (!$student) {
  $_SESSION['message'] = 'Student record not found!';
  $_SESSION['message_type'] = 'danger';
  redirect('dashboard.php');
}

$student_id = $student['id'];
$class_id = $student['class_id'];

// Get date filters
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$term = isset($_GET['term']) ? $_GET['term'] : 'all';

// Get attendance records for the student
$attendance_query = "SELECT a.*, u.full_name as marked_by_name 
                     FROM attendance a 
                     LEFT JOIN users u ON a.marked_by = u.id 
                     WHERE a.student_id = $student_id 
                     AND MONTH(a.date) = $month 
                     AND YEAR(a.date) = $year";

if ($term != 'all') {
  // Add term filtering logic
  if ($term == 'first') {
    $attendance_query .= " AND MONTH(a.date) IN (1, 2, 3, 4)";
  } elseif ($term == 'second') {
    $attendance_query .= " AND MONTH(a.date) IN (5, 6, 7, 8)";
  } elseif ($term == 'third') {
    $attendance_query .= " AND MONTH(a.date) IN (9, 10, 11, 12)";
  }
}

$attendance_query .= " ORDER BY a.date DESC";
$attendance_records = mysqli_query($conn, $attendance_query);

// Calculate attendance statistics
$stats_query = "SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_days
                FROM attendance 
                WHERE student_id = $student_id 
                AND YEAR(date) = $year";

if ($term != 'all') {
  if ($term == 'first') {
    $stats_query .= " AND MONTH(date) IN (1, 2, 3, 4)";
  } elseif ($term == 'second') {
    $stats_query .= " AND MONTH(date) IN (5, 6, 7, 8)";
  } elseif ($term == 'third') {
    $stats_query .= " AND MONTH(date) IN (9, 10, 11, 12)";
  }
}

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$total_days = $stats['total_days'] ?? 0;
$present_days = $stats['present_days'] ?? 0;
$absent_days = $stats['absent_days'] ?? 0;
$late_days = $stats['late_days'] ?? 0;
$excused_days = $stats['excused_days'] ?? 0;

$attendance_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100, 2) : 0;

// Get monthly attendance data for chart
$monthly_data = [];
for ($m = 1; $m <= 12; $m++) {
  $monthly_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                      FROM attendance 
                      WHERE student_id = $student_id 
                      AND MONTH(date) = $m 
                      AND YEAR(date) = $year";
  $monthly_result = mysqli_query($conn, $monthly_query);
  $monthly_stats = mysqli_fetch_assoc($monthly_result);

  $monthly_data[$m] = [
    'total' => $monthly_stats['total'] ?? 0,
    'present' => $monthly_stats['present'] ?? 0,
    'percentage' => ($monthly_stats['total'] ?? 0) > 0 ? round(($monthly_stats['present'] / $monthly_stats['total']) * 100, 2) : 0
  ];
}

include '../includes/header.php';
?>

<style>
  .attendance-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
  }

  .attendance-stat {
    text-align: center;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
  }

  .attendance-stat.present {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
  }

  .attendance-stat.absent {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
  }

  .attendance-stat.late {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
  }

  .attendance-stat.excused {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
  }

  .attendance-stat .stat-value {
    font-size: 2rem;
    font-weight: bold;
  }

  .attendance-stat .stat-label {
    font-size: 0.8rem;
    opacity: 0.9;
  }

  .calendar-day {
    padding: 10px;
    text-align: center;
    border-radius: 8px;
    transition: all 0.3s ease;
  }

  .calendar-day:hover {
    transform: scale(1.05);
  }

  .calendar-day.present {
    background-color: #10b981;
    color: white;
  }

  .calendar-day.absent {
    background-color: #ef4444;
    color: white;
  }

  .calendar-day.late {
    background-color: #f59e0b;
    color: white;
  }

  .calendar-day.excused {
    background-color: #8b5cf6;
    color: white;
  }

  .calendar-day.no-data {
    background-color: #e5e7eb;
    color: #6c757d;
  }

  .legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 1rem;
    margin-bottom: 0.5rem;
  }

  .legend-color {
    width: 15px;
    height: 15px;
    border-radius: 3px;
    margin-right: 5px;
  }
</style>

<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 15px; padding: 2rem;">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="text-white">My Attendance Record</h2>
          <p class="text-white-50 mb-0">Track your attendance history and performance.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-calendar-check" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Student Info Card -->
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-3">
            <div class="text-center">
              <i class="fas fa-user-graduate fa-3x" style="color: #3b82f6;"></i>
              <h5 class="mt-2 mb-0"><?php echo $student['full_name']; ?></h5>
              <small class="text-muted"><?php echo $student['admission_number']; ?></small>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <small class="text-muted">Class</small>
              <h6 class="mb-0"><?php echo $student['class_name']; ?></h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <small class="text-muted">Academic Year</small>
              <h6 class="mb-0"><?php echo getCurrentAcademicYear(); ?></h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <small class="text-muted">Overall Attendance</small>
              <h6 class="mb-0 text-primary fw-bold"><?php echo $attendance_percentage; ?>%</h6>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <form method="GET" action="" class="row g-3">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Select Year</label>
            <select class="form-select" name="year" onchange="this.form.submit()">
              <?php
              $current_year = date('Y');
              for ($y = $current_year; $y >= $current_year - 5; $y--):
              ?>
                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                  <?php echo $y; ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Select Month</label>
            <select class="form-select" name="month" onchange="this.form.submit()">
              <option value="1" <?php echo $month == 1 ? 'selected' : ''; ?>>January</option>
              <option value="2" <?php echo $month == 2 ? 'selected' : ''; ?>>February</option>
              <option value="3" <?php echo $month == 3 ? 'selected' : ''; ?>>March</option>
              <option value="4" <?php echo $month == 4 ? 'selected' : ''; ?>>April</option>
              <option value="5" <?php echo $month == 5 ? 'selected' : ''; ?>>May</option>
              <option value="6" <?php echo $month == 6 ? 'selected' : ''; ?>>June</option>
              <option value="7" <?php echo $month == 7 ? 'selected' : ''; ?>>July</option>
              <option value="8" <?php echo $month == 8 ? 'selected' : ''; ?>>August</option>
              <option value="9" <?php echo $month == 9 ? 'selected' : ''; ?>>September</option>
              <option value="10" <?php echo $month == 10 ? 'selected' : ''; ?>>October</option>
              <option value="11" <?php echo $month == 11 ? 'selected' : ''; ?>>November</option>
              <option value="12" <?php echo $month == 12 ? 'selected' : ''; ?>>December</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Select Term</label>
            <select class="form-select" name="term" onchange="this.form.submit()">
              <option value="all" <?php echo $term == 'all' ? 'selected' : ''; ?>>All Terms</option>
              <option value="first" <?php echo $term == 'first' ? 'selected' : ''; ?>>First Term (Jan-Apr)</option>
              <option value="second" <?php echo $term == 'second' ? 'selected' : ''; ?>>Second Term (May-Aug)</option>
              <option value="third" <?php echo $term == 'third' ? 'selected' : ''; ?>>Third Term (Sep-Dec)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">&nbsp;</label>
            <a href="view_attendance.php" class="btn btn-secondary d-block">
              <i class="fas fa-sync-alt me-2"></i>Reset Filters
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="attendance-stat present">
      <div class="stat-value"><?php echo $present_days; ?></div>
      <div class="stat-label">Present Days</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="attendance-stat absent">
      <div class="stat-value"><?php echo $absent_days; ?></div>
      <div class="stat-label">Absent Days</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="attendance-stat late">
      <div class="stat-value"><?php echo $late_days; ?></div>
      <div class="stat-label">Late Days</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="attendance-stat excused">
      <div class="stat-value"><?php echo $excused_days; ?></div>
      <div class="stat-label">Excused Days</div>
    </div>
  </div>
</div>

<!-- Overall Progress -->
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chart-line me-2"></i>Overall Attendance Progress
        </h6>
      </div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-3 text-center">
            <div class="position-relative">
              <canvas id="attendanceDonutChart" width="150" height="150"></canvas>
              <div class="position-absolute top-50 start-50 translate-middle text-center">
                <h4 class="mb-0 fw-bold"><?php echo $attendance_percentage; ?>%</h4>
                <small class="text-muted">Attendance</small>
              </div>
            </div>
          </div>
          <div class="col-md-9">
            <div class="progress mb-3" style="height: 30px;">
              <div class="progress-bar bg-success" style="width: <?php echo $attendance_percentage; ?>%">
                Present: <?php echo $attendance_percentage; ?>%
              </div>
            </div>
            <div class="row text-center">
              <div class="col-3">
                <small class="text-muted">Target: 75%</small>
              </div>
              <div class="col-3">
                <small class="text-muted">Required: <?php echo $total_days > 0 ? ceil($total_days * 0.75) - $present_days : 0; ?> more days</small>
              </div>
              <div class="col-3">
                <small class="text-muted">
                  <?php
                  if ($attendance_percentage >= 75) {
                    echo '<span class="text-success">✓ Meeting requirement</span>';
                  } else {
                    echo '<span class="text-warning">⚠ Below 75%</span>';
                  }
                  ?>
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Monthly Attendance Chart -->
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chart-bar me-2"></i>Monthly Attendance Report - <?php echo $year; ?>
        </h6>
      </div>
      <div class="card-body">
        <canvas id="monthlyAttendanceChart" height="100"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Calendar View -->
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-calendar-alt me-2"></i>
          Attendance Calendar - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?>
        </h6>
      </div>
      <div class="card-body">
        <?php
        // Get days in month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $first_day = date('N', strtotime("$year-$month-01"));

        // Create attendance map
        $attendance_map = [];
        while ($record = mysqli_fetch_assoc($attendance_records)) {
          $day = date('j', strtotime($record['date']));
          $attendance_map[$day] = $record['status'];
        }
        ?>

        <div class="legend mb-3">
          <span class="legend-item"><span class="legend-color" style="background: #10b981;"></span> Present</span>
          <span class="legend-item"><span class="legend-color" style="background: #ef4444;"></span> Absent</span>
          <span class="legend-item"><span class="legend-color" style="background: #f59e0b;"></span> Late</span>
          <span class="legend-item"><span class="legend-color" style="background: #8b5cf6;"></span> Excused</span>
          <span class="legend-item"><span class="legend-color" style="background: #e5e7eb;"></span> No Data</span>
        </div>

        <div class="calendar-grid">
          <div class="row g-2">
            <?php
            // Day headers
            $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            foreach ($weekdays as $weekday) {
              echo '<div class="col"><div class="text-center fw-bold py-2" style="background: #f8f9fa; border-radius: 8px;">' . $weekday . '</div></div>';
            }

            // Empty cells before first day
            for ($i = 1; $i < $first_day; $i++) {
              echo '<div class="col"><div class="calendar-day no-data text-center py-3"></div></div>';
            }

            // Calendar days
            for ($day = 1; $day <= $days_in_month; $day++) {
              $status = isset($attendance_map[$day]) ? $attendance_map[$day] : 'no-data';
              $status_class = $status;
              $status_text = $status == 'present' ? 'P' : ($status == 'absent' ? 'A' : ($status == 'late' ? 'L' : ($status == 'excused' ? 'E' : '')));

              echo '<div class="col">';
              echo '<div class="calendar-day ' . $status_class . ' text-center py-3">';
              echo '<strong>' . $day . '</strong>';
              if ($status_text) {
                echo '<br><small>' . $status_text . '</small>';
              }
              echo '</div>';
              echo '</div>';

              // New row after Sunday
              if (($first_day + $day - 1) % 7 == 0) {
                echo '</div><div class="row g-2 mt-2">';
              }
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Detailed Attendance Table -->
<div class="row">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-list me-2"></i>Detailed Attendance Records
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover datatable mb-0">
            <thead style="background-color: #3b82f6; color: white;">
              <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Marked By</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Reset pointer
              mysqli_data_seek($attendance_records, 0);
              if (mysqli_num_rows($attendance_records) > 0):
                while ($record = mysqli_fetch_assoc($attendance_records)):
                  $status_class = '';
                  $status_icon = '';
                  switch ($record['status']) {
                    case 'present':
                      $status_class = 'success';
                      $status_icon = 'check-circle';
                      break;
                    case 'absent':
                      $status_class = 'danger';
                      $status_icon = 'times-circle';
                      break;
                    case 'late':
                      $status_class = 'warning';
                      $status_icon = 'clock';
                      break;
                    case 'excused':
                      $status_class = 'info';
                      $status_icon = 'envelope';
                      break;
                  }
              ?>
                  <tr>
                    <td><?php echo date('F d, Y', strtotime($record['date'])); ?></td>
                    <td><?php echo date('l', strtotime($record['date'])); ?></td>
                    <td>
                      <span class="badge bg-<?php echo $status_class; ?>">
                        <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                        <?php echo ucfirst($record['status']); ?>
                      </span>
                    </td>
                    <td><?php echo $record['remarks'] ?: '—'; ?></td>
                    <td><?php echo $record['marked_by_name'] ?: 'System'; ?></td>
                  </tr>
                <?php
                endwhile;
              else:
                ?>
                <tr>
                  <td colspan="5" class="text-center py-4">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3 d-block"></i>
                    No attendance records found for this period.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Donut Chart for Overall Attendance
  const donutCtx = document.getElementById('attendanceDonutChart').getContext('2d');
  new Chart(donutCtx, {
    type: 'doughnut',
    data: {
      labels: ['Present', 'Absent', 'Late', 'Excused'],
      datasets: [{
        data: [<?php echo $present_days; ?>, <?php echo $absent_days; ?>, <?php echo $late_days; ?>, <?php echo $excused_days; ?>],
        backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#8b5cf6'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 10,
            font: {
              size: 10
            }
          }
        }
      }
    }
  });

  // Monthly Attendance Chart
  const monthlyCtx = document.getElementById('monthlyAttendanceChart').getContext('2d');
  new Chart(monthlyCtx, {
    type: 'bar',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [{
        label: 'Attendance Percentage',
        data: [
          <?php echo $monthly_data[1]['percentage']; ?>,
          <?php echo $monthly_data[2]['percentage']; ?>,
          <?php echo $monthly_data[3]['percentage']; ?>,
          <?php echo $monthly_data[4]['percentage']; ?>,
          <?php echo $monthly_data[5]['percentage']; ?>,
          <?php echo $monthly_data[6]['percentage']; ?>,
          <?php echo $monthly_data[7]['percentage']; ?>,
          <?php echo $monthly_data[8]['percentage']; ?>,
          <?php echo $monthly_data[9]['percentage']; ?>,
          <?php echo $monthly_data[10]['percentage']; ?>,
          <?php echo $monthly_data[11]['percentage']; ?>,
          <?php echo $monthly_data[12]['percentage']; ?>
        ],
        backgroundColor: '#3b82f6',
        borderRadius: 5,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          title: {
            display: true,
            text: 'Attendance Percentage (%)'
          },
          ticks: {
            callback: function(value) {
              return value + '%';
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'Months'
          }
        }
      },
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.parsed.y + '%';
            }
          }
        },
        legend: {
          position: 'top'
        }
      }
    }
  });
</script>

<style>
  .calendar-grid {
    overflow-x: auto;
  }

  .calendar-grid .row {
    flex-wrap: nowrap;
    min-width: 700px;
  }

  .calendar-day {
    min-width: 60px;
    transition: all 0.2s ease;
    cursor: pointer;
  }

  .calendar-day:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .calendar-day.present {
    background-color: #10b981;
    color: white;
  }

  .calendar-day.absent {
    background-color: #ef4444;
    color: white;
  }

  .calendar-day.late {
    background-color: #f59e0b;
    color: white;
  }

  .calendar-day.excused {
    background-color: #8b5cf6;
    color: white;
  }

  .calendar-day.no-data {
    background-color: #f3f4f6;
    color: #9ca3af;
  }

  @media (max-width: 768px) {
    .calendar-day {
      min-width: 45px;
      padding: 8px 4px !important;
      font-size: 12px;
    }

    .attendance-stat .stat-value {
      font-size: 1.5rem;
    }
  }
</style>

<?php include '../includes/footer.php'; ?>