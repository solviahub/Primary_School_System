<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['librarian']);

$page_title = 'Return Books';
$librarian_id = $_SESSION['user_id'];

// Handle book return
if (isset($_GET['loan_id'])) {
  $loan_id = (int)$_GET['loan_id'];

  // Get loan details
  $loan_query = "SELECT bl.*, b.title, b.id as book_id, u.full_name as student_name 
                   FROM book_loans bl 
                   JOIN books b ON bl.book_id = b.id 
                   JOIN students s ON bl.student_id = s.id 
                   JOIN users u ON s.user_id = u.id 
                   WHERE bl.id = $loan_id";
  $loan = mysqli_fetch_assoc(mysqli_query($conn, $loan_query));

  if ($loan) {
    $return_date = date('Y-m-d');
    $fine = 0;

    // Calculate fine if overdue
    if (strtotime($return_date) > strtotime($loan['due_date'])) {
      $days_overdue = ceil((strtotime($return_date) - strtotime($loan['due_date'])) / (60 * 60 * 24));
      $fine = $days_overdue * 50; // 50 per day fine
    }

    // Update loan record
    $update_query = "UPDATE book_loans 
                        SET return_date = '$return_date', status = 'returned', fine_amount = $fine 
                        WHERE id = $loan_id";

    if (mysqli_query($conn, $update_query)) {
      // Update available copies
      mysqli_query($conn, "UPDATE books SET available_copies = available_copies + 1 WHERE id = {$loan['book_id']}");

      logActivity($librarian_id, 'Returned book', "Book: {$loan['title']} from {$loan['student_name']}, Fine: $fine");

      if ($fine > 0) {
        $_SESSION['message'] = "Book returned successfully! Fine amount: ₹" . number_format($fine, 2);
      } else {
        $_SESSION['message'] = 'Book returned successfully!';
      }
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error returning book!';
      $_SESSION['message_type'] = 'danger';
    }
  }
  redirect('librarian/return_books.php');
}

// Get issued books for return
$issued_books = mysqli_query($conn, "SELECT bl.*, b.title, b.isbn, u.full_name as student_name, s.admission_number,
                                     DATEDIFF(CURDATE(), bl.due_date) as days_overdue
                                     FROM book_loans bl
                                     JOIN books b ON bl.book_id = b.id
                                     JOIN students s ON bl.student_id = s.id
                                     JOIN users u ON s.user_id = u.id
                                     WHERE bl.status = 'issued'
                                     ORDER BY bl.due_date ASC");
$has_issued = mysqli_num_rows($issued_books) > 0;

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-undo-alt me-2"></i>Return Issued Books
        </h6>
      </div>
      <div class="card-body">
        <?php if ($has_issued): ?>
          <div class="table-responsive">
            <table class="table table-hover datatable">
              <thead style="background-color: #3b82f6; color: white;">
                <tr>
                  <th>Student Name</th>
                  <th>Admission No</th>
                  <th>Book Title</th>
                  <th>ISBN</th>
                  <th>Issue Date</th>
                  <th>Due Date</th>
                  <th>Days Overdue</th>
                  <th>Fine Amount</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($book = mysqli_fetch_assoc($issued_books)):
                  $is_overdue = $book['days_overdue'] > 0;
                  $fine = $is_overdue ? $book['days_overdue'] * 50 : 0;
                ?>
                  <tr>
                    <td><?php echo $book['student_name']; ?></td>
                    <td><?php echo $book['admission_number']; ?></td>
                    <td><?php echo $book['title']; ?></td>
                    <td><?php echo $book['isbn']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                    <td>
                      <?php if ($is_overdue): ?>
                        <span class="badge bg-danger"><?php echo $book['days_overdue']; ?> days</span>
                      <?php else: ?>
                        <span class="badge bg-success">On Time</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($is_overdue): ?>
                        <span class="text-danger fw-bold">₹<?php echo number_format($fine, 2); ?></span>
                      <?php else: ?>
                        <span class="text-success">No fine</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="?loan_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Process return for this book?')">
                        <i class="fas fa-undo"></i> Return Book
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h5>No books currently issued</h5>
            <p class="text-muted">All books have been returned</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>