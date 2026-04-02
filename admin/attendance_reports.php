<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Attendance Reports';

// Get filter parameters
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = date('Y', strtotime($month));
$month_num = date('m', strtotime($month));

// Get all classes for filter dropdown
$classes_query = "SELECT * FROM classes ORDER BY class_name, section";
$classes = mysqli_query($conn, $classes_query);

// Get class name if selected
$class_name = '';
if ($class_id) {
  $class_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT class_name, section FROM classes WHERE id = $class_id"));
  $class_name = $class_info['class_name'] . ' ' . $class_info['section'];
}

// Get attendance summary for selected class and month
$summary = [];
$students = [];
$attendance_data = [];

if ($class_id) {
  // Get all students in the class - FIXED: specify table for status column
  $students_query = "SELECT s.id, s.admission_number, u.full_name 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.class_id = $class_id AND s.status = 'active' 
                       ORDER BY u.full_name";
  $students_result = mysqli_query($conn, $students_query);

  while ($student = mysqli_fetch_assoc($students_result)) {
    $students[] = $student;
  }

  // Get attendance data for each student for the selected month
  foreach ($students as $student) {
    $attendance_query = "SELECT 
                                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                                COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                                COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused,
                                COUNT(*) as total
                             FROM attendance 
                             WHERE student_id = {$student['id']} 
                             AND MONTH(date) = $month_num AND YEAR(date) = $year";
    $attendance_result = mysqli_query($conn, $attendance_query);
    $attendance = mysqli_fetch_assoc($attendance_result);

    $attendance_data[$student['id']] = $attendance;
  }

  // Calculate overall class summary - FIXED: specify table for status column
  $summary_query = "SELECT 
                        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as total_present,
                        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as total_absent,
                        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as total_late,
                        COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as total_excused,
                        COUNT(*) as total_records
                      FROM attendance a
                      JOIN students s ON a.student_id = s.id
                      WHERE s.class_id = $class_id 
                      AND MONTH(a.date) = $month_num AND YEAR(a.date) = $year";
  $summary_result = mysqli_query($conn, $summary_query);
  $summary = mysqli_fetch_assoc($summary_result);

  // Get daily attendance for chart - FIXED: specify table for status column
  $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);
  $daily_attendance = [];

  for ($day = 1; $day <= $days_in_month; $day++) {
    $date = "$year-$month_num-" . str_pad($day, 2, '0', STR_PAD_LEFT);
    $daily_query = "SELECT 
                           COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                           COUNT(*) as total_count
                        FROM attendance a
                        JOIN students s ON a.student_id = s.id
                        WHERE s.class_id = $class_id AND a.date = '$date'";
    $daily_result = mysqli_query($conn, $daily_query);
    $daily = mysqli_fetch_assoc($daily_result);

    $daily_attendance[$day] = [
      'date' => $date,
      'present' => $daily['present_count'] ?? 0,
      'total' => $daily['total_count'] ?? 0,
      'percentage' => ($daily['total_count'] > 0) ? round(($daily['present_count'] / $daily['total_count']) * 100, 2) : 0
    ];
  }
}

include '../includes/header.php';
?>

<style>
  .attendance-stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .attendance-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
  }

  .attendance-stat-card h3 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
  }

  .attendance-stat-card p {
    margin: 0;
    color: #6c757d;
  }

  .filter-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .table-responsive {
    overflow-x: auto;
  }
</style>

<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 15px; padding: 2rem;">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="text-white">Attendance Reports</h2>
          <p class="text-white-50 mb-0">View and analyze student attendance across all classes.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-chart-line" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Section -->
<div class="filter-card">
  <form method="GET" action="" class="row g-3">
    <div class="col-md-4">
      <label class="form-label fw-semibold">Select Class</label>
      <select class="form-select" name="class_id" required>
        <option value="">-- Select Class --</option>
        <?php while ($class = mysqli_fetch_assoc($classes)): ?>
          <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
            <?php echo $class['class_name'] . ' ' . $class['section']; ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">Select Month</label>
      <input type="month" class="form-control" name="month" value="<?php echo $month; ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">&nbsp;</label>
      <button type="submit" class="btn w-100" style="background-color: #3b82f6; color: white;">
        <i class="fas fa-search me-2"></i>Generate Report
      </button>
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold">&nbsp;</label>
      <?php if ($class_id && !empty($students)): ?>
        <button type="button" class="btn btn-outline-secondary w-100" onclick="exportReport()">
          <i class="fas fa-download me-2"></i>Export
        </button>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ($class_id && !empty($students)): ?>

  <!-- Summary Statistics -->
  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="attendance-stat-card">
        <h3 class="text-primary"><?php echo number_format($summary['total_records'] ?? 0); ?></h3>
        <p>Total Records</p>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="attendance-stat-card">
        <h3 class="text-success"><?php echo number_format($summary['total_present'] ?? 0); ?></h3>
        <p>Present Days</p>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="attendance-stat-card">
        <h3 class="text-danger"><?php echo number_format($summary['total_absent'] ?? 0); ?></h3>
        <p>Absent Days</p>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="attendance-stat-card">
        <?php
        $total_records = ($summary['total_records'] ?? 0);
        $total_present = ($summary['total_present'] ?? 0);
        $overall_percentage = ($total_records > 0) ? round(($total_present / $total_records) * 100, 2) : 0;
        ?>
        <h3 class="text-info"><?php echo $overall_percentage; ?>%</h3>
        <p>Overall Attendance</p>
      </div>
    </div>
  </div>

  <!-- Attendance Chart -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
      <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
        <i class="fas fa-chart-line me-2"></i>Daily Attendance Trend - <?php echo date('F Y', strtotime($month)); ?>
      </h6>
    </div>
    <div class="card-body">
      <canvas id="attendanceTrendChart" style="max-height: 300px; width: 100%;"></canvas>
    </div>
  </div>

  <!-- Student Attendance Table -->
  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-users me-2"></i>Student Attendance Details - <?php echo $class_name; ?> (<?php echo date('F Y', strtotime($month)); ?>)
        </h6>
        <div class="mt-2 mt-md-0">
          <button class="btn btn-sm btn-outline-primary me-1" onclick="printReport()">
            <i class="fas fa-print me-1"></i> Print
          </button>
          <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()">
            <i class="fas fa-file-csv me-1"></i> CSV
          </button>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive" id="attendanceTable">
        <table class="table table-hover mb-0">
          <thead style="background-color: #3b82f6; color: white;">
            <tr class="text-center">
              <th style="width: 5%;">#</th>
              <th style="width: 25%;">Student Name</th>
              <th style="width: 15%;">Admission No</th>
              <th style="width: 10%;">Present</th>
              <th style="width: 10%;">Absent</th>
              <th style="width: 10%;">Late</th>
              <th style="width: 10%;">Excused</th>
              <th style="width: 15%;">Attendance %</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $counter = 1;
            foreach ($students as $student):
              $data = isset($attendance_data[$student['id']]) ? $attendance_data[$student['id']] : ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
              $present = $data['present'] ?? 0;
              $absent = $data['absent'] ?? 0;
              $late = $data['late'] ?? 0;
              $excused = $data['excused'] ?? 0;
              $total = $data['total'] ?? 0;
              $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

              $badge_class = 'success';
              if ($percentage < 75) $badge_class = 'danger';
              elseif ($percentage < 85) $badge_class = 'warning';
              elseif ($percentage < 95) $badge_class = 'info';
            ?>
              <tr class="text-center">
                <td><?php echo $counter++; ?></td>
                <td class="text-start"><?php echo htmlspecialchars($student['full_name']); ?></td>
                <td><?php echo $student['admission_number']; ?></td>
                <td class="text-success fw-bold"><?php echo $present; ?></td>
                <td class="text-danger"><?php echo $absent; ?></td>
                <td class="text-warning"><?php echo $late; ?></td>
                <td><?php echo $excused; ?></td>
                <td>
                  <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $percentage; ?>%</span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot style="background-color: #f8f9fa;">
            <tr class="fw-bold">
              <td colspan="3" class="text-end">TOTALS:</td>
              <td class="text-center text-success"><?php echo number_format($summary['total_present'] ?? 0); ?></td>
              <td class="text-center text-danger"><?php echo number_format($summary['total_absent'] ?? 0); ?></td>
              <td class="text-center text-warning"><?php echo number_format($summary['total_late'] ?? 0); ?></td>
              <td class="text-center"><?php echo number_format($summary['total_excused'] ?? 0); ?></td>
              <td class="text-center">
                <span class="badge bg-primary"><?php echo $overall_percentage; ?>%</span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Attendance Summary by Student Type -->
  <div class="row mt-4">
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-chart-pie me-2"></i>Attendance Distribution
          </h6>
        </div>
        <div class="card-body">
          <canvas id="attendancePieChart" style="max-height: 300px;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-exclamation-triangle me-2"></i>Students Needing Attention
          </h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Student Name</th>
                  <th>Admission No</th>
                  <th>Attendance %</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $low_attendance = [];
                foreach ($students as $student) {
                  $data = isset($attendance_data[$student['id']]) ? $attendance_data[$student['id']] : ['present' => 0, 'total' => 0];
                  $present = $data['present'] ?? 0;
                  $total = $data['total'] ?? 0;
                  $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

                  if ($percentage < 75 && $total > 0) {
                    $low_attendance[] = [
                      'name' => $student['full_name'],
                      'admission' => $student['admission_number'],
                      'percentage' => $percentage
                    ];
                  }
                }

                if (!empty($low_attendance)):
                  foreach ($low_attendance as $student):
                ?>
                    <tr>
                      <td><?php echo htmlspecialchars($student['name']); ?></td>
                      <td><?php echo $student['admission']; ?></td>
                      <td><?php echo $student['percentage']; ?>%</td>
                      <td><span class="badge bg-danger">Low Attendance</span></td>
                    </tr>
                  <?php
                  endforeach;
                else:
                  ?>
                  <tr>
                    <td colspan="4" class="text-center py-4">
                      <i class="fas fa-check-circle text-success me-2"></i>
                      All students have attendance above 75%
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
    // Daily Attendance Trend Chart
    const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
    const dailyData = <?php
                      $chart_data = [];
                      foreach ($daily_attendance as $day => $data) {
                        $chart_data[] = $data['percentage'];
                      }
                      echo json_encode($chart_data);
                      ?>;

    const days = <?php
                  $day_labels = [];
                  for ($i = 1; $i <= $days_in_month; $i++) {
                    $day_labels[] = $i;
                  }
                  echo json_encode($day_labels);
                  ?>;

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: days,
        datasets: [{
          label: 'Attendance Percentage',
          data: dailyData,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#3b82f6',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
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
        },
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
              text: 'Day of Month'
            }
          }
        }
      }
    });

    // Attendance Distribution Pie Chart
    const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
    new Chart(pieCtx, {
      type: 'pie',
      data: {
        labels: ['Present', 'Absent', 'Late', 'Excused'],
        datasets: [{
          data: [
            <?php echo $summary['total_present'] ?? 0; ?>,
            <?php echo $summary['total_absent'] ?? 0; ?>,
            <?php echo $summary['total_late'] ?? 0; ?>,
            <?php echo $summary['total_excused'] ?? 0; ?>
          ],
          backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#8b5cf6'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                let value = context.parsed || 0;
                let total = <?php echo ($summary['total_records'] ?? 0); ?>;
                let percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          }
        }
      }
    });

    // Print report
    function printReport() {
      var printContents = document.getElementById('attendanceTable').innerHTML;
      var title = '<h2 style="text-align:center; margin-bottom:20px;">Attendance Report - <?php echo addslashes($class_name); ?> (<?php echo date('F Y', strtotime($month)); ?>)</h2>';
      var schoolName = '<h3 style="text-align:center;"><?php echo addslashes(getSetting('school_name', 'School Name')); ?></h3>';
      var printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
        <head>
            <title>Attendance Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                @media print {
                    body { margin: 0; padding: 0; }
                    .btn { display: none; }
                }
            </style>
        </head>
        <body>
            ${schoolName}
            ${title}
            <div class="table-responsive">
                ${printContents}
            </div>
            <p class="text-muted mt-4">Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </body>
        </html>
    `);
      printWindow.document.close();
      printWindow.print();
    }

    // Export to CSV
    function exportToCSV() {
      let csv = [];
      let rows = document.querySelectorAll('#attendanceTable table tr');

      for (let i = 0; i < rows.length; i++) {
        let row = [],
          cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
          let text = cols[j].innerText.replace(/,/g, ' ');
          row.push('"' + text + '"');
        }
        csv.push(row.join(','));
      }

      let csvFile = new Blob([csv.join('\n')], {
        type: 'text/csv'
      });
      let downloadLink = document.createElement('a');
      downloadLink.download = 'Attendance_Report_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $class_name); ?>_<?php echo date('F_Y'); ?>.csv';
      downloadLink.href = window.URL.createObjectURL(csvFile);
      downloadLink.style.display = 'none';
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }

    // Export full report
    function exportReport() {
      window.location.href = 'export_attendance.php?class_id=<?php echo $class_id; ?>&month=<?php echo $month; ?>';
    }
  </script>

<?php elseif ($class_id && empty($students)): ?>
  <div class="card shadow-sm">
    <div class="card-body text-center py-5">
      <i class="fas fa-users fa-4x text-muted mb-3"></i>
      <h5>No Students Found</h5>
      <p class="text-muted">No students are enrolled in this class yet.</p>
      <a href="manage_students.php" class="btn btn-primary">Add Students</a>
    </div>
  </div>
<?php elseif ($class_id == 0): ?>
  <div class="card shadow-sm">
    <div class="card-body text-center py-5">
      <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
      <h5>Select a Class to View Attendance Report</h5>
      <p class="text-muted">Choose a class and month from the filter above to generate attendance report.</p>
    </div>
  </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>