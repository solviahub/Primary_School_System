<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Promote Students';

// Get all classes
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// Handle promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['promote'])) {
  $from_class = (int)$_POST['from_class'];
  $to_class = (int)$_POST['to_class'];
  $academic_year = sanitize($_POST['academic_year']);

  // Get students from current class
  $students = mysqli_query($conn, "SELECT id FROM students WHERE class_id = $from_class AND status = 'active'");

  $promoted = 0;
  while ($student = mysqli_fetch_assoc($students)) {
    $query = "UPDATE students SET class_id = $to_class WHERE id = {$student['id']}";
    if (mysqli_query($conn, $query)) {
      $promoted++;
    }
  }

  if ($promoted > 0) {
    $_SESSION['message'] = "$promoted students promoted successfully to the next class!";
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = "No students found to promote!";
    $_SESSION['message_type'] = 'warning';
  }

  redirect('admin/promote_students.php');
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-6 mx-auto">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-arrow-up me-2"></i>Promote Students to Next Class</h6>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          This will promote all active students from the selected class to the next class for the new academic year.
        </div>

        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">From Class (Current)</label>
            <select class="form-select" name="from_class" required>
              <option value="">Select Current Class</option>
              <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $class['id']; ?>">
                  <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">To Class (Next Level)</label>
            <select class="form-select" name="to_class" required>
              <option value="">Select Next Class</option>
              <?php
              $classes2 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
              while ($class = mysqli_fetch_assoc($classes2)):
              ?>
                <option value="<?php echo $class['id']; ?>">
                  <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">New Academic Year</label>
            <input type="text" class="form-control" name="academic_year"
              value="<?php echo date('Y') . '-' . (date('Y') + 1); ?>" required>
          </div>

          <button type="submit" name="promote" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to promote all students? This action cannot be undone.')">
            <i class="fas fa-arrow-up me-2"></i>Promote Students
          </button>
        </form>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Class Statistics</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <