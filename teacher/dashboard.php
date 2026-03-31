<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'Teacher Dashboard';
$teacher_id = $_SESSION['user_id'];

// Get teacher's assigned classes
$classes_query = "SELECT DISTINCT c.*, 
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as student_count 
                  FROM classes c 
                  JOIN class_subjects cs ON c.id = cs.class_id 
                  WHERE cs.teacher_id = $teacher_id 
                  ORDER BY c.class_name";
$classes = mysqli_query($conn, $classes_query);
$total_classes = mysqli_num_rows($classes);

// Get teacher's subjects
$subjects_query = "SELECT DISTINCT s.* 
                   FROM subjects s 
                   JOIN class_subjects cs ON s.id = cs.subject_id 
                   WHERE cs.teacher_id = $teacher_id";
$subjects = mysqli_query($conn, $subjects_query);
$total_subjects = mysqli_num_rows($subjects);

// Get today's attendance count
$today = date('Y-m-d');
$attendance_query = "SELECT COUNT(*) as total 
                     FROM attendance 
                     WHERE marked_by = $teacher_id AND date = '$today'";
$attendance_result = mysqli_query($conn, $attendance_query);
$today_attendance = mysqli_fetch_assoc($attendance_result)['total'];

// Get pending assignments
$assignments_query = "SELECT COUNT(*) as total 
                      FROM assignments 
                      WHERE created_by = $teacher_id AND due_date >= CURDATE()";
$assignments_result = mysqli_query($conn, $assignments_query);
$pending_assignments = mysqli_fetch_assoc($assignments_result)['total'];

// Get recent activities
$activities_query = "SELECT * FROM activity_logs 
                     WHERE user_id = $teacher_id 
                     ORDER BY created_at DESC LIMIT 5";
$activities = mysqli_query($conn, $activities_query);

include '../includes/header.php';
?>

<!-- Welcome Section -->
<div class="welcome-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h2 class="text-white">Welcome back, <?php echo $_SESSION['user_name']; ?>!</h2>
      <p class="text-white-50 mb-0">Here's an overview of your teaching activities today.</p>
    </div>
    <div class="col-md-4 text-end">
      <i class="fas fa-chalkboard-user" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
    </div>
  </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-chalkboard"></i>
      </div>
      <div class="stat-title">My Classes</div>
      <div class="stat-value"><?php echo $total_classes; ?></div>
      <div class="stat-change">
        <i class="fas fa-check-circle text-success"></i> Active classes
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-book"></i>
      </div>
      <div class="stat-title">Subjects</div>
      <div class="stat-value"><?php echo $total_subjects; ?></div>
      <div class="stat-change">
        <i class="fas fa-graduation-cap text-success"></i> Teaching subjects
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="stat-title">Today's Attendance</div>
      <div class="stat-value"><?php echo $today_attendance; ?></div>
      <div class="stat-change">
        <i class="fas fa-calendar-day text-info"></i> Marked today
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-tasks"></i>
      </div>
      <div class="stat-title">Pending Assignments</div>
      <div class="stat-value"><?php echo $pending_assignments; ?></div>
      <div class="stat-change">
        <i class="fas fa-clock text-warning"></i> Awaiting grading
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- My Classes -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chalkboard me-2"></i>My Classes
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php if (mysqli_num_rows($classes) > 0): ?>
            <?php while ($class = mysqli_fetch_assoc($classes)): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?php echo $class['class_name'] . ' ' . $class['section']; ?></strong>
                    <br>
                    <small class="text-muted">Students: <?php echo $class['student_count']; ?></small>
                  </div>
                  <div>
                    <a href="view_students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                      <i class="fas fa-users"></i> View
                    </a>
                    <a href="mark_attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-success">
                      <i class="fas fa-check"></i> Attendance
                    </a>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-chalkboard fa-3x text-muted mb-3"></i>
              <p class="text-muted">No classes assigned yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- My Subjects -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-book me-2"></i>My Subjects
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php if (mysqli_num_rows($subjects) > 0): ?>
            <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?php echo $subject['subject_name']; ?></strong>
                    <br>
                    <small class="text-muted">Code: <?php echo $subject['subject_code']; ?></small>
                  </div>
                  <div>
                    <a href="upload_marks.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-upload"></i> Marks
                    </a>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-book fa-3x text-muted mb-3"></i>
              <p class="text-muted">No subjects assigned yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Recent Activities -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-history me-2"></i>Recent Activities
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php if (mysqli_num_rows($activities) > 0): ?>
            <?php while ($activity = mysqli_fetch_assoc($activities)): ?>
              <div class="list-group-item">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                  </div>
                  <div>
                    <p class="mb-0"><?php echo htmlspecialchars($activity['action']); ?></p>
                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <p class="text-muted">No recent activities</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-bolt me-2"></i>Quick Actions
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <a href="mark_attendance.php" class="btn btn-outline-primary w-100 py-3 text-start">
              <i class="fas fa-check-circle me-2"></i>
              <strong>Mark Attendance</strong>
              <small class="d-block text-muted">Record today's attendance</small>
            </a>
          </div>
          <div class="col-md-6">
            <a href="upload_marks.php" class="btn btn-outline-success w-100 py-3 text-start">
              <i class="fas fa-upload me-2"></i>
              <strong>Upload Marks</strong>
              <small class="d-block text-muted">Add student grades</small>
            </a>
          </div>
          <div class="col-md-6">
            <a href="manage_assignments.php" class="btn btn-outline-info w-100 py-3 text-start">
              <i class="fas fa-tasks me-2"></i>
              <strong>Manage Assignments</strong>
              <small class="d-block text-muted">Create and grade assignments</small>
            </a>
          </div>
          <div class="col-md-6">
            <a href="report_cards.php" class="btn btn-outline-warning w-100 py-3 text-start">
              <i class="fas fa-id-card me-2"></i>
              <strong>Report Cards</strong>
              <small class="d-block text-muted">Generate student report cards</small>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
  }

  .stat-card .stat-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 3rem;
    opacity: 0.1;
  }

  .stat-card .stat-title {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .stat-card .stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0;
  }

  .stat-card .stat-change {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    color: #6c757d;
  }

  .welcome-card {
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    color: white;
  }
</style>

<?php
function timeAgo($timestamp)
{
  $time_ago = strtotime($timestamp);
  $current_time = time();
  $time_difference = $current_time - $time_ago;
  $seconds = $time_difference;
  $minutes = round($seconds / 60);
  $hours = round($seconds / 3600);
  $days = round($seconds / 86400);
  $weeks = round($seconds / 604800);
  $months = round($seconds / 2629440);
  $years = round($seconds / 31553280);

  if ($seconds <= 60) {
    return "Just Now";
  } else if ($minutes <= 60) {
    return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
  } else if ($hours <= 24) {
    return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
  } else if ($days <= 7) {
    return ($days == 1) ? "yesterday" : "$days days ago";
  } else if ($weeks <= 4.3) {
    return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
  } else if ($months <= 12) {
    return ($months == 1) ? "1 month ago" : "$months months ago";
  } else {
    return ($years == 1) ? "1 year ago" : "$years years ago";
  }
}
?>

<?php include '../includes/footer.php'; ?>