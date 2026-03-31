<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'Mark Attendance';
$teacher_id = $_SESSION['user_id'];

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get class details
$class_query = "SELECT * FROM classes WHERE id = $class_id";
$class = mysqli_fetch_assoc(mysqli_query($conn, $class_query));

// Get students in this class
$students_query = "SELECT s.*, u.full_name 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   WHERE s.class_id = $class_id AND s.status = 'active' 
                   ORDER BY u.full_name";
$students = mysqli_query($conn, $students_query);

// Get existing attendance for this date
$attendance_data = [];
$attendance_query = "SELECT student_id, status, remarks 
                     FROM attendance 
                     WHERE class_id = $class_id AND date = '$date'";
$attendance_result = mysqli_query($conn, $attendance_query);
while ($att = mysqli_fetch_assoc($attendance_result)) {
  $attendance_data[$att['student_id']] = $att;
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
  $attendance = $_POST['attendance'];

  foreach ($attendance as $student_id => $data) {
    $status = sanitize($data['status']);
    $remarks = sanitize($data['remarks']);

    // Check if attendance already exists
    $check_query = "SELECT id FROM attendance 
                        WHERE student_id = $student_id AND class_id = $class_id AND date = '$date'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
      // Update existing
      $update_query = "UPDATE attendance 
                            SET status = '$status', remarks = '$remarks', marked_by = $teacher_id 
                            WHERE student_id = $student_id AND class_id = $class_id AND date = '$date'";
      mysqli_query($conn, $update_query);
    } else {
      // Insert new
      $insert_query = "INSERT INTO attendance (student_id, class_id, date, status, remarks, marked_by) 
                            VALUES ($student_id, $class_id, '$date', '$status', '$remarks', $teacher_id)";
      mysqli_query($conn, $insert_query);
    }
  }

  // Log activity
  logActivity($teacher_id, 'Marked attendance for class ' . $class['class_name'], "Date: $date, Class: {$class['class_name']}");

  $_SESSION['message'] = 'Attendance saved successfully!';
  $_SESSION['message_type'] = 'success';
  redirect("teacher/mark_attendance.php?class_id=$class_id&date=$date");
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-check-circle me-2"></i>Mark Attendance - <?php echo $class['class_name'] . ' ' . $class['section']; ?>
          </h6>
          <div class="mt-2 mt-md-0">
            <input type="date" id="datePicker" value="<?php echo $date; ?>" class="form-control form-control-sm" style="width: 150px;">
            <a href="?class_id=<?php echo $class_id; ?>&date=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-primary mt-1">
              <i class="fas fa-calendar-day"></i> Today
            </a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead style="background-color: #3b82f6; color: white;">
                <tr class="text-center">
                  <th style="width: 5%;">#</th>
                  <th style="width: 30%;">Student Name</th>
                  <th style="width: 20%;">Admission No</th>
                  <th style="width: 20%;">Status</th>
                  <th style="width: 25%;">Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $count = 1;
                while ($student = mysqli_fetch_assoc($students)):
                  $existing = isset($attendance_data[$student['id']]) ? $attendance_data[$student['id']] : null;
                  $status = $existing ? $existing['status'] : 'present';
                  $remarks = $existing ? $existing['remarks'] : '';
                ?>
                  <tr>
                    <td class="text-center"><?php echo $count++; ?></td>
                    <td><?php echo $student['full_name']; ?></td>
                    <td class="text-center"><?php echo $student['admission_number']; ?></td>
                    <td class="text-center">
                      <select name="attendance[<?php echo $student['id']; ?>][status]" class="form-select form-select-sm">
                        <option value="present" <?php echo $status == 'present' ? 'selected' : ''; ?>>Present</option>
                        <option value="absent" <?php echo $status == 'absent' ? 'selected' : ''; ?>>Absent</option>
                        <option value="late" <?php echo $status == 'late' ? 'selected' : ''; ?>>Late</option>
                        <option value="excused" <?php echo $status == 'excused' ? 'selected' : ''; ?>>Excused</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" name="attendance[<?php echo $student['id']; ?>][remarks]"
                        class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars($remarks); ?>"
                        placeholder="Optional remarks...">
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            <button type="submit" name="save_attendance" class="btn" style="background-color: #3b82f6; color: white;">
              <i class="fas fa-save me-2"></i>Save Attendance
            </button>
            <a href="dashboard.php" class="btn btn-secondary ms-2">
              <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Attendance Summary -->
    <div class="card shadow-sm mt-4">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chart-bar me-2"></i>Attendance Summary
        </h6>
      </div>
      <div class="card-body">
        <?php
        // Get attendance statistics for this class
        $stats_query = "SELECT 
                                   COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                                   COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                                   COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                                   COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused,
                                   COUNT(*) as total
                                FROM attendance 
                                WHERE class_id = $class_id AND date = '$date'";
        $stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));
        ?>

        <div class="row text-center">
          <div class="col-3">
            <div class="bg-success text-white p-3 rounded">
              <h4><?php echo $stats['present']; ?></h4>
              <small>Present</small>
            </div>
          </div>
          <div class="col-3">
            <div class="bg-danger text-white p-3 rounded">
              <h4><?php echo $stats['absent']; ?></h4>
              <small>Absent</small>
            </div>
          </div>
          <div class="col-3">
            <div class="bg-warning text-dark p-3 rounded">
              <h4><?php echo $stats['late']; ?></h4>
              <small>Late</small>
            </div>
          </div>
          <div class="col-3">
            <div class="bg-info text-white p-3 rounded">
              <h4><?php echo $stats['excused']; ?></h4>
              <small>Excused</small>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <div class="progress" style="height: 30px;">
            <?php
            $total = $stats['total'] > 0 ? $stats['total'] : 1;
            $present_percent = ($stats['present'] / $total) * 100;
            ?>
            <div class="progress-bar bg-success" style="width: <?php echo $present_percent; ?>%">
              Present: <?php echo round($present_percent); ?>%
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('datePicker').addEventListener('change', function() {
    window.location.href = '?class_id=<?php echo $class_id; ?>&date=' + this.value;
  });
</script>

<?php include '../includes/footer.php'; ?>