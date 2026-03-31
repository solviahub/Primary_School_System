<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'Manage Assignments';
$teacher_id = $_SESSION['user_id'];

// Get teacher's classes and subjects
$teacher_classes = mysqli_query($conn, "SELECT DISTINCT c.* 
                                        FROM classes c 
                                        JOIN class_subjects cs ON c.id = cs.class_id 
                                        WHERE cs.teacher_id = $teacher_id");

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
  $class_id = (int)$_POST['class_id'];
  $subject_id = (int)$_POST['subject_id'];
  $title = sanitize($_POST['title']);
  $description = sanitize($_POST['description']);
  $due_date = sanitize($_POST['due_date']);

  // Handle file upload
  $attachment = '';
  if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    $allowed = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'];
    $filename = $_FILES['attachment']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
      $upload_dir = '../uploads/assignments/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }
      $attachment = $upload_dir . time() . '_' . $filename;
      move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment);
      $attachment = str_replace('../', '', $attachment);
    }
  }

  $query = "INSERT INTO assignments (class_id, subject_id, title, description, due_date, attachment, created_by) 
              VALUES ($class_id, $subject_id, '$title', '$description', '$due_date', '$attachment', $teacher_id)";

  if (mysqli_query($conn, $query)) {
    logActivity($teacher_id, 'Created new assignment', "Title: $title, Class: $class_id");
    $_SESSION['message'] = 'Assignment created successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error creating assignment!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('teacher/manage_assignments.php');
}

// Handle assignment deletion
if (isset($_GET['delete'])) {
  $assignment_id = (int)$_GET['delete'];
  $query = "DELETE FROM assignments WHERE id = $assignment_id AND created_by = $teacher_id";
  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Assignment deleted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deleting assignment!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('teacher/manage_assignments.php');
}

// Get teacher's assignments
$assignments_query = "SELECT a.*, c.class_name, s.subject_name 
                      FROM assignments a 
                      JOIN classes c ON a.class_id = c.id 
                      JOIN subjects s ON a.subject_id = s.id 
                      WHERE a.created_by = $teacher_id 
                      ORDER BY a.created_at DESC";
$assignments = mysqli_query($conn, $assignments_query);

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-plus-circle me-2"></i>Create New Assignment
        </h6>
      </div>
      <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label fw-semibold">Class *</label>
            <select class="form-select" name="class_id" required>
              <option value="">Select Class</option>
              <?php while ($class = mysqli_fetch_assoc($teacher_classes)): ?>
                <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name'] . ' ' . $class['section']; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Subject *</label>
            <select class="form-select" name="subject_id" required>
              <option value="">Select Subject</option>
              <?php
              $teacher_subjects = mysqli_query($conn, "SELECT DISTINCT s.* 
                                                                     FROM subjects s 
                                                                     JOIN class_subjects cs ON s.id = cs.subject_id 
                                                                     WHERE cs.teacher_id = $teacher_id");
              while ($subject = mysqli_fetch_assoc($teacher_subjects)):
              ?>
                <option value="<?php echo $subject['id']; ?>"><?php echo $subject['subject_name']; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Assignment Title *</label>
            <input type="text" class="form-control" name="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Due Date *</label>
            <input type="date" class="form-control" name="due_date" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Attachment (Optional)</label>
            <input type="file" class="form-control" name="attachment">
            <small class="text-muted">Allowed: PDF, DOC, DOCX, TXT, JPG, PNG</small>
          </div>
          <button type="submit" name="create_assignment" class="btn w-100" style="background-color: #3b82f6; color: white;">
            <i class="fas fa-paper-plane me-2"></i>Create Assignment
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-tasks me-2"></i>My Assignments
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php if (mysqli_num_rows($assignments) > 0): ?>
            <?php while ($assignment = mysqli_fetch_assoc($assignments)):
              $is_overdue = strtotime($assignment['due_date']) < time();
              $status_class = $is_overdue ? 'danger' : 'warning';
            ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                      <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                      <span class="badge bg-<?php echo $status_class; ?> ms-2">
                        <?php echo $is_overdue ? 'Overdue' : 'Active'; ?>
                      </span>
                    </div>
                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($assignment['description']); ?></p>
                    <div class="row mt-2">
                      <div class="col-md-4">
                        <small><i class="fas fa-chalkboard me-1"></i> <?php echo $assignment['class_name']; ?></small>
                      </div>
                      <div class="col-md-4">
                        <small><i class="fas fa-book me-1"></i> <?php echo $assignment['subject_name']; ?></small>
                      </div>
                      <div class="col-md-4">
                        <small class="text-<?php echo $is_overdue ? 'danger' : 'warning'; ?>">
                          <i class="fas fa-calendar-alt me-1"></i> Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                        </small>
                      </div>
                    </div>
                    <?php if ($assignment['attachment']): ?>
                      <div class="mt-2">
                        <a href="../<?php echo $assignment['attachment']; ?>" class="btn btn-sm btn-outline-info" target="_blank">
                          <i class="fas fa-paperclip me-1"></i> View Attachment
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="ms-3">
                    <a href="grade_assignments.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-success mb-2 w-100">
                      <i class="fas fa-check-circle"></i> Grade
                    </a>
                    <a href="?delete=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm w-100">
                      <i class="fas fa-trash"></i> Delete
                    </a>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-5">
              <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
              <p class="text-muted">No assignments created yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>