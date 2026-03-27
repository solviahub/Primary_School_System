<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Manage Subjects';

// Handle subject deletion
if (isset($_GET['delete'])) {
  $subject_id = (int)$_GET['delete'];
  $query = "DELETE FROM subjects WHERE id = $subject_id";
  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Subject deleted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deleting subject!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/manage_subjects.php');
}

// Handle subject creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  $subject_name = sanitize($_POST['subject_name']);
  $subject_code = sanitize($_POST['subject_code']);
  $description = sanitize($_POST['description']);

  if ($action == 'create') {
    $query = "INSERT INTO subjects (subject_name, subject_code, description) 
                  VALUES ('$subject_name', '$subject_code', '$description')";

    if (mysqli_query($conn, $query)) {
      $_SESSION['message'] = 'Subject created successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error creating subject!';
      $_SESSION['message_type'] = 'danger';
    }
  } elseif ($action == 'edit') {
    $subject_id = (int)$_POST['subject_id'];
    $query = "UPDATE subjects SET subject_name='$subject_name', subject_code='$subject_code', description='$description' 
                  WHERE id=$subject_id";

    if (mysqli_query($conn, $query)) {
      $_SESSION['message'] = 'Subject updated successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error updating subject!';
      $_SESSION['message_type'] = 'danger';
    }
  }

  redirect('admin/manage_subjects.php');
}

// Get all subjects
$subjects = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");

// Get classes for assignment
$classes = mysqli_query($conn, "SELECT * FROM classes");

// Get teachers for assignment
$teachers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active'");

// Handle subject-class assignment
if (isset($_POST['assign_subject'])) {
  $class_id = (int)$_POST['class_id'];
  $subject_id = (int)$_POST['subject_id'];
  $teacher_id = (int)$_POST['teacher_id'];

  $query = "INSERT INTO class_subjects (class_id, subject_id, teacher_id) 
              VALUES ($class_id, $subject_id, $teacher_id)";

  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Subject assigned to class successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error assigning subject!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/manage_subjects.php');
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-book me-2"></i>All Subjects</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover datatable">
            <thead>
              <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                <tr>
                  <td><strong><?php echo $subject['subject_code']; ?></strong></td>
                  <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                  <td><?php echo htmlspecialchars(substr($subject['description'], 0, 50)); ?></td>
                  <td>
                    <button class="btn btn-sm btn-info edit-subject"
                      data-id="<?php echo $subject['id']; ?>"
                      data-code="<?php echo $subject['subject_code']; ?>"
                      data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                      data-description="<?php echo htmlspecialchars($subject['description']); ?>">
                      <i class="fas fa-edit"></i>
                    </button>
                    <a href="?delete=<?php echo $subject['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Subject</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label class="form-label">Subject Name *</label>
            <input type="text" class="form-control" name="subject_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Subject Code *</label>
            <input type="text" class="form-control" name="subject_code" placeholder="e.g., MATH101" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">Add Subject</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-chalkboard me-2"></i>Assign Subject to Class</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">Select Class</label>
            <select class="form-select" name="class_id" required>
              <option value="">Choose Class</option>
              <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name'] . ' ' . $class['section']; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Select Subject</label>
            <select class="form-select" name="subject_id" required>
              <option value="">Choose Subject</option>
              <?php
              $subjects_list = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");
              while ($subject = mysqli_fetch_assoc($subjects_list)):
              ?>
                <option value="<?php echo $subject['id']; ?>"><?php echo $subject['subject_name']; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Assign Teacher</label>
            <select class="form-select" name="teacher_id" required>
              <option value="">Choose Teacher</option>
              <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['full_name']; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <button type="submit" name="assign_subject" class="btn btn-success w-100">Assign Subject</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="subject_id" id="edit_subject_id">
          <div class="mb-3">
            <label class="form-label">Subject Name *</label>
            <input type="text" class="form-control" name="subject_name" id="edit_subject_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Subject Code *</label>
            <input type="text" class="form-control" name="subject_code" id="edit_subject_code" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Subject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Edit subject button click
  document.querySelectorAll('.edit-subject').forEach(button => {
    button.addEventListener('click', function() {
      document.getElementById('edit_subject_id').value = this.dataset.id;
      document.getElementById('edit_subject_name').value = this.dataset.name;
      document.getElementById('edit_subject_code').value = this.dataset.code;
      document.getElementById('edit_description').value = this.dataset.description;

      new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
    });
  });
</script>

<?php include '../includes/footer.php'; ?>