<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['librarian']);

$page_title = 'Overdue Books';
$librarian_id = $_SESSION['user_id'];

// Get all overdue books
$overdue_query = "SELECT bl.*, b.title, b.isbn, b.author, u.full_name as student_name, s.admission_number,
                  DATEDIFF(CURDATE(), bl.due_date) as days_overdue,
                  (DATEDIFF(CURDATE(), bl.due_date) * 50) as fine_amount
                  FROM book_loans bl
                  JOIN books b ON bl.book_id = b.id
                  JOIN students s ON bl.student_id = s.id
                  JOIN users u ON s.user_id = u.id
                  WHERE bl.status = 'issued' AND bl.due_date < CURDATE()
                  ORDER BY bl.due_date ASC";
$overdue_books = mysqli_query($conn, $overdue_query);
$total_overdue = mysqli_num_rows($overdue_books);

// Calculate total fines
$total_fines = 0;
$overdue_list = [];
while ($book = mysqli_fetch_assoc($overdue_books)) {
  $total_fines += $book['fine_amount'];
  $overdue_list[] = $book;
}
// Reset pointer
mysqli_data_seek($overdue_books, 0);

include '../includes/header.php';
?>

<style>
  .overdue-card {
    border-left: 4px solid #dc2626;
  }
</style>

<div class="row mb-4">
  <div class="col-md-12">
    <div class="welcome-card" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius: 15px; padding: 2rem;">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="text-white">Overdue Books</h2>
          <p class="text-white-50 mb-0">Books that are past their due date.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-clock fa-3x text-white" style="opacity: 0.3;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <h3 class="text-danger mb-0"><?php echo $total_overdue; ?></h3>
        <p class="text-muted">Overdue Books</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <h3 class="text-warning mb-0">₹<?php echo number_format($total_fines, 2); ?></h3>
        <p class="text-muted">Total Fines Collected</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <a href="return_books.php" class="btn btn-danger w-100">
          <i class="fas fa-undo me-2"></i>Process Returns
        </a>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white">
    <h6 class="mb-0 fw-bold" style="color: #dc2626;">
      <i class="fas fa-exclamation-triangle me-2"></i>Overdue Books List
    </h6>
  </div>
  <div class="card-body">
    <?php if ($total_overdue > 0): ?>
      <div class="table-responsive">
        <table class="table table-hover datatable">
          <thead style="background-color: #dc2626; color: white;">
            <tr>
              <th>Student Name</th>
              <th>Admission No</th>
              <th>Book Title</th>
              <th>Author</th>
              <th>Issue Date</th>
              <th>Due Date</th>
              <th>Days Overdue</th>
              <th>Fine Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($overdue_list as $book): ?>
              <tr>
                <td><?php echo $book['student_name']; ?></td>
                <td><?php echo $book['admission_number']; ?></td>
                <td><strong><?php echo $book['title']; ?></strong></td>
                <td><?php echo $book['author'] ?: 'Unknown'; ?></td>
                <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                <td>
                  <span class="badge bg-danger"><?php echo $book['days_overdue']; ?> days</span>
                </td>
                <td>
                  <span class="text-danger fw-bold">₹<?php echo number_format($book['fine_amount'], 2); ?></span>
                </td>
                <td>
                  <a href="return_books.php?loan_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-undo"></i> Return
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot style="background-color: #f8f9fa;">
            <tr class="fw-bold">
              <td colspan="7" class="text-end">Total Fines:</td>
              <td colspan="2">₹<?php echo number_format($total_fines, 2); ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
        <h5>No overdue books</h5>
        <p class="text-muted">All books are returned on time!</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>