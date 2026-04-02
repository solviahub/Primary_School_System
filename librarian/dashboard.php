<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['librarian']);

$page_title = 'Librarian Dashboard';
$librarian_id = $_SESSION['user_id'];

// Get statistics
$stats_query = "SELECT 
                    (SELECT COUNT(*) FROM books) as total_books,
                    (SELECT SUM(total_copies) FROM books) as total_copies,
                    (SELECT SUM(available_copies) FROM books) as available_copies,
                    (SELECT COUNT(*) FROM book_loans WHERE status = 'issued') as issued_books,
                    (SELECT COUNT(*) FROM book_loans WHERE status = 'overdue') as overdue_books,
                    (SELECT COUNT(*) FROM book_loans WHERE DATE(return_date) = CURDATE() AND status = 'returned') as today_returns,
                    (SELECT COUNT(*) FROM book_loans WHERE DATE(issue_date) = CURDATE()) as today_issues";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent book loans
$recent_loans_query = "SELECT bl.*, b.title, b.isbn, s.admission_number, u.full_name as student_name 
                       FROM book_loans bl 
                       JOIN books b ON bl.book_id = b.id 
                       JOIN students s ON bl.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       ORDER BY bl.issue_date DESC LIMIT 10";
$recent_loans = mysqli_query($conn, $recent_loans_query);

// Get overdue books
$overdue_query = "SELECT bl.*, b.title, b.isbn, s.admission_number, u.full_name as student_name,
                  DATEDIFF(CURDATE(), bl.due_date) as days_overdue
                  FROM book_loans bl 
                  JOIN books b ON bl.book_id = b.id 
                  JOIN students s ON bl.student_id = s.id 
                  JOIN users u ON s.user_id = u.id 
                  WHERE bl.status = 'overdue' OR (bl.status = 'issued' AND bl.due_date < CURDATE())
                  ORDER BY bl.due_date ASC";
$overdue_books = mysqli_query($conn, $overdue_query);

// Get popular books (most issued)
$popular_query = "SELECT b.*, COUNT(bl.id) as times_issued 
                  FROM books b 
                  LEFT JOIN book_loans bl ON b.id = bl.book_id 
                  GROUP BY b.id 
                  ORDER BY times_issued DESC LIMIT 5";
$popular_books = mysqli_query($conn, $popular_query);

include '../includes/header.php';
?>

<style>
  .stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    margin-bottom: 1rem;
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
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    color: white;
  }
</style>

<div class="welcome-card">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h2 class="text-white">Welcome back, <?php echo $_SESSION['user_name']; ?>!</h2>
      <p class="text-white-50 mb-0">Here's your library management overview.</p>
    </div>
    <div class="col-md-4 text-end">
      <i class="fas fa-book" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
    </div>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-book"></i>
      </div>
      <div class="stat-title">Total Books</div>
      <div class="stat-value"><?php echo number_format($stats['total_books']); ?></div>
      <div class="stat-change">
        <i class="fas fa-copy text-primary"></i> <?php echo number_format($stats['total_copies']); ?> copies
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="stat-title">Available Books</div>
      <div class="stat-value"><?php echo number_format($stats['available_copies']); ?></div>
      <div class="stat-change">
        <i class="fas fa-percent text-success"></i> <?php echo $stats['total_copies'] > 0 ? round(($stats['available_copies'] / $stats['total_copies']) * 100) : 0; ?>% available
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-hand-holding"></i>
      </div>
      <div class="stat-title">Currently Issued</div>
      <div class="stat-value"><?php echo number_format($stats['issued_books']); ?></div>
      <div class="stat-change">
        <i class="fas fa-clock text-warning"></i> <?php echo $stats['overdue_books']; ?> overdue
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-3">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-chart-line"></i>
      </div>
      <div class="stat-title">Today's Activity</div>
      <div class="stat-value"><?php echo $stats['today_issues'] + $stats['today_returns']; ?></div>
      <div class="stat-change">
        <i class="fas fa-arrow-right text-info"></i> <?php echo $stats['today_issues']; ?> issued | <?php echo $stats['today_returns']; ?> returned
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Recent Loans -->
  <div class="col-lg-7 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-history me-2"></i>Recent Book Loans
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead style="background-color: #3b82f6; color: white;">
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
              <?php if (mysqli_num_rows($recent_loans) > 0): ?>
                <?php while ($loan = mysqli_fetch_assoc($recent_loans)):
                  $status_class = $loan['status'] == 'issued' ? 'warning' : ($loan['status'] == 'overdue' ? 'danger' : 'success');
                ?>
                  <tr>
                    <td><?php echo htmlspecialchars(substr($loan['title'], 0, 30)); ?>...</td>
                    <td><?php echo $loan['student_name'] . '<br><small>' . $loan['admission_number'] . '</small>'; ?></td>
                    <td><?php echo date('M d, Y', strtotime($loan['issue_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($loan['due_date'])); ?></td>
                    <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($loan['status']); ?></span></td>
                    <td>
                      <?php if ($loan['status'] == 'issued'): ?>
                        <a href="return_books.php?loan_id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-success">
                          <i class="fas fa-undo"></i> Return
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4">No recent loans found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Overdue Books -->
  <div class="col-lg-5 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #dc2626;">
          <i class="fas fa-exclamation-triangle me-2"></i>Overdue Books
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php if (mysqli_num_rows($overdue_books) > 0): ?>
            <?php while ($overdue = mysqli_fetch_assoc($overdue_books)): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?php echo htmlspecialchars($overdue['title']); ?></strong>
                    <br>
                    <small><?php echo $overdue['student_name']; ?> (<?php echo $overdue['admission_number']; ?>)</small>
                    <br>
                    <small class="text-danger">Overdue by <?php echo $overdue['days_overdue']; ?> days</small>
                  </div>
                  <a href="return_books.php?loan_id=<?php echo $overdue['id']; ?>" class="btn btn-sm btn-danger">
                    <i class="fas fa-undo"></i> Return
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
              <p class="text-muted">No overdue books</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Popular Books -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-fire me-2"></i>Most Popular Books
        </h6>
      </div>
      <div class="card-body">
        <div class="list-group">
          <?php if (mysqli_num_rows($popular_books) > 0): ?>
            <?php while ($book = mysqli_fetch_assoc($popular_books)): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                    <br>
                    <small class="text-muted">by <?php echo $book['author'] ?: 'Unknown'; ?></small>
                  </div>
                  <span class="badge bg-primary rounded-pill"><?php echo $book['times_issued']; ?> issues</span>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="text-muted text-center">No data available</p>
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
            <a href="manage_books.php" class="btn btn-outline-primary w-100 py-3 text-start">
              <i class="fas fa-plus-circle me-2"></i>
              <strong>Add New Book</strong>
              <small class="d-block text-muted">Add books to library</small>
            </a>
          </div>
          <div class="col-md-6">
            <a href="issue_books.php" class="btn btn-outline-success w-100 py-3 text-start">
              <i class="fas fa-hand-holding me-2"></i>
              <strong>Issue Book</strong>
              <small class="d-block text-muted">Lend book to student</small>
            </a>
          </div>
          <div class="col-md-6">
            <a href="return_books.php" class="btn btn-outline-warning w-100 py-3 text-start">
              <i class="fas fa-undo-alt me-2"></i>
              <strong>Return Book</strong>
              <small class="d-block text-muted">Process book returns</small>
            </a>
          </div>
          <div class="col-md-6">
            <a href="overdue_books.php" class="btn btn-outline-danger w-100 py-3 text-start">
              <i class="fas fa-clock me-2"></i>
              <strong>Overdue Books</strong>
              <small class="d-block text-muted">View overdue books</small>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>