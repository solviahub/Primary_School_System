<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Manage Teachers';

// Handle teacher deletion (soft delete - set status to inactive)
if (isset($_GET['delete'])) {
  $teacher_id = (int)$_GET['delete'];
  $query = "UPDATE users SET status = 'inactive' WHERE id = $teacher_id AND role = 'teacher'";

  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Teacher deactivated successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deactivating teacher!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/manage_teachers.php');
}

// Handle teacher restoration (activate again)
if (isset($_GET['activate'])) {
  $teacher_id = (int)$_GET['activate'];
  $query = "UPDATE users SET status = 'active' WHERE id = $teacher_id AND role = 'teacher'";

  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Teacher activated successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error activating teacher!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/manage_teachers.php');
}

// Handle permanent delete
if (isset($_GET['permanent_delete'])) {
  $teacher_id = (int)$_GET['permanent_delete'];

  // Check if teacher has any assignments
  $check_query = "SELECT COUNT(*) as count FROM class_subjects WHERE teacher_id = $teacher_id";
  $check_result = mysqli_query($conn, $check_query);
  $assignments = mysqli_fetch_assoc($check_result);

  if ($assignments['count'] > 0) {
    $_SESSION['message'] = 'Cannot delete teacher! Remove all subject assignments first.';
    $_SESSION['message_type'] = 'danger';
  } else {
    // Delete user
    $query = "DELETE FROM users WHERE id = $teacher_id AND role = 'teacher'";
    if (mysqli_query($conn, $query)) {
      $_SESSION['message'] = 'Teacher permanently deleted!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error deleting teacher!';
      $_SESSION['message_type'] = 'danger';
    }
  }
  redirect('admin/manage_teachers.php');
}

// Handle teacher creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  $full_name = sanitize($_POST['full_name']);
  $email = sanitize($_POST['email']);
  $username = sanitize($_POST['username']);
  $phone = sanitize($_POST['phone']);
  $address = sanitize($_POST['address']);

  if ($action == 'create') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
      $_SESSION['message'] = 'Username or email already exists!';
      $_SESSION['message_type'] = 'danger';
    } else {
      $query = "INSERT INTO users (username, password, email, full_name, role, phone, address, status) 
                      VALUES ('$username', '$password', '$email', '$full_name', 'teacher', '$phone', '$address', 'active')";

      if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Teacher created successfully! Username: $username, Password: {$_POST['password']}";
        $_SESSION['message_type'] = 'success';
      } else {
        $_SESSION['message'] = 'Error creating teacher: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
      }
    }
  } elseif ($action == 'edit') {
    $teacher_id = (int)$_POST['teacher_id'];
    $query = "UPDATE users SET full_name='$full_name', email='$email', username='$username', phone='$phone', address='$address' 
                  WHERE id=$teacher_id AND role='teacher'";

    if (mysqli_query($conn, $query)) {
      $_SESSION['message'] = 'Teacher updated successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error updating teacher!';
      $_SESSION['message_type'] = 'danger';
    }
  }

  redirect('admin/manage_teachers.php');
}

// Get all teachers (including inactive)
$teachers_query = "SELECT u.*, 
                  (SELECT COUNT(*) FROM class_subjects WHERE teacher_id = u.id) as subject_count,
                  (SELECT COUNT(DISTINCT class_id) FROM class_subjects WHERE teacher_id = u.id) as class_count
                  FROM users u 
                  WHERE u.role = 'teacher' 
                  ORDER BY u.status DESC, u.full_name";
$teachers = mysqli_query($conn, $teachers_query);

// Get statistics
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='teacher'"))['count'];
$active_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='teacher' AND status='active'"))['count'];
$inactive_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='teacher' AND status='inactive'"))['count'];

include '../includes/header.php';
?>

<style>
  .teacher-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .teacher-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.15);
  }

  .teacher-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    margin: 0 auto 15px;
  }

  .stat-box {
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #3b82f6;
  }

  .stat-box h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
    color: #3b82f6;
  }

  .stat-box p {
    margin: 0;
    color: #6c757d;
  }

  .table-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
  }

  .teacher-avatar-sm {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 10px;
  }
</style>

<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 15px; padding: 2rem;">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="text-white">Manage Teachers</h2>
          <p class="text-white-50 mb-0">Add, edit, and manage all teacher accounts in the system.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-chalkboard-user" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Statistics Row -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="stat-box">
      <h3><?php echo $total_teachers; ?></h3>
      <p>Total Teachers</p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-box">
      <h3 class="text-success"><?php echo $active_teachers; ?></h3>
      <p>Active Teachers</p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-box">
      <h3 class="text-danger"><?php echo $inactive_teachers; ?></h3>
      <p>Inactive Teachers</p>
    </div>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-12">
    <button type="button" class="btn" style="background-color: #3b82f6; color: white;" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
      <i class="fas fa-plus me-2"></i>Add New Teacher
    </button>
    <button type="button" class="btn btn-outline-secondary ms-2" onclick="window.print()">
      <i class="fas fa-print me-2"></i>Print List
    </button>
  </div>
</div>

<!-- Teachers Table -->
<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 datatable">
        <thead style="background-color: #3b82f6; color: white;">
          <tr>
            <th>ID</th>
            <th>Teacher</th>
            <th>Contact Info</th>
            <th>Username</th>
            <th>Classes</th>
            <th>Subjects</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($teachers) > 0): ?>
            <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
              <tr>
                <td><?php echo $teacher['id']; ?></td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="teacher-avatar-sm">
                      <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                      <strong><?php echo htmlspecialchars($teacher['full_name']); ?></strong>
                    </div>
                  </div>
                </td>
                <td>
                  <small>
                    <i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($teacher['email']); ?><br>
                    <i class="fas fa-phone text-muted"></i> <?php echo htmlspecialchars($teacher['phone']) ?: 'N/A'; ?>
                  </small>
                </td>
                <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                <td>
                  <span class="badge bg-info"><?php echo $teacher['class_count']; ?> Classes</span>
                </td>
                <td>
                  <span class="badge bg-primary"><?php echo $teacher['subject_count']; ?> Subjects</span>
                </td>
                <td>
                  <?php if ($teacher['status'] == 'active'): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="table-actions">
                    <button class="btn btn-sm btn-outline-primary edit-teacher"
                      data-id="<?php echo $teacher['id']; ?>"
                      data-fullname="<?php echo htmlspecialchars($teacher['full_name']); ?>"
                      data-username="<?php echo htmlspecialchars($teacher['username']); ?>"
                      data-email="<?php echo htmlspecialchars($teacher['email']); ?>"
                      data-phone="<?php echo htmlspecialchars($teacher['phone']); ?>"
                      data-address="<?php echo htmlspecialchars($teacher['address']); ?>">
                      <i class="fas fa-edit"></i>
                    </button>

                    <?php if ($teacher['status'] == 'active'): ?>
                      <a href="?delete=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-warning delete-confirm" title="Deactivate">
                        <i class="fas fa-ban"></i>
                      </a>
                    <?php else: ?>
                      <a href="?activate=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-success" title="Activate">
                        <i class="fas fa-check-circle"></i>
                      </a>
                      <a href="?permanent_delete=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm" title="Permanently Delete">
                        <i class="fas fa-trash"></i>
                      </a>
                    <?php endif; ?>

                    <button class="btn btn-sm btn-outline-info view-subjects-btn"
                      data-teacher-id="<?php echo $teacher['id']; ?>"
                      data-teacher-name="<?php echo htmlspecialchars($teacher['full_name']); ?>">
                      <i class="fas fa-book"></i> Subjects
                    </button>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4">
                <i class="fas fa-chalkboard-user fa-3x text-muted mb-3 d-block"></i>
                No teachers found. Click "Add New Teacher" to create one.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white;">
          <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Teacher</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="create">

          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Login credentials will be generated. Share them with the teacher.
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name *</label>
            <input type="text" class="form-control" name="full_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Username *</label>
            <input type="text" class="form-control" name="username" required>
            <small class="text-muted">Unique username for login</small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email *</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Password *</label>
            <input type="text" class="form-control" name="password" value="teacher123" required>
            <small class="text-muted">Default: teacher123 (Teacher can change after login)</small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone</label>
            <input type="text" class="form-control" name="phone">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Address</label>
            <textarea class="form-control" name="address" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn" style="background-color: #3b82f6; color: white;">Create Teacher</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white;">
          <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Teacher</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="teacher_id" id="edit_teacher_id">

          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name *</label>
            <input type="text" class="form-control" name="full_name" id="edit_fullname" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Username *</label>
            <input type="text" class="form-control" name="username" id="edit_username" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email *</label>
            <input type="email" class="form-control" name="email" id="edit_email" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone</label>
            <input type="text" class="form-control" name="phone" id="edit_phone">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Address</label>
            <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn" style="background-color: #3b82f6; color: white;">Update Teacher</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Subjects Modal -->
<div class="modal fade" id="subjectsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white;">
        <h5 class="modal-title"><i class="fas fa-book me-2"></i>Teacher Subjects</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="subjectsModalBody">
        <div class="text-center py-3 text-muted">
          <i class="fas fa-info-circle me-2"></i>
          Select a teacher to view their subjects
        </div>
      </div>
      <div class="modal-footer">
        <a href="assign_subject_teacher.php" class="btn btn-primary">Manage Assignments</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Edit teacher button click
  document.querySelectorAll('.edit-teacher').forEach(button => {
    button.addEventListener('click', function() {
      document.getElementById('edit_teacher_id').value = this.dataset.id;
      document.getElementById('edit_fullname').value = this.dataset.fullname;
      document.getElementById('edit_username').value = this.dataset.username;
      document.getElementById('edit_email').value = this.dataset.email;
      document.getElementById('edit_phone').value = this.dataset.phone || '';
      document.getElementById('edit_address').value = this.dataset.address || '';

      new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
    });
  });

  // View subjects button click - Load subjects when modal is shown
  document.querySelectorAll('.view-subjects-btn').forEach(button => {
    button.addEventListener('click', function() {
      const teacherId = this.dataset.teacherId;
      const teacherName = this.dataset.teacherName;

      // Update modal title
      document.querySelector('#subjectsModal .modal-title').innerHTML =
        '<i class="fas fa-book me-2"></i>Subjects taught by ' + teacherName;

      // Show loading
      document.getElementById('subjectsModalBody').innerHTML =
        '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

      // Fetch subjects via AJAX
      fetch(`get_teacher_subjects.php?teacher_id=${teacherId}`)
        .then(response => response.text())
        .then(data => {
          document.getElementById('subjectsModalBody').innerHTML = data;
        })
        .catch(error => {
          document.getElementById('subjectsModalBody').innerHTML =
            '<div class="alert alert-danger m-3">Error loading subjects. Please try again.</div>';
          console.error('Error:', error);
        });

      // Show the modal
      new bootstrap.Modal(document.getElementById('subjectsModal')).show();
    });
  });
</script>

<?php include '../includes/footer.php'; ?>