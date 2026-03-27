<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['student']);

$page_title = 'Student Dashboard';
$user_id = $_SESSION['user_id'];

// Get student details
$query = "SELECT s.*, c.class_name 
          FROM students s 
          JOIN classes c ON s.class_id = c.id 
          WHERE s.user_id = $user_id";
$student = mysqli_query($conn, $query);
$student_data = mysqli_fetch_assoc($student);

// Get latest results
$query = "SELECT m.*, sub.subject_name 
          FROM marks m 
          JOIN subjects sub ON m.subject_id = sub.id 
          WHERE m.student_id = {$student_data['id']} 
          ORDER BY m.created_at DESC LIMIT 5";
$latest_marks = mysqli_query($conn, $query);

// Get attendance summary
$query = "SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
            COUNT(*) as total
          FROM attendance 
          WHERE student_id = {$student_data['id']}";
$attendance = mysqli_fetch_assoc(mysqli_query($conn, $query));

// Get assignments
$query = "SELECT a.*, s.subject_name 
          FROM assignments a 
          JOIN class_subjects cs ON a.class_id = cs.class_id AND a.subject_id = cs.subject_id
          JOIN subjects s ON a.subject_id = s.id
          WHERE a.class_id = {$student_data['class_id']} 
          ORDER BY a.due_date ASC LIMIT 5";
$assignments = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
      </div>
      <div class="card-body text-center">
        <i class="fas fa-user-graduate fa-4x text-primary mb-3"></i>
        <h5><?php echo $student_data['admission_number']; ?></h5>
        <p class="text-muted">Admission Number</p>
        <hr>
        <p><strong>Class:</strong> <?php echo $student_data['class_name']; ?></p>
        <p><strong>Enrollment Date:</strong> <?php echo date('M d, Y', strtotime($student_data['enrollment_date'])); ?></p>
        <p><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($student_data['date_of_birth'])); ?></p>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                  Attendance Rate</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                  <?php
                  $attendance_rate = $attendance['total'] > 0 ? round(($attendance['present'] / $attendance['total']) * 100) : 0;
                  echo $attendance_rate . '%';
                  ?>
                </div>
              </div>
              <div class="col-auto">
                <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                  Total Assignments</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                  <?php echo mysqli_num_rows($assignments); ?>
                </div>
              </div>
              <div class="col-auto">
                <i class="fas fa-tasks fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Latest Results</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Exam Type</th>
                <th>Marks Obtained</th>
                <th>Max Marks</th>
                <th>Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($mark = mysqli_fetch_assoc($latest_marks)): ?>
                <tr>
                  <td><?php echo $mark['subject_name']; ?></td>
                  <td><?php echo ucfirst($mark['exam_type']); ?></td>
                  <td><?php echo $mark['marks_obtained']; ?></td>
                  <td><?php echo $mark['max_marks']; ?></td>
                  <td>
                    <?php
                    $percentage = ($mark['marks_obtained'] / $mark['max_marks']) * 100;
                    $badge_class = $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'info' : ($percentage >= 40 ? 'warning' : 'danger'));
                    ?>
                    <span class="badge bg-<?php echo $badge_class; ?>">
                      <?php echo round($percentage, 2); ?>%
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Upcoming Assignments</h6>
      </div>
      <div class="card-body">
        <div class="list-group">
          <?php while ($assignment = mysqli_fetch_assoc($assignments)): ?>
            <div class="list-group-item">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                <small class="text-danger">Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></small>
              </div>
              <p class="mb-1">Subject: <?php echo $assignment['subject_name']; ?></p>
              <small><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)); ?></small>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>