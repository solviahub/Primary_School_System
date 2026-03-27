<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Manage Classes';

// Handle class deletion
if (isset($_GET['delete'])) {
  $class_id = (int)$_GET['delete'];
  $query = "DELETE FROM classes WHERE id = $class_id";
  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Class deleted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deleting class!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/manage_classes.php');
}

// Handle class creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  $class_name = sanitize($_POST['class_name']);
  $section = sanitize($_POST['section']);
  $class_teacher_id = sanitize($_POST['class_teacher_id']) ?: 'NULL';
  $academic_year = sanitize($_POST['academic_year']);
  $capacity = (int)$_POST['capacity'];

  if ($action == 'create') {
    $query = "INSERT INTO classes (class_name, section, class_teacher_id, academic_year, capacity) 
                  VALUES ('$class_name', '$section', $class_teacher_id, '$academic_year', $capacity)";

    if (mysqli_query($conn, $query)) {
      $_SESSION['message'] = 'Class created successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error creating class!';
      $_SESSION['message_type'] = 'danger';
    }
  } elseif ($action == 'edit') {
    $class_id = (int)$_POST['class_id'];
    $query = "UPDATE classes SET class_name='$class_name', section='$section', 
                  class_teacher_id=$class_teacher_id, academic_year='$academic_year', capacity=$capacity 
                  WHERE id=$class_id";

    if (mysqli_query($conn, $query)) {
      $_SESSION['message'] = 'Class updated successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error updating class!';
      $_SESSION['message_type'] = 'danger';
    }
  }

  redirect('admin/manage_classes.php');
}

// Get all classes
$query = "SELECT c.*, u.full_name as teacher_name, 
          (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as student_count 
          FROM classes c 
          LEFT JOIN users u ON c.class_teacher_id = u.id 
          ORDER BY c.class_name";
$classes = mysqli_query($conn, $query);

// Get teachers for dropdown
$teachers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active'");

include '../includes/header.php';
?>

<div class="row mb-3">
  <div class="col-md-12">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
      <i class="fas fa-plus me-2"></i>Add New Class
    </button>
  </div>
</div>

<div class="row">
  <?php while ($class = mysqli_fetch_assoc($classes)): ?>
    <div class="col-md-6 col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="mb-0"><?php echo $class['class_name'] . ' ' . $class['section']; ?></h6>
        </div>
        <div class="card-body">
          <p><strong>Class Teacher:</strong> <?php echo $class['teacher_name'] ?: 'Not Assigned'; ?></p>
          <p><strong>Academic Year:</strong> <?php echo $class['academic_year']; ?></p>
          <p><strong>Capacity:</strong> <?php echo $class['capacity']; ?></p>
          <p><strong>Students Enrolled:</strong> <?php echo $class['student_count']; ?></p>
          <div class="progress mb-3">
            <?php $percentage = ($class['capacity'] > 0) ? ($class['student_count'] / $class['capacity']) * 100 : 0; ?>
            <div class="progress-bar" style="width: <?php echo $percentage; ?>%">
              <?php echo round($percentage); ?>%
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-sm btn-info edit-class"
            data-id="<?php echo $class['id']; ?>"
            data-name="<?php echo $class['class_name']; ?>"
            data-section="<?php echo $class['section']; ?>"
            data-teacher="<?php echo $class['class_teacher_id']; ?>"
            data-year="<?php echo $class['academic_year']; ?>"
            data-capacity="<?php echo $class['capacity']; ?>">
            <i class="fas fa-edit"></i> Edit
          </button>
          <a href="?delete=<?php echo $class['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
            <i class="fas fa-trash"></i> Delete
          </a>
          <a href="class_details.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-success">
            <i class="fas fa-eye"></i> View Details
          </a>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-chalkboard me-2"></i>Add New Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label class="form-label">Class Name *</label>
            <input type="text" class="form-control" name="class_name" placeholder="e.g., Grade 10" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Section</label>
            <input type="text" class="form-control" name="section" placeholder="e.g., A, B, C">
          </div>
          <div class="mb-3">
            <label class="form-label">Class Teacher</label>
            <select class="form-select" name="class_teacher_id">
              <option value="">Select Teacher</option>
              <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['full_name']; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Academic Year *</label>
            <input type="text" class="form-control" name="academic_year" placeholder="e.g., 2024-2025" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" class="form-control" name="capacity" value="40">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Class</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="class_id" id="edit_class_id">
          <div class="mb-3">
            <label class="form-label">Class Name *</label>
            <input type="text" class="form-control" name="class_name" id="edit_class_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Section</label>
            <input type="text" class="form-control" name="section" id="edit_section">
          </div>
          <div class="mb-3">
            <label class="form-label">Class Teacher</label>
            <select class="form-select" name="class_teacher_id" id="edit_teacher">
              <option value="">Select Teacher</option>
              <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['full_name']; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Academic Year *</label>
            <input type="text" class="form-control" name="academic_year" id="edit_year" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" class="form-control" name="capacity" id="edit_capacity">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Class</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Edit class button click
  document.querySelectorAll('.edit-class').forEach(button => {
    button.addEventListener('click', function() {
      document.getElementById('edit_class_id').value = this.dataset.id;
      document.getElementById('edit_class_name').value = this.dataset.name;
      document.getElementById('edit_section').value = this.dataset.section;
      document.getElementById('edit_teacher').value = this.dataset.teacher;
      document.getElementById('edit_year').value = this.dataset.year;
      document.getElementById('edit_capacity').value = this.dataset.capacity;

      new bootstrap.Modal(document.getElementById('editClassModal')).show();
    });
  });
</script>

<?php include '../includes/footer.php'; ?>