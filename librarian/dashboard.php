<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['librarian']);

$page_title = 'Librarian Dashboard';

// Get statistics
$query = "SELECT COUNT(*) as total FROM books";
$result = mysqli_query($conn, $query);
$total_books = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM books WHERE available_copies > 0";
$result = mysqli_query($conn, $query);
$available_books = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM book_loans WHERE status = 'issued'";
$result = mysqli_query($conn, $query);
$issued_books = mysqli_fetch_assoc($result)['total'];

$query = "SELECT COUNT(*) as total FROM book_loans WHERE status = 'overdue'";
$result = mysqli_query($conn, $query);
$overdue_books = mysqli_fetch_assoc($result)['total'];

include '../includes/header.php';
?>

<div class="row">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
              Total Books</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_books; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-book fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
              Available Books</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $available_books; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
              Issued Books</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $issued_books; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-hand-holding fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
              Overdue Books</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overdue_books; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-clock fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Recent Book Loans</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Book Title</th>
                <th>Student</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $query = "SELECT bl.*, b.title, s.admission_number 
                                      FROM book_loans bl 
                                      JOIN books b ON bl.book_id = b.id 
                                      JOIN students s ON bl.student_id = s.id 
                                      ORDER BY bl.issue_date DESC LIMIT 10";
              $loans = mysqli_query($conn, $query);
              while ($loan = mysqli_fetch_assoc($loans)):
                $status_class = $loan['status'] == 'issued' ? 'info' : ($loan['status'] == 'overdue' ? 'danger' : 'success');
              ?>
                <tr>
                  <td><?php echo $loan['title']; ?></td>
                  <td><?php echo $loan['admission_number']; ?></td>
                  <td><?php echo date('M d, Y', strtotime($loan['issue_date'])); ?></td>
                  <td><?php echo date('M d, Y', strtotime($loan['due_date'])); ?></td>
                  <td>
                    <span class="badge bg-<?php echo $status_class; ?>">
                      <?php echo ucfirst($loan['status']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($loan['status'] == 'issued'): ?>
                      <a href="return_books.php?loan_id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-undo"></i> Return
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="manage_books.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Book
          </a>
          <a href="issue_books.php" class="btn btn-success">
            <i class="fas fa-hand-holding"></i> Issue Book
          </a>
          <a href="return_books.php" class="btn btn-info">
            <i class="fas fa-undo-alt"></i> Return Book
          </a>
          <a href="overdue_books.php" class="btn btn-warning">
            <i class="fas fa-clock"></i> View Overdue Books
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>