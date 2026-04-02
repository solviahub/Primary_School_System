<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'Mark Attendance';
$teacher_id = $_SESSION['user_id'];

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get teacher's classes for dropdown
$classes_query = "SELECT DISTINCT c.id, c.class_name, c.section 
                  FROM classes c 
                  JOIN class_subjects cs ON c.id = cs.class_id 
                  WHERE cs.teacher_id = $teacher_id 
                  ORDER BY c.class_name, c.section";
$teacher_classes = mysqli_query($conn, $classes_query);

// Get class details only if class_id is provided
$class = null;
if ($class_id > 0) {
  $class_query = "SELECT * FROM classes WHERE id = $class_id";
  $class_result = mysqli_query($conn, $class_query);
  if (mysqli_num_rows($class_result) > 0) {
    $class = mysqli_fetch_assoc($class_result);
  }
}

// Get students in this class only if class exists
$students = [];
if ($class_id > 0 && $class !== null) {
  $students_query = "SELECT s.*, u.full_name 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.class_id = $class_id AND s.status = 'active' 
                       ORDER BY u.full_name";
  $students = mysqli_query($conn, $students_query);
}

// Get existing attendance for this date
$attendance_data = [];
if ($class_id > 0 && $class !== null) {
  $attendance_query = "SELECT student_id, status, remarks 
                         FROM attendance 
                         WHERE class_id = $class_id AND date = '$date'";
  $attendance_result = mysqli_query($conn, $attendance_query);
  while ($att = mysqli_fetch_assoc($attendance_result)) {
    $attendance_data[$att['student_id']] = $att;
  }
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
  if ($class_id > 0 && $class !== null) {
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
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-12">
    <!-- Class Selection Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-filter me-2"></i>Select Class
        </h6>
      </div>
      <div class="card-body">
        <form method="GET" action="" class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Choose a Class</label>
            <select class="form-select" name="class_id" onchange="this.form.submit()" required>
              <option value="">-- Select Class --</option>
              <?php while ($cls = mysqli_fetch_assoc($teacher_classes)): ?>
                <option value="<?php echo $cls['id']; ?>" <?php echo $class_id == $cls['id'] ? 'selected' : ''; ?>>
                  <?php echo $cls['class_name'] . ' ' . $cls['section']; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <?php if ($class_id > 0 && $class !== null): ?>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Select Date</label>
              <input type="date" name="date" class="form-control" value="<?php echo $date; ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">&nbsp;</label>
              <a href="?class_id=<?php echo $class_id; ?>&date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary w-100">
                <i class="fas fa-calendar-day me-1"></i> Today
              </a>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Attendance Form -->
    <?php if ($class_id > 0 && $class !== null): ?>
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
              <i class="fas fa-check-circle me-2"></i>Mark Attendance - <?php echo $class['class_name'] . ' ' . $class['section']; ?>
            </h6>
            <span class="badge bg-primary">Date: <?php echo date('F d, Y', strtotime($date)); ?></span>
          </div>
        </div>
        <div class="card-body">
          <?php if (mysqli_num_rows($students) > 0): ?>
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
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
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
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-users fa-3x text-muted mb-3"></i>
              <p class="text-muted">No students enrolled in this class yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Attendance Summary -->
      <?php if (mysqli_num_rows($students) > 0): ?>
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
            $stats_result = mysqli_query($conn, $stats_query);
            $stats = mysqli_fetch_assoc($stats_result);

            $total = ($stats['total'] ?? 0) > 0 ? ($stats['total'] ?? 0) : 1;
            $present = $stats['present'] ?? 0;
            $absent = $stats['absent'] ?? 0;
            $late = $stats['late'] ?? 0;
            $excused = $stats['excused'] ?? 0;
            $present_percent = ($present / $total) * 100;
            ?>

            <div class="row text-center">
              <div class="col-3">
                <div class="bg-success text-white p-3 rounded">
                  <h4><?php echo $present; ?></h4>
                  <small>Present</small>
                </div>
              </div>
              <div class="col-3">
                <div class="bg-danger text-white p-3 rounded">
                  <h4><?php echo $absent; ?></h4>
                  <small>Absent</small>
                </div>
              </div>
              <div class="col-3">
                <div class="bg-warning text-dark p-3 rounded">
                  <h4><?php echo $late; ?></h4>
                  <small>Late</small>
                </div>
              </div>
              <div class="col-3">
                <div class="bg-info text-white p-3 rounded">
                  <h4><?php echo $excused; ?></h4>
                  <small>Excused</small>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-success" style="width: <?php echo $present_percent; ?>%">
                  Present: <?php echo round($present_percent); ?>%
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    <?php elseif ($class_id > 0 && $class === null): ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
          <h5>Class Not Found</h5>
          <p class="text-muted">The selected class does not exist or you don't have permission to access it.</p>
          <a href="mark_attendance.php" class="btn btn-primary">Select Another Class</a>
        </div>
      </div>
    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-check-circle fa-4x text-primary mb-3"></i>
          <h5>Select a Class to Mark Attendance</h5>
          <p class="text-muted">Please select a class from the dropdown above to mark attendance.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>