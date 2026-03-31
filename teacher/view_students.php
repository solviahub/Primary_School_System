<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'View Students';
$teacher_id = $_SESSION['user_id'];

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get class details
$class_query = "SELECT * FROM classes WHERE id = $class_id";
$class = mysqli_fetch_assoc(mysqli_query($conn, $class_query));

// Get students in this class
$students_query = "SELECT s.*, u.full_name, u.email, u.phone 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   WHERE s.class_id = $class_id AND s.status = 'active' 
                   ORDER BY u.full_name";
$students = mysqli_query($conn, $students_query);

include '../includes/header.php';
?>

<div class="card shadow-sm">
  <div class="card-header bg-white">
    <div class="d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
        <i class="fas fa-users me-2"></i>Students in <?php echo $class['class_name'] . ' ' . $class['section']; ?>
      </h6>
      <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover datatable">
        <thead>
          <tr>
            <th>#</th>
            <th>Admission No</th>
            <th>Student Name</th>
            <th>Email</th>
            <th>Phone</th>
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
              <td><?php echo $student['full_name']; ?></td>
              <td><?php echo $student['email']; ?></td>
              <td><?php echo $student['phone']; ?></td>
              <td>
                <a href="mark_attendance.php?class_id=<?php echo $class_id; ?>&student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-success me-1">
                  <i class="fas fa-check-circle"></i> Mark Attendance
                </a>
                <a href="upload_marks.php?class_id=<?php echo $class_id; ?>&student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-upload"></i> Add Marks
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>