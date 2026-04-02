<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Bulk Upload Students';

// Include PHPExcel library (you need to download PHPExcel or PhpSpreadsheet)
// For this example, we'll use a simple CSV/Excel parsing approach

// Get classes for dropdown
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name, section");

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
  $file = $_FILES['excel_file'];
  $class_id = (int)$_POST['class_id'];
  $default_password = $_POST['default_password'];

  if ($file['error'] == 0) {
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (in_array(strtolower($file_extension), ['csv', 'xls', 'xlsx'])) {
      // Parse the file
      $students_data = [];
      $errors = [];
      $success_count = 0;

      if (strtolower($file_extension) == 'csv') {
        // Parse CSV
        if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
          $header = fgetcsv($handle); // Read header row

          while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 5) {
              $students_data[] = [
                'full_name' => trim($row[0]),
                'email' => trim($row[1]),
                'phone' => trim($row[2]),
                'date_of_birth' => trim($row[3]),
                'gender' => strtolower(trim($row[4])),
                'address' => isset($row[5]) ? trim($row[5]) : '',
                'parent_email' => isset($row[6]) ? trim($row[6]) : ''
              ];
            }
          }
          fclose($handle);
        }
      } else {
        // For Excel files, you would need PhpSpreadsheet library
        // This is a simplified version - recommend using PhpSpreadsheet
        $_SESSION['message'] = 'Please use CSV format for now. Excel files require additional library.';
        $_SESSION['message_type'] = 'danger';
        redirect('admin/bulk_upload_students.php');
      }

      // Process each student
      foreach ($students_data as $student) {
        // Validate required fields
        if (empty($student['full_name']) || empty($student['email']) || empty($student['date_of_birth'])) {
          $errors[] = "Missing required fields for: " . ($student['full_name'] ?? 'Unknown');
          continue;
        }

        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = '{$student['email']}'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
          $errors[] = "Email already exists: {$student['email']}";
          continue;
        }

        // Generate username
        $username = strtolower(str_replace(' ', '', $student['full_name'])) . rand(100, 999);
        $password_hash = password_hash($default_password, PASSWORD_DEFAULT);

        // Find parent ID by email if provided
        $parent_id = 'NULL';
        if (!empty($student['parent_email'])) {
          $parent_query = "SELECT id FROM users WHERE email = '{$student['parent_email']}' AND role = 'parent'";
          $parent_result = mysqli_query($conn, $parent_query);
          if ($parent_row = mysqli_fetch_assoc($parent_result)) {
            $parent_id = $parent_row['id'];
          }
        }

        // Create user account
        $query = "INSERT INTO users (username, password, email, full_name, role, phone, address, status) 
                          VALUES ('$username', '$password_hash', '{$student['email']}', '{$student['full_name']}', 'student', '{$student['phone']}', '{$student['address']}', 'active')";

        if (mysqli_query($conn, $query)) {
          $user_id = mysqli_insert_id($conn);

          // Generate admission number
          $admission_number = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);

          // Create student record
          $query2 = "INSERT INTO students (user_id, admission_number, class_id, parent_id, date_of_birth, gender, enrollment_date) 
                              VALUES ($user_id, '$admission_number', $class_id, $parent_id, '{$student['date_of_birth']}', '{$student['gender']}', CURDATE())";

          if (mysqli_query($conn, $query2)) {
            $success_count++;
          } else {
            $errors[] = "Failed to create student record for: {$student['full_name']}";
          }
        } else {
          $errors[] = "Failed to create user account for: {$student['full_name']}";
        }
      }

      // Set session message
      if ($success_count > 0) {
        $_SESSION['message'] = "$success_count students uploaded successfully!";
        $_SESSION['message_type'] = 'success';
      }
      if (!empty($errors)) {
        $_SESSION['bulk_errors'] = $errors;
      }

      logActivity($_SESSION['user_id'], 'Bulk uploaded students', "$success_count students added");
    } else {
      $_SESSION['message'] = 'Invalid file format. Please upload CSV, XLS, or XLSX file.';
      $_SESSION['message_type'] = 'danger';
    }
  } else {
    $_SESSION['message'] = 'Error uploading file!';
    $_SESSION['message_type'] = 'danger';
  }

  redirect('admin/bulk_upload_students.php');
}

include '../includes/header.php';
?>

<style>
  .preview-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 1rem;
  }

  .sample-row {
    background: #f0f9ff;
  }

  .error-list {
    max-height: 300px;
    overflow-y: auto;
  }
</style>

<div class="row">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-file-excel me-2"></i>Bulk Upload Students
          </h6>
          <a href="manage_students.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Students
          </a>
        </div>
      </div>
      <div class="card-body">
        <!-- Upload Form -->
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Instructions:</strong>
          <ul class="mb-0 mt-2">
            <li>Download the sample template below to see the correct format</li>
            <li>First row should be headers (will be skipped)</li>
            <li>Required columns: Full Name, Email, Date of Birth (YYYY-MM-DD), Gender</li>
            <li>Optional columns: Phone, Address, Parent Email</li>
            <li>Gender must be: male, female, or other</li>
            <li>Default password will be set for all uploaded students</li>
          </ul>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" class="mb-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Select Class *</label>
              <select class="form-select" name="class_id" required>
                <option value="">-- Select Class --</option>
                <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                  <option value="<?php echo $class['id']; ?>">
                    <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Default Password *</label>
              <input type="text" class="form-control" name="default_password" value="password123" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Excel/CSV File *</label>
              <input type="file" class="form-control" name="excel_file" accept=".csv,.xls,.xlsx" required>
            </div>
            <div class="col-md-12">
              <button type="submit" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-upload me-2"></i>Upload & Process
              </button>
              <a href="download_sample.php" class="btn btn-outline-success ms-2">
                <i class="fas fa-download me-2"></i>Download Sample Template
              </a>
            </div>
          </div>
        </form>

        <!-- Sample Preview -->
        <div class="card mt-4">
          <div class="card-header bg-white">
            <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
              <i class="fas fa-eye me-2"></i>Sample File Format Preview
            </h6>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <thead style="background-color: #3b82f6; color: white;">
                  <tr>
                    <th>Full Name *</th>
                    <th>Email *</th>
                    <th>Phone</th>
                    <th>Date of Birth *</th>
                    <th>Gender *</th>
                    <th>Address</th>
                    <th>Parent Email</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="sample-row">
                    <td>John Doe</td>
                    <td>john.doe@example.com</td>
                    <td>+1234567890</td>
                    <td>2010-05-15</td>
                    <td>male</td>
                    <td>123 Main Street, City</td>
                    <td>parent@example.com</td>
                  </tr>
                  <tr>
                    <td>Jane Smith</td>
                    <td>jane.smith@example.com</td>
                    <td>+1987654321</td>
                    <td>2010-08-20</td>
                    <td>female</td>
                    <td>456 Oak Avenue, City</td>
                    <td></td>
                  </tr>
                  <tr class="sample-row">
                    <td>Alex Johnson</td>
                    <td>alex.johnson@example.com</td>
                    <td></td>
                    <td>2011-03-10</td>
                    <td>other</td>
                    <td></td>
                    <td>parent2@example.com</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer bg-white">
            <small class="text-muted">
              <i class="fas fa-star-of-life text-danger me-1"></i> Required fields
            </small>
          </div>
        </div>

        <!-- Display Errors if any -->
        <?php if (isset($_SESSION['bulk_errors']) && !empty($_SESSION['bulk_errors'])): ?>
          <div class="card mt-4 border-danger">
            <div class="card-header bg-danger text-white">
              <h6 class="mb-0 fw-bold">
                <i class="fas fa-exclamation-triangle me-2"></i>Upload Errors (<?php echo count($_SESSION['bulk_errors']); ?>)
              </h6>
            </div>
            <div class="card-body error-list">
              <ul class="mb-0">
                <?php foreach ($_SESSION['bulk_errors'] as $error): ?>
                  <li class="text-danger"><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <?php unset($_SESSION['bulk_errors']); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>