<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['parent']);

$page_title = 'Parent Dashboard';
$user_id = $_SESSION['user_id'];

// Get children
$query = "SELECT s.*, c.class_name 
          FROM students s 
          JOIN classes c ON s.class_id = c.id 
          WHERE s.parent_id = $user_id AND s.status = 'active'";
$children = mysqli_query($conn, $query);

$child_count = mysqli_num_rows($children);

include '../includes/header.php';
?>

<div class="row">
  <div class="col-12">
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> Welcome to the Parent Portal! Here you can monitor your children's academic progress, attendance, and fee status.
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">My Children</h6>
      </div>
      <div class="card-body">
        <?php if ($child_count > 0): ?>
          <div class="list-group">
            <?php while ($child = mysqli_fetch_assoc($children)): ?>
              <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                  <h5 class="mb-1"><?php echo $child['admission_number']; ?></h5>
                  <small>Class: <?php echo $child['class_name']; ?></small>
                </div>
                <p class="mb-1">
                  <i class="fas fa-user"></i> <?php echo $child['admission_number']; ?><br>
                  <i class="fas fa-calendar"></i> Enrolled: <?php echo date('M d, Y', strtotime($child['enrollment_date'])); ?>
                </p>
                <div class="mt-2">
                  <a href="view_results.php?student_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-chart-line"></i> Results
                  </a>
                  <a href="view_attendance.php?student_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-info">
                    <i class="fas fa-calendar-check"></i> Attendance
                  </a>
                  <a href="fee_balance.php?student_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-credit-card"></i> Fee Balance
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-warning">
            No children linked to your account. Please contact the school administrator.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Recent Announcements</h6>
      </div>
      <div class="card-body">
        <?php
        $query = "SELECT * FROM announcements 
                          WHERE target_role IN ('all', 'parent') 
                          ORDER BY created_at DESC LIMIT 5";
        $announcements = mysqli_query($conn, $query);
        ?>
        <div class="list-group">
          <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
            <div class="list-group-item">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                <small><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
              </div>
              <p class="mb-1"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...</p>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-2">
            <a href="make_payment.php" class="btn btn-success w-100">
              <i class="fas fa-money-bill-wave"></i> Make Payment
            </a>
          </div>
          <div class="col-md-6 mb-2">
            <a href="view_assignments.php" class="btn btn-info w-100">
              <i class="fas fa-tasks"></i> View Assignments
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>