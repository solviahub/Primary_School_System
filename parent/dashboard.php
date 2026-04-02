<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['parent']);

$page_title = 'Parent Dashboard';
$parent_id = $_SESSION['user_id'];

// Get children
$children_query = "SELECT s.*, u.full_name, u.email, c.class_name 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   JOIN classes c ON s.class_id = c.id 
                   WHERE s.parent_id = $parent_id AND s.status = 'active'";
$children = mysqli_query($conn, $children_query);
$child_count = mysqli_num_rows($children);

// Get recent announcements
$announcements_query = "SELECT * FROM announcements 
                        WHERE target_role IN ('all', 'parent') 
                        ORDER BY created_at DESC LIMIT 5";
$announcements = mysqli_query($conn, $announcements_query);

// Get pending fee payments
$pending_fees = 0;
while ($child = mysqli_fetch_assoc($children)) {
  $fee_query = "SELECT SUM(amount) as total FROM fee_payments WHERE student_id = {$child['id']}";
  $fee_result = mysqli_query($conn, $fee_query);
  $paid = mysqli_fetch_assoc($fee_result)['total'] ?? 0;

  // Assuming total fee is 5000 (you can modify based on your fee structure)
  $total_fee = 5000;
  $pending_fees += ($total_fee - $paid);
}
mysqli_data_seek($children, 0);

include '../includes/header.php';
?>

<style>
  .welcome-card {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    color: white;
  }

  .stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
  }

  .stat-card h3 {
    font-size: 2rem;
    font-weight: bold;
    color: #3b82f6;
    margin-bottom: 0.5rem;
  }

  .stat-card p {
    margin: 0;
    color: #6c757d;
  }

  .child-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .child-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(59, 130, 246, 0.15);
  }

  .child-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
  }
</style>

<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-card">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
          <p class="mb-0 opacity-75">Monitor your children's academic progress, attendance, and fee status.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-user-friends" style="font-size: 4rem; opacity: 0.3;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
  <div class="col-md-4 mb-3">
    <div class="stat-card">
      <h3><?php echo $child_count; ?></h3>
      <p>My Children</p>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="stat-card">
      <h3><?php echo number_format($pending_fees, 2); ?></h3>
      <p>Pending Fees</p>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="stat-card">
      <h3><?php echo mysqli_num_rows($announcements); ?></h3>
      <p>New Announcements</p>
    </div>
  </div>
</div>

<div class="row">
  <!-- My Children Section -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-child me-2"></i>My Children
        </h6>
      </div>
      <div class="card-body">
        <?php if ($child_count > 0): ?>
          <?php while ($child = mysqli_fetch_assoc($children)): ?>
            <div class="child-card card mb-3">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="child-avatar me-3">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-1"><?php echo htmlspecialchars($child['full_name']); ?></h6>
                    <p class="text-muted mb-1 small">
                      <i class="fas fa-id-card me-1"></i> <?php echo $child['admission_number']; ?>
                      <i class="fas fa-chalkboard ms-2 me-1"></i> <?php echo $child['class_name']; ?>
                    </p>
                  </div>
                </div>
                <div class="mt-3">
                  <div class="btn-group w-100" role="group">
                    <a href="view_results.php?student_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-chart-line"></i> Results
                    </a>
                    <a href="view_attendance.php?student_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-info">
                      <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                    <a href="fee_balance.php?student_id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-success">
                      <i class="fas fa-credit-card"></i> Fees
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-child fa-3x text-muted mb-3"></i>
            <p class="text-muted">No children linked to your account.</p>
            <small>Please contact the school administrator.</small>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent Announcements -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-bullhorn me-2"></i>Recent Announcements
        </h6>
      </div>
      <div class="card-body p-0">
        <?php if (mysqli_num_rows($announcements) > 0): ?>
          <div class="list-group list-group-flush">
            <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                    <p class="mb-1 small"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...</p>
                    <small class="text-muted">
                      <i class="fas fa-clock me-1"></i> <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                    </small>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
            <p class="text-muted">No announcements yet.</p>
          </div>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-white">
        <a href="announcements.php" class="text-decoration-none">View all announcements <i class="fas fa-arrow-right ms-1"></i></a>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row">
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
            <a href="view_results.php" class="btn btn-outline-primary w-100 py-3">
              <i class="fas fa-chart-line fa-2x d-block mb-2"></i>
              <strong>View Results</strong>
              <small class="d-block text-muted">Check academic performance</small>
            </a>
          </div>
          <div class="col-md-3">
            <a href="view_attendance.php" class="btn btn-outline-info w-100 py-3">
              <i class="fas fa-calendar-check fa-2x d-block mb-2"></i>
              <strong>View Attendance</strong>
              <small class="d-block text-muted">Track attendance records</small>
            </a>
          </div>
          <div class="col-md-3">
            <a href="fee_balance.php" class="btn btn-outline-success w-100 py-3">
              <i class="fas fa-credit-card fa-2x d-block mb-2"></i>
              <strong>Fee Balance</strong>
              <small class="d-block text-muted">Check payment status</small>
            </a>
          </div>
          <div class="col-md-3">
            <a href="make_payment.php" class="btn btn-outline-warning w-100 py-3">
              <i class="fas fa-money-bill-wave fa-2x d-block mb-2"></i>
              <strong>Make Payment</strong>
              <small class="d-block text-muted">Pay school fees online</small>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>