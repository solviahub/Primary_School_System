<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['librarian']);

$page_title = 'Issue Books';
$librarian_id = $_SESSION['user_id'];

// Get available books
$books_query = "SELECT * FROM books WHERE available_copies > 0 ORDER BY title";
$books = mysqli_query($conn, $books_query);

// Get students
$students_query = "SELECT s.*, u.full_name, u.email 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   WHERE s.status = 'active' 
                   ORDER BY u.full_name";
$students = mysqli_query($conn, $students_query);

// Handle book issuance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['issue_book'])) {
    $book_id = (int)$_POST['book_id'];
    $student_id = (int)$_POST['student_id'];
    $due_days = (int)$_POST['due_days'];
    $issue_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime("+$due_days days"));
    
    // Check if book is available
    $book_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT available_copies FROM books WHERE id = $book_id"));
    
    if($book_check['available_copies'] > 0) {
        // Create loan record
        $query = "INSERT INTO book_loans (book_id, student_id, issue_date, due_date, status, issued_by) 
                  VALUES ($book_id, $student_id, '$issue_date', '$due_date', 'issued', $librarian_id)";
        
        if(mysqli_query($conn, $query)) {
            // Update available copies
            mysqli_query($conn, "UPDATE books SET available_copies = available_copies - 1 WHERE id = $book_id");
            
            // Get book and student details for logging
            $book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM books WHERE id = $book_id"));
            $student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = $student_id"));
            
            logActivity($librarian_id, 'Issued book', "Book: {$book['title']} to {$student['full_name']}");
            $_SESSION['message'] = 'Book issued successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error issuing book: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Book is not available!';
        $_SESSION['message_type'] = 'danger';
    }
    redirect('librarian/issue_books.php');
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
                    <i class="fas fa-hand-holding me-2"></i>Issue Book to Student
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Student *</label>
                        <select class="form-select" name="student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php while($student = mysqli_fetch_assoc($students)): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo $student['full_name'] . ' (' . $student['admission_number'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Book *</label>
                        <select class="form-select" name="book_id" required>
                            <option value="">-- Select Book --</option>
                            <?php while($book = mysqli_fetch_assoc($books)): ?>
                                <option value="<?php echo $book['id']; ?>">
                                    <?php echo $book['title'] . ' (' . $book['available_copies'] . ' available)'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Due Date</label>
                        <select class="form-select" name="due_days" required>
                            <option value="7">7 days (1 week)</option>
                            <option value="14" selected>14 days (2 weeks)</option>
                            <option value="21">21 days (3 weeks)</option>
                            <option value="30">30 days (1 month)</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Make sure the student has no overdue books before issuing.</small>
                    </div>
                    
                    <button type="submit" name="issue_book" class="btn w-100" style="background-color: #3b82f6; color: white;">
                        <i class="fas fa-check-circle me-2"></i>Issue Book
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
                    <i class="fas fa-clock me-2"></i>Currently Issued Books
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background-color: #3b82f6; color: white;">
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_loans = mysqli_query($conn, "SELECT bl.*, b.title, u.full_name as student_name, s.admission_number
                                                                FROM book_loans bl
                                                                JOIN books b ON bl.book_id = b.id
                                                                JOIN students s ON bl.student_id = s.id
                                                                JOIN users u ON s.user_id = u.id
                                                                WHERE bl.status = 'issued'
                                                                ORDER BY bl.due_date ASC");
                            ?>
                            <?php if(mysqli_num_rows($current_loans) > 0): ?>
                                <?php while($loan = mysqli_fetch_assoc($current_loans)): 
                                    $is_overdue = strtotime($loan['due_date']) < time();
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo $loan['student_name']; ?>
                                            <br><small><?php echo $loan['admission_number']; ?></small>
                                        </td>
                                        <td><?php echo $loan['title']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($loan['issue_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($loan['due_date'])); ?></td>
                                        <td>
                                            <?php if($is_overdue): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Issued</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No books currently issued</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>