<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'View Students';
$teacher_id = $_SESSION['user_id'];

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get teacher's classes for dropdown
$classes_query = "SELECT DISTINCT c.id, c.class_name, c.section 
                  FROM classes c 
                  JOIN class_subjects cs ON c.id = cs.class_id 
                  WHERE cs.teacher_id = $teacher_id 
                  ORDER BY c.class_name, c.section";
$teacher_classes = mysqli_query($conn, $classes_query);

// Get class details only if class_id is provided and valid
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
  $students_query = "SELECT s.*, u.full_name, u.email, u.phone 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.class_id = $class_id AND s.status = 'active' 
                       ORDER BY u.full_name";
  $students = mysqli_query($conn, $students_query);
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
          <div class="col-md-6">
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
          <?php if ($class_id > 0): ?>
            <div class="col-md-6">
              <label class="form-label fw-semibold">&nbsp;</label>
              <div>
                <a href="mark_attendance.php?class_id=<?php echo $class_id; ?>" class="btn btn-outline-success me-2">
                  <i class="fas fa-check-circle me-1"></i> Mark Attendance
                </a>
                <a href="upload_marks.php?class_id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                  <i class="fas fa-upload me-1"></i> Upload Marks
                </a>
              </div>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Students Table -->
    <?php if ($class_id > 0 && $class !== null): ?>
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
              <i class="fas fa-users me-2"></i>Students in <?php echo $class['class_name'] . ' ' . $class['section']; ?>
            </h6>
            <span class="badge bg-primary">Total: <?php echo mysqli_num_rows($students); ?> Students</span>
          </div>
        </div>
        <div class="card-body">
          <?php if (mysqli_num_rows($students) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover datatable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $count = 1;
                  while ($student = mysqli_fetch_assoc($students)):
                  ?>
                    <tr>
                      <td><?php echo $count++; ?></td>
                      <td><strong><?php echo $student['admission_number']; ?></strong></td>
                      <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                      <td><?php echo htmlspecialchars($student['email']); ?></td>
                      <td><?php echo htmlspecialchars($student['phone']); ?></td>
                      <td><?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A'; ?></td>
                      <td>
                        <span class="badge bg-<?php echo $student['gender'] == 'male' ? 'info' : 'danger'; ?>">
                          <?php echo ucfirst($student['gender']); ?>
                        </span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <a href="mark_attendance.php?class_id=<?php echo $class_id; ?>&student_id=<?php echo $student['id']; ?>"
                            class="btn btn-outline-success" title="Mark Attendance">
                            <i class="fas fa-check-circle"></i>
                          </a>
                          <a href="upload_marks.php?class_id=<?php echo $class_id; ?>&student_id=<?php echo $student['id']; ?>"
                            class="btn btn-outline-primary" title="Add Marks">
                            <i class="fas fa-upload"></i>
                          </a>
                          <a href="report_cards.php?class_id=<?php echo $class_id; ?>&student_id=<?php echo $student['id']; ?>"
                            class="btn btn-outline-info" title="View Report Card">
                            <i class="fas fa-id-card"></i>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <i class="fas fa-users fa-4x text-muted mb-3"></i>
              <h5>No Students Found</h5>
              <p class="text-muted">No students are currently enrolled in this class.</p>
              <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif ($class_id > 0 && $class === null): ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
          <h5>Class Not Found</h5>
          <p class="text-muted">The selected class does not exist or you don't have permission to view it.</p>
          <a href="view_students.php" class="btn btn-primary">Select Another Class</a>
        </div>
      </div>
    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-chalkboard fa-4x text-muted mb-3"></i>
          <h5>Select a Class</h5>
          <p class="text-muted">Please select a class from the dropdown above to view students.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>