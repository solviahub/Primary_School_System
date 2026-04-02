<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['parent']);

$page_title = 'View Attendance';
$parent_id = $_SESSION['user_id'];

// Get children
$children_query = "SELECT s.*, u.full_name, c.class_name 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   JOIN classes c ON s.class_id = c.id 
                   WHERE s.parent_id = $parent_id AND s.status = 'active'";
$children = mysqli_query($conn, $children_query);

// Get selected child
$selected_child = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

$attendance_data = [];
$summary = [];
$student_info = null;

if ($selected_child) {
  // Get student details
  $student_query = "SELECT s.*, u.full_name, c.class_name 
                      FROM students s 
                      JOIN users u ON s.user_id = u.id 
                      JOIN classes c ON s.class_id = c.id 
                      WHERE s.id = $selected_child";
  $student_info = mysqli_fetch_assoc(mysqli_query($conn, $student_query));

  if ($student_info) {
    $year = date('Y', strtotime($month));
    $month_num = date('m', strtotime($month));

    // Get attendance summary
    $summary_query = "SELECT 
                            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                            COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                            COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused,
                            COUNT(*) as total
                          FROM attendance 
                          WHERE student_id = $selected_child 
                          AND MONTH(date) = $month_num AND YEAR(date) = $year";
    $summary = mysqli_fetch_assoc(mysqli_query($conn, $summary_query));

    // Get daily attendance
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);
    for ($day = 1; $day <= $days_in_month; $day++) {
      $date = "$year-$month_num-" . str_pad($day, 2, '0', STR_PAD_LEFT);
      $daily_query = "SELECT status, remarks FROM attendance 
                            WHERE student_id = $selected_child AND date = '$date'";
      $daily_result = mysqli_query($conn, $daily_query);
      $attendance_data[$day] = mysqli_fetch_assoc($daily_result);
    }
  }
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-3 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-child me-2"></i>Select Child
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php while ($child = mysqli_fetch_assoc($children)): ?>
            <a href="?student_id=<?php echo $child['id']; ?>&month=<?php echo $month; ?>"
              class="list-group-item list-group-item-action <?php echo $selected_child == $child['id'] ? 'active' : ''; ?>"
              style="<?php echo $selected_child == $child['id'] ? 'background-color: #3b82f6; border-color: #3b82f6;' : ''; ?>">
              <div>
                <strong><?php echo htmlspecialchars($child['full_name']); ?></strong>
                <br>
                <small><?php echo $child['class_name']; ?> | <?php echo $child['admission_number']; ?></small>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <?php if ($selected_child && $student_info): ?>
      <div class="card shadow-sm mt-3">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-calendar me-2"></i>Select Month
          </h6>
        </div>
        <div class="card-body">
          <input type="month" class="form-control" id="monthPicker" value="<?php echo $month; ?>">
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-md-9">
    <?php if ($selected_child && $student_info): ?>
      <!-- Summary Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="bg-success text-white p-3 rounded text-center">
            <h3><?php echo $summary['present'] ?? 0; ?></h3>
            <small>Present</small>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bg-danger text-white p-3 rounded text-center">
            <h3><?php echo $summary['absent'] ?? 0; ?></h3>
            <small>Absent</small>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bg-warning text-dark p-3 rounded text-center">
            <h3><?php echo $summary['late'] ?? 0; ?></h3>
            <small>Late</small>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bg-info text-white p-3 rounded text-center">
            <h3><?php echo $summary['excused'] ?? 0; ?></h3>
            <small>Excused</small>
          </div>
        </div>
      </div>

      <!-- Attendance Calendar -->
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-calendar-alt me-2"></i>
            Attendance Calendar - <?php echo date('F Y', strtotime($month)); ?>
          </h6>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered text-center">
              <thead style="background-color: #3b82f6; color: white;">
                <tr>
                  <th>Sun</th>
                  <th>Mon</th>
                  <th>Tue</th>
                  <th>Wed</th>
                  <th>Thu</th>
                  <th>Fri</th>
                  <th>Sat</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $first_day = date('w', strtotime("$year-$month_num-01"));
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);
                $day_counter = 1;

                for ($i = 0; $i < 6; $i++) {
                  echo '<tr>';
                  for ($j = 0; $j < 7; $j++) {
                    if ($i == 0 && $j < $first_day) {
                      echo '<td></td>';
                    } elseif ($day_counter <= $days_in_month) {
                      $status = isset($attendance_data[$day_counter]) ? $attendance_data[$day_counter]['status'] : null;
                      $badge_class = '';
                      if ($status == 'present') $badge_class = 'bg-success';
                      elseif ($status == 'absent') $badge_class = 'bg-danger';
                      elseif ($status == 'late') $badge_class = 'bg-warning';
                      elseif ($status == 'excused') $badge_class = 'bg-info';
                      else $badge_class = 'bg-secondary';

                      echo '<td>';
                      echo '<div class="mb-1">' . $day_counter . '</div>';
                      if ($status) {
                        echo '<span class="badge ' . $badge_class . '" style="font-size: 10px;">' . ucfirst($status) . '</span>';
                      }
                      echo '</td>';
                      $day_counter++;
                    } else {
                      echo '<td></td>';
                    }
                  }
                  echo '</tr>';
                  if ($day_counter > $days_in_month) break;
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <div class="row text-center">
            <div class="col"><span class="badge bg-success">Present</span> <small>Present</small></div>
            <div class="col"><span class="badge bg-danger">Absent</span> <small>Absent</small></div>
            <div class="col"><span class="badge bg-warning">Late</span> <small>Late</small></div>
            <div class="col"><span class="badge bg-info">Excused</span> <small>Excused</small></div>
            <div class="col"><span class="badge bg-secondary">N/A</span> <small>No Data</small></div>
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-calendar-check fa-4x text-muted mb-3"></i>
          <h5>Select a child to view attendance</h5>
          <p class="text-muted">Choose a child from the left panel to view their attendance records.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  document.getElementById('monthPicker')?.addEventListener('change', function() {
    window.location.href = '?student_id=<?php echo $selected_child; ?>&month=' + this.value;
  });
</script>

<?php include '../includes/footer.php'; ?>