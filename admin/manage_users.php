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
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  if ($action == 'create') {
    // Check if username already exists
    $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
      $_SESSION['message'] = 'Username or email already exists!';
      $_SESSION['message_type'] = 'danger';
    } else {
      $query = "INSERT INTO users (username, password, email, full_name, role, phone, address, status) 
                      VALUES ('$username', '$password', '$email', '$full_name', '$role', '$phone', '$address', 'active')";

      if (mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);

        // If role is student, create student record
        if ($role == 'student') {
          $admission_number = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
          $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : 'NULL';
          $date_of_birth = sanitize($_POST['date_of_birth']);
          $gender = sanitize($_POST['gender']);
          $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 'NULL';

          $query2 = "INSERT INTO students (user_id, admission_number, class_id, parent_id, date_of_birth, gender, enrollment_date) 
                              VALUES ($user_id, '$admission_number', $class_id, $parent_id, '$date_of_birth', '$gender', CURDATE())";
          mysqli_query($conn, $query2);
        }

        // Send login credentials to user via email (optional)
        // mail($email, "Your School Management System Login Credentials", "Username: $username\nPassword: {$_POST['password']}\nLogin at: " . SITE_URL);

        $_SESSION['message'] = "User created successfully! Username: $username, Password: {$_POST['password']}";
        $_SESSION['message_type'] = 'success';
      } else {
        $_SESSION['message'] = 'Error creating user: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
      }
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

// Get all users with their details
$query = "SELECT u.*, 
          CASE 
              WHEN u.role = 'student' THEN s.admission_number 
              ELSE NULL 
          END as admission_number,
          CASE 
              WHEN u.role = 'student' THEN c.class_name 
              ELSE NULL 
          END as class_name
          FROM users u 
          LEFT JOIN students s ON u.id = s.user_id 
          LEFT JOIN classes c ON s.class_id = c.id 
          ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $query);

// Get classes for dropdown
$classes = mysqli_query($conn, "SELECT * FROM classes WHERE status = 'active'");

// Get parents for dropdown
$parents = mysqli_query($conn, "SELECT id, full_name, email FROM users WHERE role = 'parent' AND status = 'active'");

include '../includes/header.php';
?>

<div class="row mb-3">
  <div class="col-md-12">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
      <i class="fas fa-plus me-2"></i>Add New User
    </button>
    <button type="button" class="btn btn-info" onclick="window.print()">
      <i class="fas fa-print me-2"></i>Print User List
    </button>
  </div>
</div>

<!-- User Statistics -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-user-tie"></i>
      </div>
      <div class="stat-title">Total Teachers</div>
      <?php
      $teacher_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='teacher'"));
      ?>
      <div class="stat-value"><?php echo $teacher_count['count']; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-user-friends"></i>
      </div>
      <div class="stat-title">Total Parents</div>
      <?php
      $parent_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='parent'"));
      ?>
      <div class="stat-value"><?php echo $parent_count['count']; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-user-graduate"></i>
      </div>
      <div class="stat-title">Total Students</div>
      <?php
      $student_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"));
      ?>
      <div class="stat-value"><?php echo $student_count['count']; ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-users"></i>
      </div>
      <div class="stat-title">Total Users</div>
      <?php
      $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"));
      ?>
      <div class="stat-value"><?php echo $total_users['count']; ?></div>
    </div>
  </div>
</div>

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
            <th>Details</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($user = mysqli_fetch_assoc($users)): ?>
            <tr>
              <td><?php echo $user['id']; ?></td>
              <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
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
                <?php if ($user['role'] == 'student'): ?>
                  <small>Adm: <?php echo $user['admission_number']; ?><br>
                    Class: <?php echo $user['class_name']; ?></small>
                <?php elseif ($user['role'] == 'parent'): ?>
                  <small>Parent Account</small>
                <?php elseif ($user['role'] == 'teacher'): ?>
                  <small>Teacher Account</small>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                  <?php echo ucfirst($user['status']); ?>
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-info edit-user" data-id="<?php echo $user['id']; ?>"
                  data-username="<?php echo htmlspecialchars($user['username']); ?>"
                  data-email="<?php echo htmlspecialchars($user['email']); ?>"
                  data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                  data-role="<?php echo $user['role']; ?>"
                  data-phone="<?php echo htmlspecialchars($user['phone']); ?>"
                  data-address="<?php echo htmlspecialchars($user['address']); ?>">
                  <i class="fas fa-edit"></i>
                </button>
                <?php if ($user['role'] != 'admin'): ?>
                  <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                    <i class="fas fa-trash"></i>
                  </a>
                <?php endif; ?>
                <button class="btn btn-sm btn-secondary" onclick="showCredentials('<?php echo $user['username']; ?>', '<?php echo $user['email']; ?>')">
                  <i class="fas fa-key"></i>
                </button>
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
      <form method="POST" action="">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="create">

          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Login credentials will be generated automatically. Make sure to share them with the user.
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name *</label>
              <input type="text" class="form-control" name="full_name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Username *</label>
              <input type="text" class="form-control" name="username" required>
              <small class="text-muted">Unique username for login</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email *</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Password *</label>
              <input type="text" class="form-control" name="password" value="password123" required>
              <small class="text-muted">Default: password123 (User should change after login)</small>
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
            <div id="studentFields" style="display: none;" class="row">
              <div class="col-md-12">
                <hr>
                <h6 class="mb-3">Student Information</h6>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Class *</label>
                <select class="form-select" name="class_id">
                  <option value="">Select Class</option>
                  <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                    <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name'] . ' ' . $class['section']; ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Parent/Guardian</label>
                <select class="form-select" name="parent_id">
                  <option value="">Select Parent</option>
                  <?php while ($parent = mysqli_fetch_assoc($parents)): ?>
                    <option value="<?php echo $parent['id']; ?>"><?php echo $parent['full_name']; ?> (<?php echo $parent['email']; ?>)</option>
                  <?php endwhile; ?>
                </select>
                <small class="text-muted">Link student to parent account</small>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="date_of_birth">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Gender</label>
                <select class="form-select" name="gender">
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>

            <!-- Teacher Specific Fields -->
            <div id="teacherFields" style="display: none;" class="row">
              <div class="col-md-12">
                <hr>
                <h6 class="mb-3">Teacher Information</h6>
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label">Qualification</label>
                <input type="text" class="form-control" name="qualification" placeholder="e.g., B.Ed, M.Sc">
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label">Specialization</label>
                <input type="text" class="form-control" name="specialization" placeholder="e.g., Mathematics, Science">
              </div>
            </div>

            <!-- Parent Specific Fields -->
            <div id="parentFields" style="display: none;" class="row">
              <div class="col-md-12">
                <hr>
                <h6 class="mb-3">Parent Information</h6>
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label">Occupation</label>
                <input type="text" class="form-control" name="occupation">
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label">Children (Will link after creation)</label>
                <select class="form-select" name="child_id">
                  <option value="">Select Child to Link</option>
                  <?php
                  $unlinked_students = mysqli_query($conn, "SELECT s.id, s.admission_number, u.full_name 
                                                                             FROM students s 
                                                                             JOIN users u ON s.user_id = u.id 
                                                                             WHERE s.parent_id IS NULL");
                  while ($student = mysqli_fetch_assoc($unlinked_students)):
                  ?>
                    <option value="<?php echo $student['id']; ?>"><?php echo $student['full_name'] . ' (' . $student['admission_number'] . ')'; ?></option>
                  <?php endwhile; ?>
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

<!-- Credentials Modal -->
<div class="modal fade" id="credentialsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-key me-2"></i>User Login Credentials</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          Share these credentials with the user. They can use them to login to the system.
        </div>
        <div class="card">
          <div class="card-body">
            <p><strong>Login URL:</strong> <code><?php echo SITE_URL; ?>login.php</code></p>
            <p><strong>Username:</strong> <code id="cred_username"></code></p>
            <p><strong>Password:</strong> <code id="cred_password">password123</code></p>
            <hr>
            <p class="text-muted small mb-0">
              <i class="fas fa-exclamation-triangle me-1"></i>
              Users should change their password after first login for security.
            </p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" onclick="copyCredentials()">
          <i class="fas fa-copy me-2"></i>Copy Credentials
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Show/hide fields based on role selection
  document.getElementById('roleSelect').addEventListener('change', function() {
    const studentFields = document.getElementById('studentFields');
    const teacherFields = document.getElementById('teacherFields');
    const parentFields = document.getElementById('parentFields');

    // Hide all first
    studentFields.style.display = 'none';
    teacherFields.style.display = 'none';
    parentFields.style.display = 'none';

    // Show relevant fields
    if (this.value === 'student') {
      studentFields.style.display = 'flex';
      studentFields.style.flexWrap = 'wrap';
      studentFields.style.gap = '1rem';
    } else if (this.value === 'teacher') {
      teacherFields.style.display = 'flex';
      teacherFields.style.flexWrap = 'wrap';
      teacherFields.style.gap = '1rem';
    } else if (this.value === 'parent') {
      parentFields.style.display = 'flex';
      parentFields.style.flexWrap = 'wrap';
      parentFields.style.gap = '1rem';
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
      document.getElementById('edit_phone').value = this.dataset.phone || '';
      document.getElementById('edit_address').value = this.dataset.address || '';

      new bootstrap.Modal(document.getElementById('editUserModal')).show();
    });
  });

  // Show credentials
  let currentUsername = '';

  function showCredentials(username, email) {
    currentUsername = username;
    document.getElementById('cred_username').textContent = username;
    new bootstrap.Modal(document.getElementById('credentialsModal')).show();
  }

  // Copy credentials
  function copyCredentials() {
    const credentials = `Login URL: <?php echo SITE_URL; ?>login.php\nUsername: ${currentUsername}\nPassword: password123`;
    navigator.clipboard.writeText(credentials);
    alert('Credentials copied to clipboard!');
  }
</script>

<style>
  .stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
  }

  .stat-card .stat-icon {
    font-size: 2rem;
    margin-bottom: 10px;
  }

  .stat-card .stat-title {
    font-size: 0.9rem;
    opacity: 0.9;
  }

  .stat-card .stat-value {
    font-size: 2rem;
    font-weight: bold;
  }
</style>

<?php include '../includes/footer.php'; ?>