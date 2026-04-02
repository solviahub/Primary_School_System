<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Add New Student';

// Get classes for dropdown
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name, section");

// Get parents for dropdown
$parents = mysqli_query($conn, "SELECT id, full_name, email FROM users WHERE role = 'parent' AND status = 'active' ORDER BY full_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
  $full_name = sanitize($_POST['full_name']);
  $email = sanitize($_POST['email']);
  $phone = sanitize($_POST['phone']);
  $address = sanitize($_POST['address']);
  $date_of_birth = sanitize($_POST['date_of_birth']);
  $gender = sanitize($_POST['gender']);
  $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : 'NULL';
  $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 'NULL';
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  // Generate username
  $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);

  // Check if email already exists
  $check_query = "SELECT id FROM users WHERE email = '$email'";
  $check_result = mysqli_query($conn, $check_query);

  if (mysqli_num_rows($check_result) > 0) {
    $_SESSION['message'] = 'Email already exists!';
    $_SESSION['message_type'] = 'danger';
  } else {
    // Create user account
    $query = "INSERT INTO users (username, password, email, full_name, role, phone, address, status) 
                  VALUES ('$username', '$password', '$email', '$full_name', 'student', '$phone', '$address', 'active')";

    if (mysqli_query($conn, $query)) {
      $user_id = mysqli_insert_id($conn);

      // Generate admission number
      $admission_number = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);

      // Create student record
      $query2 = "INSERT INTO students (user_id, admission_number, class_id, parent_id, date_of_birth, gender, enrollment_date) 
                      VALUES ($user_id, '$admission_number', $class_id, $parent_id, '$date_of_birth', '$gender', CURDATE())";

      if (mysqli_query($conn, $query2)) {
        logActivity($_SESSION['user_id'], 'Added new student', "Student: $full_name, Admission: $admission_number");
        $_SESSION['message'] = "Student added successfully! Admission Number: $admission_number";
        $_SESSION['message_type'] = 'success';
        redirect('admin/manage_students.php');
      } else {
        $_SESSION['message'] = 'Error creating student record!';
        $_SESSION['message_type'] = 'danger';
      }
    } else {
      $_SESSION['message'] = 'Error creating user account: ' . mysqli_error($conn);
      $_SESSION['message_type'] = 'danger';
    }
  }
}

include '../includes/header.php';
?>

<style>
  .form-section {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .form-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #3b82f6;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
  }
</style>

<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-user-plus me-2"></i>Add New Student
          </h6>
          <a href="manage_students.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Students
          </a>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <div class="form-section">
            <div class="form-section-title">
              <i class="fas fa-user me-2"></i>Personal Information
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Full Name *</label>
                <input type="text" class="form-control" name="full_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Email *</label>
                <input type="email" class="form-control" name="email" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Phone</label>
                <input type="text" class="form-control" name="phone">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Gender *</label>
                <select class="form-select" name="gender" required>
                  <option value="">Select Gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Date of Birth *</label>
                <input type="date" class="form-control" name="date_of_birth" required>
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label fw-semibold">Address</label>
                <textarea class="form-control" name="address" rows="2"></textarea>
              </div>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title">
              <i class="fas fa-graduation-cap me-2"></i>Academic Information
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Class *</label>
                <select class="form-select" name="class_id" required>
                  <option value="">Select Class</option>
                  <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                    <option value="<?php echo $class['id']; ?>">
                      <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Parent/Guardian</label>
                <select class="form-select" name="parent_id">
                  <option value="">Select Parent</option>
                  <?php while ($parent = mysqli_fetch_assoc($parents)): ?>
                    <option value="<?php echo $parent['id']; ?>">
                      <?php echo $parent['full_name'] . ' (' . $parent['email'] . ')'; ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title">
              <i class="fas fa-lock me-2"></i>Account Information
            </div>
            <div class="row">
              <div class="col-md-12 mb-3">
                <label class="form-label fw-semibold">Password *</label>
                <input type="password" class="form-control" name="password" value="password123" required>
                <small class="text-muted">Default password: password123 (User can change after login)</small>
              </div>
            </div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" name="add_student" class="btn" style="background-color: #3b82f6; color: white;">
              <i class="fas fa-save me-2"></i>Add Student
            </button>
            <a href="bulk_upload_students.php" class="btn btn-outline-primary">
              <i class="fas fa-file-excel me-2"></i>Bulk Upload via Excel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>