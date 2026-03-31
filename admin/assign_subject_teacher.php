<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Assign Subject Teachers';

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_teacher'])) {
  $class_id = (int)$_POST['class_id'];
  $subject_id = (int)$_POST['subject_id'];
  $teacher_id = (int)$_POST['teacher_id'];

  // Check if this subject is already assigned to this class
  $check_query = "SELECT id FROM class_subjects 
                    WHERE class_id = $class_id AND subject_id = $subject_id";
  $check_result = mysqli_query($conn, $check_query);

  if (mysqli_num_rows($check_result) > 0) {
    // Update existing assignment
    $query = "UPDATE class_subjects 
                  SET teacher_id = $teacher_id 
                  WHERE class_id = $class_id AND subject_id = $subject_id";
    $message = 'Teacher updated for this subject!';
  } else {
    // Create new assignment
    $query = "INSERT INTO class_subjects (class_id, subject_id, teacher_id) 
                  VALUES ($class_id, $subject_id, $teacher_id)";
    $message = 'Teacher assigned to subject successfully!';
  }

  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error assigning teacher: ' . mysqli_error($conn);
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/assign_subject_teacher.php');
}

// Handle removal of assignment
if (isset($_GET['remove'])) {
  $assignment_id = (int)$_GET['remove'];
  $query = "DELETE FROM class_subjects WHERE id = $assignment_id";
  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Teacher removed from subject successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error removing teacher!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/assign_subject_teacher.php');
}

// Get all data for dropdowns
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name, section");
$subjects = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");
$teachers = mysqli_query($conn, "SELECT id, full_name, email FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY full_name");

// Get current assignments with teacher names
$assignments = mysqli_query($conn, "SELECT cs.*, 
                                    c.class_name, 
                                    c.section, 
                                    s.subject_name, 
                                    s.subject_code,
                                    u.full_name as teacher_name,
                                    u.email as teacher_email
                                   FROM class_subjects cs 
                                   JOIN classes c ON cs.class_id = c.id 
                                   JOIN subjects s ON cs.subject_id = s.id 
                                   LEFT JOIN users u ON cs.teacher_id = u.id 
                                   ORDER BY c.class_name, s.subject_name");

include '../includes/header.php';
?>

<style>
  .assignment-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 10px;
    overflow: hidden;
  }

  .assignment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.1);
  }

  .subject-badge {
    background-color: #e6f2ff;
    color: #3b82f6;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 12px;
  }
</style>

<div class="row">
  <!-- Assignment Form -->
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-user-plus me-2"></i>Assign Teacher to Subject
        </h6>
        <small class="text-muted">Link a teacher to teach a specific subject in a class</small>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label fw-semibold">Select Class *</label>
            <select class="form-select" name="class_id" id="classSelect" required>
              <option value="">-- Select Class --</option>
              <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $class['id']; ?>">
                  <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Select Subject *</label>
            <select class="form-select" name="subject_id" required>
              <option value="">-- Select Subject --</option>
              <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                <option value="<?php echo $subject['id']; ?>">
                  <?php echo $subject['subject_name'] . ' (' . $subject['subject_code'] . ')'; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Select Teacher *</label>
            <select class="form-select" name="teacher_id" required>
              <option value="">-- Select Teacher --</option>
              <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo $teacher['id']; ?>">
                  <?php echo $teacher['full_name'] . ' (' . $teacher['email'] . ')'; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <small>This will allow the teacher to view and manage this subject for the selected class.</small>
          </div>

          <button type="submit" name="assign_teacher" class="btn w-100" style="background-color: #3b82f6; color: white;">
            <i class="fas fa-check-circle me-2"></i>Assign Teacher
          </button>
        </form>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3" style="color: #3b82f6;">Quick Stats</h6>
        <?php
        $total_assignments = mysqli_num_rows($assignments);
        $total_teachers = mysqli_num_rows($teachers);
        $total_classes = mysqli_num_rows($classes);
        ?>
        <div class="row text-center">
          <div class="col-4">
            <h4 class="text-primary"><?php echo $total_assignments; ?></h4>
            <small>Assignments</small>
          </div>
          <div class="col-4">
            <h4 class="text-success"><?php echo $total_teachers; ?></h4>
            <small>Teachers</small>
          </div>
          <div class="col-4">
            <h4 class="text-info"><?php echo $total_classes; ?></h4>
            <small>Classes</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Current Assignments List -->
  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-list me-2"></i>Current Subject-Teacher Assignments
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead style="background-color: #3b82f6; color: white;">
              <tr>
                <th>Class</th>
                <th>Subject</th>
                <th>Teacher</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($assignments) > 0): ?>
                <?php while ($assignment = mysqli_fetch_assoc($assignments)): ?>
                  <tr>
                    <td>
                      <strong><?php echo $assignment['class_name'] . ' ' . $assignment['section']; ?></strong>
                    </td>
                    <td>
                      <?php echo $assignment['subject_name']; ?>
                      <br>
                      <small class="text-muted"><?php echo $assignment['subject_code']; ?></small>
                    </td>
                    <td>
                      <?php if ($assignment['teacher_name']): ?>
                        <span class="badge bg-success"><?php echo $assignment['teacher_name']; ?></span>
                        <br>
                        <small><?php echo $assignment['teacher_email']; ?></small>
                      <?php else: ?>
                        <span class="badge bg-danger">Not Assigned</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($assignment['teacher_id']): ?>
                        <a href="?remove=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                          <i class="fas fa-trash"></i> Remove
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center py-4">
                    <i class="fas fa-info-circle me-2"></i>No assignments yet. Use the form to assign teachers.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Assignments by Class -->
    <div class="card shadow-sm mt-3">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chalkboard me-2"></i>Assignments by Class
        </h6>
      </div>
      <div class="card-body">
        <?php
        // Reset assignments pointer
        mysqli_data_seek($assignments, 0);
        $current_class = '';
        $class_assignments = [];
        while ($assignment = mysqli_fetch_assoc($assignments)) {
          $class_key = $assignment['class_name'] . ' ' . $assignment['section'];
          $class_assignments[$class_key][] = $assignment;
        }
        ?>

        <?php if (!empty($class_assignments)): ?>
          <?php foreach ($class_assignments as $class_name => $subjects): ?>
            <div class="mb-3">
              <h6 class="fw-bold mb-2"><?php echo $class_name; ?></h6>
              <div class="row">
                <?php foreach ($subjects as $subject): ?>
                  <div class="col-md-6 mb-2">
                    <div class="border rounded p-2">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <span class="subject-badge"><?php echo $subject['subject_name']; ?></span>
                        </div>
                        <div>
                          <?php if ($subject['teacher_name']): ?>
                            <small class="text-success">
                              <i class="fas fa-user-check"></i> <?php echo $subject['teacher_name']; ?>
                            </small>
                          <?php else: ?>
                            <small class="text-danger">
                              <i class="fas fa-user-times"></i> No teacher
                            </small>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-center">No assignments found</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>