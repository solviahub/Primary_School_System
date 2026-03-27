<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Manage Users';

// Handle user deletion
if (isset($_GET['delete'])) {
  $user_id = (int)$_GET['delete'];
  $query = "DELETE FROM users WHERE id = $user_id AND role != 'admin'";
  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'User deleted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deleting user!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/manage_users.php');
}

// Handle user creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  $username = sanitize($_POST['username']);
  $email = sanitize($_POST['email']);
  $full_name = sanitize($_POST['full_name']);
  $role = sanitize($_POST['role']);
  $phone = sanitize($_POST['phone']);
  $address = sanitize($_POST['address']);

  if ($action == 'create') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, password, email, full_name, role, phone, address, status) 
                  VALUES ('$username', '$password', '$email', '$full_name', '$role', '$phone', '$address', 'active')";

    if (mysqli_query($conn, $query)) {
      $user_id = mysqli_insert_id($conn);

      // If role is student, create student record
      if ($role == 'student') {
        $admission_number = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
        $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : 'NULL';
        $date_of_birth = !empty($_POST['date_of_birth']) ? "'" . sanitize($_POST['date_of_birth']) . "'" : 'NULL';
        $gender = !empty($_POST['gender']) ? "'" . sanitize($_POST['gender']) . "'" : 'NULL';
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 'NULL';

        $query2 = "INSERT INTO students (user_id, admission_number, class_id, parent_id, date_of_birth, gender, enrollment_date) 
                          VALUES ($user_id, '$admission_number', $class_id, $parent_id, $date_of_birth, $gender, CURDATE())";
        mysqli_query($conn, $query2);
      }

      $_SESSION['message'] = 'User created successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error creating user: ' . mysqli_error($conn);
      $_SESSION['message_type'] = 'danger';
    }
  } elseif ($action == 'edit') {
    $user_id = (int)$_POST['user_id'];
    $query = "UPDATE users SET username='$username', email='$email', full_name='$full_name', role='$role', phone='$phone', address='$address' 
                  WHERE id=$user_id";

    if (mysqli_query($conn, $query)) {
      $_SESSION['message'] = 'User updated successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error updating user!';
      $_SESSION['message_type'] = 'danger';
    }
  }

  redirect('admin/manage_users.php');
}

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$users = mysqli_query($conn, $query);

// FIXED: Removed WHERE status = 'active' since classes table doesn't have status column
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name, section");

// Get parents for dropdown - Check if status column exists, if not, remove the condition
// First, check if status column exists in users table
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
$has_status_column = mysqli_num_rows($check_column) > 0;

if ($has_status_column) {
    $parents = mysqli_query($conn, "SELECT * FROM users WHERE role = 'parent' AND status = 'active' ORDER BY full_name");
} else {
    $parents = mysqli_query($conn, "SELECT * FROM users WHERE role = 'parent' ORDER BY full_name");
}

include '../includes/header.php';
?>

<div class="row mb-3">
  <div class="col-md-12">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
      <i class="fas fa-plus me-2"></i>Add New User
    </button>
  </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
  <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
    <?php 
    echo $_SESSION['message'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h6 class="mb-0"><i class="fas fa-users me-2"></i>All Users</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover datatable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($user = mysqli_fetch_assoc($users)): ?>
            <tr>
              <td><?php echo $user['id']; ?></td>
              <td><?php echo htmlspecialchars($user['username']); ?></td>
              <td><?php echo htmlspecialchars($user['full_name']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td>
                <span class="badge bg-<?php
                                      echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'teacher' ? 'primary' : ($user['role'] == 'parent' ? 'success' : ($user['role'] == 'student' ? 'info' : 'warning')));
                                      ?>">
                  <?php echo ucfirst($user['role']); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($user['phone']); ?></td>
              <td>
                <?php if (isset($user['status'])): ?>
                  <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                    <?php echo ucfirst($user['status']); ?>
                  </span>
                <?php else: ?>
                  <span class="badge bg-success">Active</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm btn-info edit-user" data-id="<?php echo $user['id']; ?>"
                  data-username="<?php echo htmlspecialchars($user['username']); ?>"
                  data-email="<?php echo htmlspecialchars($user['email']); ?>"
                  data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                  data-role="<?php echo $user['role']; ?>"
                  data-phone="<?php echo htmlspecialchars($user['phone']); ?>"
                  data-address="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                  <i class="fas fa-edit"></i>
                </button>
                <?php if ($user['role'] != 'admin'): ?>
                  <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger delete-confirm" onclick="return confirm('Are you sure you want to delete this user?');">
                    <i class="fas fa-trash"></i>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="" onsubmit="return validateForm()">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="create">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Username *</label>
              <input type="text" class="form-control" name="username" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Password *</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-control" name="full_name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email *</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Role *</label>
              <select class="form-select" name="role" id="roleSelect" required>
                <option value="">Select Role</option>
                <option value="admin">Administrator</option>
                <option value="teacher">Teacher</option>
                <option value="parent">Parent</option>
                <option value="student">Student</option>
                <option value="librarian">Librarian</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" name="phone">
            </div>
            <div class="col-md-12 mb-3">
              <label class="form-label">Address</label>
              <textarea class="form-control" name="address" rows="2"></textarea>
            </div>

            <!-- Student Specific Fields -->
            <div id="studentFields" style="display: none;">
              <div class="col-md-6 mb-3">
                <label class="form-label">Class</label>
                <select class="form-select" name="class_id">
                  <option value="">Select Class</option>
                  <?php 
                  mysqli_data_seek($classes, 0);
                  while ($class = mysqli_fetch_assoc($classes)): 
                  ?>
                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name'] . ' ' . ($class['section'] ?? '')); ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Parent</label>
                <select class="form-select" name="parent_id">
                  <option value="">Select Parent</option>
                  <?php 
                  if ($parents && mysqli_num_rows($parents) > 0):
                    mysqli_data_seek($parents, 0);
                    while ($parent = mysqli_fetch_assoc($parents)): 
                  ?>
                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['full_name']); ?></option>
                  <?php endwhile; endif; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="date_of_birth">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Gender</label>
                <select class="form-select" name="gender">
                  <option value="">Select Gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="user_id" id="edit_user_id">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" id="edit_username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name" id="edit_fullname" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="edit_email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" id="edit_role" required>
              <option value="admin">Administrator</option>
              <option value="teacher">Teacher</option>
              <option value="parent">Parent</option>
              <option value="student">Student</option>
              <option value="librarian">Librarian</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" id="edit_phone">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Show/hide student fields based on role selection
  document.getElementById('roleSelect').addEventListener('change', function() {
    const studentFields = document.getElementById('studentFields');
    if (this.value === 'student') {
      studentFields.style.display = 'flex';
      studentFields.style.flexWrap = 'wrap';
      studentFields.style.gap = '1rem';
    } else {
      studentFields.style.display = 'none';
    }
  });

  // Edit user button click
  document.querySelectorAll('.edit-user').forEach(button => {
    button.addEventListener('click', function() {
      document.getElementById('edit_user_id').value = this.dataset.id;
      document.getElementById('edit_username').value = this.dataset.username;
      document.getElementById('edit_fullname').value = this.dataset.fullname;
      document.getElementById('edit_email').value = this.dataset.email;
      document.getElementById('edit_role').value = this.dataset.role;
      document.getElementById('edit_phone').value = this.dataset.phone;
      document.getElementById('edit_address').value = this.dataset.address;

      new bootstrap.Modal(document.getElementById('editUserModal')).show();
    });
  });

  // Form validation
  function validateForm() {
    const password = document.querySelector('input[name="password"]');
    if (password && password.value.length < 6) {
      alert('Password must be at least 6 characters long!');
      return false;
    }
    return true;
  }
</script>

<?php include '../includes/footer.php'; ?>