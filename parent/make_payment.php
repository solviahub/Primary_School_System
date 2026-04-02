<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['parent']);

$page_title = 'Make Payment';
$parent_id = $_SESSION['user_id'];

// Get children
$children_query = "SELECT s.*, u.full_name, c.class_name 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   JOIN classes c ON s.class_id = c.id 
                   WHERE s.parent_id = $parent_id AND s.status = 'active'";
$children = mysqli_query($conn, $children_query);

// Get selected child
$selected_child = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

$student_info = null;
$balance = 0;

if ($selected_child) {
  $student_query = "SELECT s.*, u.full_name, c.class_name 
                      FROM students s 
                      JOIN users u ON s.user_id = u.id 
                      JOIN classes c ON s.class_id = c.id 
                      WHERE s.id = $selected_child";
  $student_info = mysqli_fetch_assoc(mysqli_query($conn, $student_query));

  if ($student_info) {
    // Calculate total fee
    $fee_query = "SELECT SUM(amount) as total FROM fee_structure WHERE class_id = {$student_info['class_id']}";
    $fee_result = mysqli_query($conn, $fee_query);
    $total_fee = mysqli_fetch_assoc($fee_result)['total'] ?? 0;

    // Calculate paid amount
    $paid_query = "SELECT SUM(amount) as total FROM fee_payments WHERE student_id = $selected_child";
    $paid_result = mysqli_query($conn, $paid_query);
    $total_paid = mysqli_fetch_assoc($paid_result)['total'] ?? 0;

    $balance = $total_fee - $total_paid;
  }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_payment'])) {
  $student_id = (int)$_POST['student_id'];
  $amount = (float)$_POST['amount'];
  $payment_method = sanitize($_POST['payment_method']);
  $transaction_id = sanitize($_POST['transaction_id']);
  $remarks = sanitize($_POST['remarks']);

  // Generate receipt number
  $receipt_number = 'RCP' . date('Ymd') . rand(1000, 9999);

  $query = "INSERT INTO fee_payments (student_id, amount, payment_date, payment_method, transaction_id, receipt_number, remarks, recorded_by) 
              VALUES ($student_id, $amount, CURDATE(), '$payment_method', '$transaction_id', '$receipt_number', '$remarks', {$parent_id})";

  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = "Payment successful! Receipt Number: $receipt_number";
    $_SESSION['message_type'] = 'success';
    redirect("parent/fee_balance.php?student_id=$student_id");
  } else {
    $_SESSION['message'] = 'Error processing payment!';
    $_SESSION['message_type'] = 'danger';
  }
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-4 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-child me-2"></i>Select Child
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php while ($child = mysqli_fetch_assoc($children)): ?>
            <a href="?student_id=<?php echo $child['id']; ?>"
              class="list-group-item list-group-item-action <?php echo $selected_child == $child['id'] ? 'active' : ''; ?>"
              style="<?php echo $selected_child == $child['id'] ? 'background-color: #3b82f6; border-color: #3b82f6;' : ''; ?>">
              <div>
                <strong><?php echo htmlspecialchars($child['full_name']); ?></strong>
                <br>
                <small><?php echo $child['class_name']; ?></small>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <?php if ($selected_child && $student_info && $balance > 0): ?>
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-credit-card me-2"></i>Make Payment for <?php echo $student_info['full_name']; ?>
          </h6>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <strong>Outstanding Balance:</strong> $<?php echo number_format($balance, 2); ?>
          </div>

          <form method="POST" action="">
            <input type="hidden" name="student_id" value="<?php echo $selected_child; ?>">

            <div class="mb-3">
              <label class="form-label fw-semibold">Amount to Pay *</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" class="form-control" name="amount"
                  max="<?php echo $balance; ?>" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Payment Method *</label>
              <select class="form-select" name="payment_method" required>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="online">Online Payment</option>
                <option value="check">Check</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Transaction ID (Optional)</label>
              <input type="text" class="form-control" name="transaction_id"
                placeholder="Bank reference / Transaction number">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Remarks (Optional)</label>
              <textarea class="form-control" name="remarks" rows="2"
                placeholder="Any additional information..."></textarea>
            </div>

            <button type="submit" name="make_payment" class="btn w-100" style="background-color: #3b82f6; color: white;">
              <i class="fas fa-check-circle me-2"></i>Process Payment
            </button>
          </form>
        </div>
      </div>

    <?php elseif ($selected_child && $student_info && $balance <= 0): ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
          <h5>No Outstanding Balance</h5>
          <p class="text-muted">All fees for this student have been paid.</p>
          <a href="fee_balance.php?student_id=<?php echo $selected_child; ?>" class="btn btn-primary">
            View Payment History
          </a>
        </div>
      </div>

    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
          <h5>Select a child to make payment</h5>
          <p class="text-muted">Choose a child from the left panel to pay their school fees.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>