<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'My Classes';
$teacher_id = $_SESSION['user_id'];

// Get teacher's assigned classes with detailed info
$classes_query = "SELECT DISTINCT 
                  c.id,
                  c.class_name,
                  c.section,
                  c.academic_year,
                  c.capacity,
                  u.full_name as class_teacher_name,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as total_students,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active' AND gender = 'male') as male_students,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active' AND gender = 'female') as female_students,
                  (SELECT COUNT(*) FROM class_subjects WHERE class_id = c.id) as total_subjects
                  FROM class_subjects cs
                  INNER JOIN classes c ON cs.class_id = c.id
                  LEFT JOIN users u ON c.class_teacher_id = u.id
                  WHERE cs.teacher_id = $teacher_id
                  ORDER BY c.class_name, c.section";

$classes = mysqli_query($conn, $classes_query);

// Get selected class for detailed view
$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_class = null;
$students_in_class = [];
$teacher_subjects = [];

if ($selected_class_id > 0) {
  // Get class details - with null check
  $class_detail_query = "SELECT c.*, u.full_name as class_teacher_name 
                          FROM classes c 
                          LEFT JOIN users u ON c.class_teacher_id = u.id 
                          WHERE c.id = $selected_class_id";
  $class_detail_result = mysqli_query($conn, $class_detail_query);

  if (mysqli_num_rows($class_detail_result) > 0) {
    $selected_class = mysqli_fetch_assoc($class_detail_result);

    // Get students in this class
    $students_query = "SELECT s.*, u.full_name, u.email, u.phone 
                          FROM students s 
                          JOIN users u ON s.user_id = u.id 
                          WHERE s.class_id = $selected_class_id AND s.status = 'active' 
                          ORDER BY u.full_name";
    $students_in_class = mysqli_query($conn, $students_query);

    // Get subjects taught in this class by this teacher
    $subjects_query = "SELECT s.*, cs.teacher_id, u.full_name as teacher_name 
                          FROM subjects s 
                          JOIN class_subjects cs ON s.id = cs.subject_id 
                          LEFT JOIN users u ON cs.teacher_id = u.id 
                          WHERE cs.class_id = $selected_class_id AND cs.teacher_id = $teacher_id";
    $teacher_subjects = mysqli_query($conn, $subjects_query);
  }
}

include '../includes/header.php';
?>

<style>
  .class-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .class-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.15);
  }

  .class-card .card-header {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    color: white;
    padding: 1rem;
  }

  .class-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
  }

  .class-stat-item {
    text-align: center;
    flex: 1;
  }

  .class-stat-value {
    font-size: 1.25rem;
    font-weight: bold;
    color: #3b82f6;
  }

  .class-stat-label {
    font-size: 0.7rem;
    color: #6c757d;
    text-transform: uppercase;
  }

  .student-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
  }

  .progress {
    height: 8px;
    border-radius: 4px;
  }

  .progress-bar {
    background-color: #3b82f6;
  }

  .alert-info {
    background-color: #e6f2ff;
    border-color: #3b82f6;
    color: #1e40af;
  }

  .welcome-card {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
  }
</style>

<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-card">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="text-white">My Classes</h2>
          <p class="text-white-50 mb-0">Manage your classes, view students, and access class-specific actions.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-chalkboard" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Classes Grid -->
<div class="row">
  <?php if (mysqli_num_rows($classes) > 0): ?>
    <?php while ($class = mysqli_fetch_assoc($classes)): ?>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card class-card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?></h6>
              <span class="badge bg-light text-dark"><?php echo $class['total_students']; ?> Students</span>
            </div>
          </div>
          <div class="card-body">
            <?php if (!empty($class['class_teacher_name'])): ?>
              <p class="mb-2">
                <i class="fas fa-user-tie me-2 text-primary"></i>
                <small>Class Teacher: <?php echo htmlspecialchars($class['class_teacher_name']); ?></small>
              </p>
            <?php endif; ?>
            <p class="mb-2">
              <i class="fas fa-book me-2 text-primary"></i>
              <small>Subjects: <?php echo $class['total_subjects']; ?></small>
            </p>
            <p class="mb-2">
              <i class="fas fa-calendar-alt me-2 text-primary"></i>
              <small>Academic Year: <?php echo htmlspecialchars($class['academic_year']); ?></small>
            </p>

            <div class="class-stats">
              <div class="class-stat-item">
                <div class="class-stat-value"><?php echo $class['male_students']; ?></div>
                <div class="class-stat-label">Boys</div>
              </div>
              <div class="class-stat-item">
                <div class="class-stat-value"><?php echo $class['female_students']; ?></div>
                <div class="class-stat-label">Girls</div>
              </div>
              <div class="class-stat-item">
                <div class="class-stat-value"><?php echo $class['capacity']; ?></div>
                <div class="class-stat-label">Capacity</div>
              </div>
            </div>

            <div class="mt-3">
              <div class="progress mb-2">
                <?php $fill_percent = $class['capacity'] > 0 ? ($class['total_students'] / $class['capacity']) * 100 : 0; ?>
                <div class="progress-bar" style="width: <?php echo $fill_percent; ?>%"></div>
              </div>
              <small class="text-muted"><?php echo round($fill_percent); ?>% Capacity Used</small>
            </div>
          </div>
          <div class="card-footer bg-white">
            <div class="d-grid gap-2">
              <a href="?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-eye me-1"></i> View Details
              </a>
              <div class="btn-group" role="group">
                <a href="view_students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="fas fa-users"></i> Students
                </a>
                <a href="mark_attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-success btn-sm">
                  <i class="fas fa-check-circle"></i> Attendance
                </a>
                <a href="upload_marks.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-info btn-sm">
                  <i class="fas fa-upload"></i> Marks
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-chalkboard fa-4x text-muted mb-3"></i>
          <h5>No Classes Assigned Yet</h5>
          <p class="text-muted">You haven't been assigned to any classes. Please contact the administrator.</p>
          <hr>
          <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>How to get assigned:</strong>
            <ol class="text-start mt-2 mb-0">
              <li>Administrator needs to go to <strong>Assign Subject Teachers</strong> page</li>
              <li>Select a class, subject, and your name</li>
              <li>Click "Assign Teacher"</li>
              <li>Refresh this page after assignment</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Detailed Class View -->
<?php if ($selected_class_id > 0 && $selected_class !== null): ?>
  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
              <i class="fas fa-info-circle me-2"></i>
              Class Details: <?php echo htmlspecialchars($selected_class['class_name'] . ' ' . $selected_class['section']); ?>
            </h6>
            <a href="view_classes.php" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-times"></i> Close
            </a>
          </div>
        </div>
        <div class="card-body">
          <!-- Class Information -->
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="bg-light p-3 rounded">
                <small class="text-muted">Class Teacher</small>
                <h6 class="mb-0"><?php echo !empty($selected_class['class_teacher_name']) ? htmlspecialchars($selected_class['class_teacher_name']) : 'Not Assigned'; ?></h6>
              </div>
            </div>
            <div class="col-md-3">
              <div class="bg-light p-3 rounded">
                <small class="text-muted">Academic Year</small>
                <h6 class="mb-0"><?php echo htmlspecialchars($selected_class['academic_year']); ?></h6>
              </div>
            </div>
            <div class="col-md-3">
              <div class="bg-light p-3 rounded">
                <small class="text-muted">Capacity</small>
                <h6 class="mb-0"><?php echo $selected_class['capacity']; ?> Students</h6>
              </div>
            </div>
            <div class="col-md-3">
              <div class="bg-light p-3 rounded">
                <small class="text-muted">Status</small>
                <h6 class="mb-0 text-success">Active</h6>
              </div>
            </div>
          </div>

          <!-- Subjects Taught -->
          <?php if (mysqli_num_rows($teacher_subjects) > 0): ?>
            <div class="mb-4">
              <h6 class="fw-bold mb-3" style="color: #3b82f6;">
                <i class="fas fa-book me-2"></i>Subjects I Teach in This Class
              </h6>
              <div class="row">
                <?php while ($subject = mysqli_fetch_assoc($teacher_subjects)): ?>
                  <div class="col-md-4 mb-2">
                    <div class="border rounded p-2">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong>
                          <br>
                          <small class="text-muted">Code: <?php echo htmlspecialchars($subject['subject_code']); ?></small>
                        </div>
                        <div>
                          <a href="upload_marks.php?class_id=<?php echo $selected_class_id; ?>&subject_id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload"></i> Marks
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Students Table -->
          <h6 class="fw-bold mb-3" style="color: #3b82f6;">
            <i class="fas fa-users me-2"></i>Students Enrolled
          </h6>
          <?php if (mysqli_num_rows($students_in_class) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover datatable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $count = 1;
                  while ($student = mysqli_fetch_assoc($students_in_class)):
                  ?>
                    <tr>
                      <td><?php echo $count++; ?></td>
                      <td><strong><?php echo htmlspecialchars($student['admission_number']); ?></strong></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="student-avatar me-2">
                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                          </div>
                          <?php echo htmlspecialchars($student['full_name']); ?>
                        </div>
                      </td>
                      <td><?php echo htmlspecialchars($student['email']); ?></td>
                      <td><?php echo htmlspecialchars($student['phone']); ?></td>
                      <td><?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A'; ?></td>
                      <td>
                        <span class="badge bg-<?php echo $student['gender'] == 'male' ? 'info' : 'danger'; ?>">
                          <?php echo ucfirst($student['gender']); ?>
                        </span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <a href="mark_attendance.php?class_id=<?php echo $selected_class_id; ?>&student_id=<?php echo $student['id']; ?>"
                            class="btn btn-outline-success" title="Mark Attendance">
                            <i class="fas fa-check-circle"></i>
                          </a>
                          <a href="upload_marks.php?class_id=<?php echo $selected_class_id; ?>&student_id=<?php echo $student['id']; ?>"
                            class="btn btn-outline-primary" title="Add Marks">
                            <i class="fas fa-upload"></i>
                          </a>
                          <a href="report_cards.php?class_id=<?php echo $selected_class_id; ?>&student_id=<?php echo $student['id']; ?>"
                            class="btn btn-outline-info" title="View Report Card">
                            <i class="fas fa-id-card"></i>
                          </a>
                        </div>
            </div>
            </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          No students enrolled in this class yet.
        </div>
      <?php endif; ?>
      </div>
    </div>
  </div>
  </div>

  <!-- Quick Actions for Selected Class -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-bolt me-2"></i>Quick Actions
          </h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <a href="mark_attendance.php?class_id=<?php echo $selected_class_id; ?>" class="btn btn-outline-primary w-100 py-3">
                <i class="fas fa-check-circle fa-2x d-block mb-2"></i>
                <strong>Mark Attendance</strong>
                <small class="d-block text-muted">Record today's attendance</small>
              </a>
            </div>
            <div class="col-md-3">
              <a href="upload_marks.php?class_id=<?php echo $selected_class_id; ?>" class="btn btn-outline-success w-100 py-3">
                <i class="fas fa-upload fa-2x d-block mb-2"></i>
                <strong>Upload Marks</strong>
                <small class="d-block text-muted">Add student grades</small>
              </a>
            </div>
            <div class="col-md-3">
              <a href="manage_assignments.php?class_id=<?php echo $selected_class_id; ?>" class="btn btn-outline-info w-100 py-3">
                <i class="fas fa-tasks fa-2x d-block mb-2"></i>
                <strong>Create Assignment</strong>
                <small class="d-block text-muted">Post new assignments</small>
              </a>
            </div>
            <div class="col-md-3">
              <a href="report_cards.php?class_id=<?php echo $selected_class_id; ?>" class="btn btn-outline-warning w-100 py-3">
                <i class="fas fa-id-card fa-2x d-block mb-2"></i>
                <strong>Report Cards</strong>
                <small class="d-block text-muted">Generate report cards</small>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php elseif ($selected_class_id > 0 && $selected_class === null): ?>
  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
          <h5>Class Not Found</h5>
          <p class="text-muted">The selected class does not exist or you don't have permission to view it.</p>
          <a href="view_classes.php" class="btn btn-primary">Back to My Classes</a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>