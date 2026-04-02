<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['librarian']);

$page_title = 'Manage Books';
$librarian_id = $_SESSION['user_id'];

// Handle book addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
  $isbn = sanitize($_POST['isbn']);
  $title = sanitize($_POST['title']);
  $author = sanitize($_POST['author']);
  $publisher = sanitize($_POST['publisher']);
  $publication_year = (int)$_POST['publication_year'];
  $category = sanitize($_POST['category']);
  $total_copies = (int)$_POST['total_copies'];
  $available_copies = $total_copies;
  $location = sanitize($_POST['location']);

  // Check if ISBN already exists
  $check_query = "SELECT id FROM books WHERE isbn = '$isbn'";
  $check_result = mysqli_query($conn, $check_query);

  if (mysqli_num_rows($check_result) > 0) {
    $_SESSION['message'] = 'Book with this ISBN already exists!';
    $_SESSION['message_type'] = 'danger';
  } else {
    $query = "INSERT INTO books (isbn, title, author, publisher, publication_year, category, total_copies, available_copies, location, added_by) 
                  VALUES ('$isbn', '$title', '$author', '$publisher', $publication_year, '$category', $total_copies, $available_copies, '$location', $librarian_id)";

    if (mysqli_query($conn, $query)) {
      logActivity($librarian_id, 'Added new book', "Title: $title, ISBN: $isbn");
      $_SESSION['message'] = 'Book added successfully!';
      $_SESSION['message_type'] = 'success';
    } else {
      $_SESSION['message'] = 'Error adding book: ' . mysqli_error($conn);
      $_SESSION['message_type'] = 'danger';
    }
  }
  redirect('librarian/manage_books.php');
}

// Handle book update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_book'])) {
  $book_id = (int)$_POST['book_id'];
  $isbn = sanitize($_POST['isbn']);
  $title = sanitize($_POST['title']);
  $author = sanitize($_POST['author']);
  $publisher = sanitize($_POST['publisher']);
  $publication_year = (int)$_POST['publication_year'];
  $category = sanitize($_POST['category']);
  $total_copies = (int)$_POST['total_copies'];
  $location = sanitize($_POST['location']);

  // Calculate new available copies
  $current_query = "SELECT available_copies, total_copies FROM books WHERE id = $book_id";
  $current = mysqli_fetch_assoc(mysqli_query($conn, $current_query));
  $available_copies = $current['available_copies'] + ($total_copies - $current['total_copies']);

  $query = "UPDATE books SET 
              isbn='$isbn', title='$title', author='$author', publisher='$publisher', 
              publication_year=$publication_year, category='$category', total_copies=$total_copies, 
              available_copies=$available_copies, location='$location' 
              WHERE id=$book_id";

  if (mysqli_query($conn, $query)) {
    logActivity($librarian_id, 'Updated book', "Title: $title");
    $_SESSION['message'] = 'Book updated successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error updating book!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('librarian/manage_books.php');
}

// Handle book deletion
if (isset($_GET['delete'])) {
  $book_id = (int)$_GET['delete'];
  $book_query = "SELECT title FROM books WHERE id = $book_id";
  $book = mysqli_fetch_assoc(mysqli_query($conn, $book_query));

  $query = "DELETE FROM books WHERE id = $book_id";
  if (mysqli_query($conn, $query)) {
    logActivity($librarian_id, 'Deleted book', "Title: {$book['title']}");
    $_SESSION['message'] = 'Book deleted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deleting book!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('librarian/manage_books.php');
}

// Get all books
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

$where = [];
if ($search) {
  $where[] = "(title LIKE '%$search%' OR author LIKE '%$search%' OR isbn LIKE '%$search%')";
}
if ($category_filter) {
  $where[] = "category = '$category_filter'";
}
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$books_query = "SELECT *, 
                (total_copies - available_copies) as issued_copies 
                FROM books 
                $where_clause 
                ORDER BY title";
$books = mysqli_query($conn, $books_query);

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != ''";
$categories = mysqli_query($conn, $categories_query);

include '../includes/header.php';
?>

<style>
  .book-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 10px;
    overflow: hidden;
  }

  .book-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.1);
  }

  .available-badge {
    background-color: #d1fae5;
    color: #065f46;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 12px;
  }

  .issued-badge {
    background-color: #fed7aa;
    color: #92400e;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 12px;
  }
</style>

<div class="row mb-3">
  <div class="col-md-8">
    <button type="button" class="btn" style="background-color: #3b82f6; color: white;" data-bs-toggle="modal" data-bs-target="#addBookModal">
      <i class="fas fa-plus me-2"></i>Add New Book
    </button>
  </div>
  <div class="col-md-4">
    <form method="GET" action="">
      <div class="input-group">
        <input type="text" class="form-control" name="search" placeholder="Search by title, author, ISBN..." value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn btn-outline-secondary" type="submit">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($category_filter || $search): ?>
  <div class="row mb-3">
    <div class="col-12">
      <div class="alert alert-info">
        <i class="fas fa-filter me-2"></i>
        Showing filtered results
        <a href="manage_books.php" class="float-end text-decoration-none">Clear filters</a>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row">
  <!-- Filter Sidebar -->
  <div class="col-md-3 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-filter me-2"></i>Filters
        </h6>
      </div>
      <div class="card-body">
        <h6 class="fw-bold mb-2">Categories</h6>
        <div class="list-group list-group-flush">
          <a href="manage_books.php" class="list-group-item list-group-item-action <?php echo !$category_filter ? 'active' : ''; ?>" style="<?php echo !$category_filter ? 'background-color: #3b82f6;' : ''; ?>">
            All Books
          </a>
          <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
            <a href="?category=<?php echo urlencode($cat['category']); ?>" class="list-group-item list-group-item-action <?php echo $category_filter == $cat['category'] ? 'active' : ''; ?>" style="<?php echo $category_filter == $cat['category'] ? 'background-color: #3b82f6;' : ''; ?>">
              <?php echo $cat['category']; ?>
            </a>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Books Grid -->
  <div class="col-md-9">
    <div class="row">
      <?php if (mysqli_num_rows($books) > 0): ?>
        <?php while ($book = mysqli_fetch_assoc($books)): ?>
          <div class="col-md-6 col-lg-4 mb-4">
            <div class="card book-card shadow-sm h-100">
              <div class="card-body">
                <div class="text-center mb-3">
                  <i class="fas fa-book fa-3x" style="color: #3b82f6;"></i>
                </div>
                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                <small class="text-muted">by <?php echo $book['author'] ?: 'Unknown'; ?></small>
                <hr>
                <div class="row">
                  <div class="col-6">
                    <small class="text-muted">ISBN:</small>
                    <p class="mb-1"><?php echo $book['isbn']; ?></p>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Category:</small>
                    <p class="mb-1"><?php echo $book['category'] ?: 'Uncategorized'; ?></p>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    <small class="text-muted">Publisher:</small>
                    <p class="mb-1"><?php echo $book['publisher'] ?: 'N/A'; ?></p>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Year:</small>
                    <p class="mb-1"><?php echo $book['publication_year']; ?></p>
                  </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                  <span class="available-badge">
                    <i class="fas fa-check-circle"></i> Available: <?php echo $book['available_copies']; ?>
                  </span>
                  <span class="issued-badge">
                    <i class="fas fa-hand-holding"></i> Issued: <?php echo $book['issued_copies']; ?>
                  </span>
                </div>
                <div class="progress mb-2" style="height: 5px;">
                  <?php $issued_percent = $book['total_copies'] > 0 ? ($book['issued_copies'] / $book['total_copies']) * 100 : 0; ?>
                  <div class="progress-bar bg-warning" style="width: <?php echo $issued_percent; ?>%"></div>
                </div>
                <small class="text-muted">Total Copies: <?php echo $book['total_copies']; ?></small>
              </div>
              <div class="card-footer bg-white">
                <button class="btn btn-sm btn-outline-primary edit-book"
                  data-id="<?php echo $book['id']; ?>"
                  data-isbn="<?php echo $book['isbn']; ?>"
                  data-title="<?php echo htmlspecialchars($book['title']); ?>"
                  data-author="<?php echo htmlspecialchars($book['author']); ?>"
                  data-publisher="<?php echo htmlspecialchars($book['publisher']); ?>"
                  data-year="<?php echo $book['publication_year']; ?>"
                  data-category="<?php echo $book['category']; ?>"
                  data-total="<?php echo $book['total_copies']; ?>"
                  data-location="<?php echo $book['location']; ?>">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <a href="?delete=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm">
                  <i class="fas fa-trash"></i> Delete
                </a>
                <a href="issue_books.php?book_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-success">
                  <i class="fas fa-hand-holding"></i> Issue
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body text-center py-5">
              <i class="fas fa-book fa-4x text-muted mb-3"></i>
              <h5>No books found</h5>
              <p class="text-muted">Click "Add New Book" to start building your library</p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header" style="background-color: #3b82f6; color: white;">
          <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Book</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">ISBN *</label>
              <input type="text" class="form-control" name="isbn" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Title *</label>
              <input type="text" class="form-control" name="title" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Author</label>
              <input type="text" class="form-control" name="author">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Publisher</label>
              <input type="text" class="form-control" name="publisher">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Publication Year</label>
              <input type="number" class="form-control" name="publication_year" value="<?php echo date('Y'); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Category</label>
              <input type="text" class="form-control" name="category" placeholder="Fiction, Science, History...">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Total Copies *</label>
              <input type="number" class="form-control" name="total_copies" value="1" min="1" required>
            </div>
            <div class="col-md-12 mb-3">
              <label class="form-label fw-semibold">Location/Shelf</label>
              <input type="text" class="form-control" name="location" placeholder="e.g., Section A, Shelf 3">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_book" class="btn" style="background-color: #3b82f6; color: white;">Add Book</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header" style="background-color: #3b82f6; color: white;">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Book</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="book_id" id="edit_book_id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">ISBN *</label>
              <input type="text" class="form-control" name="isbn" id="edit_isbn" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Title *</label>
              <input type="text" class="form-control" name="title" id="edit_title" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Author</label>
              <input type="text" class="form-control" name="author" id="edit_author">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Publisher</label>
              <input type="text" class="form-control" name="publisher" id="edit_publisher">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Publication Year</label>
              <input type="number" class="form-control" name="publication_year" id="edit_year">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Category</label>
              <input type="text" class="form-control" name="category" id="edit_category">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Total Copies *</label>
              <input type="number" class="form-control" name="total_copies" id="edit_total" min="1" required>
            </div>
            <div class="col-md-12 mb-3">
              <label class="form-label fw-semibold">Location/Shelf</label>
              <input type="text" class="form-control" name="location" id="edit_location">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_book" class="btn" style="background-color: #3b82f6; color: white;">Update Book</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Edit book button click
  document.querySelectorAll('.edit-book').forEach(button => {
    button.addEventListener('click', function() {
      document.getElementById('edit_book_id').value = this.dataset.id;
      document.getElementById('edit_isbn').value = this.dataset.isbn;
      document.getElementById('edit_title').value = this.dataset.title;
      document.getElementById('edit_author').value = this.dataset.author;
      document.getElementById('edit_publisher').value = this.dataset.publisher;
      document.getElementById('edit_year').value = this.dataset.year;
      document.getElementById('edit_category').value = this.dataset.category;
      document.getElementById('edit_total').value = this.dataset.total;
      document.getElementById('edit_location').value = this.dataset.location;

      new bootstrap.Modal(document.getElementById('editBookModal')).show();
    });
  });
</script>

<?php include '../includes/footer.php'; ?>