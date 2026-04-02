<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['parent']);

$page_title = 'Fee Balance';
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
$fee_structure = [];
$total_fee = 0;
$total_paid = 0;
$balance = 0;
$payments = [];

if ($selected_child) {
  // Get student details
  $student_query = "SELECT s.*, u.full_name, c.class_name 
                      FROM students s 
                      JOIN users u ON s.user_id = u.id 
                      JOIN classes c ON s.class_id = c.id 
                      WHERE s.id = $selected_child";
  $student_info = mysqli_fetch_assoc(mysqli_query($conn, $student_query));

  if ($student_info) {
    // Get fee structure for this class
    $fee_query = "SELECT * FROM fee_structure WHERE class_id = {$student_info['class_id']}";
    $fee_result = mysqli_query($conn, $fee_query);
    while ($fee = mysqli_fetch_assoc($fee_result)) {
      $fee_structure[] = $fee;
      $total_fee += $fee['amount'];
    }

    // Get payment history
    $payment_query = "SELECT * FROM fee_payments 
                          WHERE student_id = $selected_child 
                          ORDER BY payment_date DESC";
    $payments = mysqli_query($conn, $payment_query);

    // Calculate total paid
    $paid_query = "SELECT SUM(amount) as total FROM fee_payments WHERE student_id = $selected_child";
    $paid_result = mysqli_query($conn, $paid_query);
    $total_paid = mysqli_fetch_assoc($paid_result)['total'] ?? 0;

    $balance = $total_fee - $total_paid;
  }
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-3 mb-4">
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
                <small><?php echo $child['class_name']; ?> | <?php echo $child['admission_number']; ?></small>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-9">
    <?php if ($selected_child && $student_info): ?>
      <!-- Balance Summary -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card text-center">
            <div class="card-body">
              <h5>Total Fee</h5>
              <h3 class="text-primary">$<?php echo number_format($total_fee, 2); ?></h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-center">
            <div class="card-body">
              <h5>Total Paid</h5>
              <h3 class="text-success">$<?php echo number_format($total_paid, 2); ?></h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-center">
            <div class="card-body">
              <h5>Balance</h5>
              <h3 class="<?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">
                $<?php echo number_format($balance, 2); ?>
              </h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Fee Structure -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-list me-2"></i>Fee Structure
          </h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead style="background-color: #3b82f6; color: white;">
                <tr>
                  <th>Fee Type</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($fee_structure as $fee): ?>
                  <tr>
                    <td><?php echo $fee['fee_type']; ?></td>
                    <td class="text-end">$<?php echo number_format($fee['amount'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="fw-bold" style="background-color: #f8f9fa;">
                  <td>Total</td>
                  <td class="text-end">$<?php echo number_format($total_fee, 2); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Payment History -->
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-history me-2"></i>Payment History
          </h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead style="background-color: #3b82f6; color: white;">
                <tr>
                  <th>Receipt No</th>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Payment Method</th>
                  <th>Transaction ID</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($payments) > 0): ?>
                  <?php while ($payment = mysqli_fetch_assoc($payments)): ?>
                    <tr>
                      <td><strong><?php echo $payment['receipt_number']; ?></strong></td>
                      <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                      <td class="text-success fw-bold">$<?php echo number_format($payment['amount'], 2); ?></td>
                      <td>
                        <span class="badge bg-<?php
                                              echo $payment['payment_method'] == 'cash' ? 'success' : ($payment['payment_method'] == 'bank_transfer' ? 'info' : 'primary');
                                              ?>">
                          <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                        </span>
                      </td>
                      <td><?php echo $payment['transaction_id'] ?: 'N/A'; ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center py-4">No payment records found</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php if ($balance > 0): ?>
        <div class="mt-3 text-end">
          <a href="make_payment.php?student_id=<?php echo $selected_child; ?>" class="btn" style="background-color: #3b82f6; color: white;">
            <i class="fas fa-credit-card me-2"></i>Make Payment
          </a>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
          <h5>Select a child to view fee balance</h5>
          <p class="text-muted">Choose a child from the left panel to view their fee details.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>