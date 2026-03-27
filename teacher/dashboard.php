<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'Teacher Dashboard';
$user_id = $_SESSION['user_id'];

// Get assigned classes
$query = "SELECT c.*, COUNT(DISTINCT s.id) as student_count 
          FROM classes c 
          LEFT JOIN students s ON s.class_id = c.id AND s.status = 'active'
          WHERE c.class_teacher_id = $user_id 
          GROUP BY c.id";
$assigned_classes = mysqli_query($conn, $query);

// Get today's classes
$today = date('Y-m-d');
$query = "SELECT cs.*, c.class_name, s.subject_name 
          FROM class_subjects cs 
          JOIN classes c ON cs.class_id = c.id 
          JOIN subjects s ON cs.subject_id = s.id 
          WHERE cs.teacher_id = $user_id";
$subjects = mysqli_query($conn, $query);

// Get pending assignments count
$query = "SELECT COUNT(*) as total 
          FROM assignments a 
          WHERE a.created_by = $user_id 
          AND a.due_date >= CURDATE()";
$result = mysqli_query($conn, $query);
$pending_assignments = mysqli_fetch_assoc($result)['total'];

include '../includes/header.php';
?>

<div class="row">
  <div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
              Assigned Classes</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo mysqli_num_rows($assigned_classes); ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-chalkboard fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
              Subjects Taught</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo mysqli_num_rows($subjects); ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-book fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
              Pending Assignments</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_assignments; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-tasks fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-6">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">My Assigned Classes</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Class Name</th>
                <th>Section</th>
                <th>Students</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($class = mysqli_fetch_assoc($assigned_classes)): ?>
                <tr>
                  <td><?php echo $class['class_name']; ?></td>
                  <td><?php echo $class['section']; ?></td>
                  <td><?php echo $class['student_count']; ?></td>
                  <td>
                    <a href="view_students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye"></i> View
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

  <div class="col-lg-6">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">My Subjects</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Class</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                <tr>
                  <td><?php echo $subject['subject_name']; ?></td>
                  <td><?php echo $subject['class_name']; ?></td>
                  <td>
                    <a href="upload_marks.php?subject_id=<?php echo $subject['subject_id']; ?>&class_id=<?php echo $subject['class_id']; ?>" class="btn btn-sm btn-success">
                      <i class="fas fa-upload"></i> Marks
                    </a>
                    <a href="mark_attendance.php?class_id=<?php echo $subject['class_id']; ?>" class="btn btn-sm btn-info">
                      <i class="fas fa-check"></i> Attendance
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
</div>

<div class="row">
  <div class="col-12">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 mb-3">
            <a href="mark_attendance.php" class="btn btn-primary w-100">
              <i class="fas fa-check-circle"></i> Mark Attendance
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="upload_marks.php" class="btn btn-success w-100">
              <i class="fas fa-upload"></i> Upload Marks
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="manage_assignments.php" class="btn btn-info w-100">
              <i class="fas fa-tasks"></i> Manage Assignments
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="report_cards.php" class="btn btn-warning w-100">
              <i class="fas fa-id-card"></i> Generate Report Cards
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>