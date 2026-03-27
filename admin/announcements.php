<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Announcements';

// Handle announcement creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
  $title = sanitize($_POST['title']);
  $content = sanitize($_POST['content']);
  $target_role = sanitize($_POST['target_role']);
  $expiry_date = sanitize($_POST['expiry_date']);
  $created_by = $_SESSION['user_id'];

  $query = "INSERT INTO announcements (title, content, target_role, expiry_date, created_by) 
              VALUES ('$title', '$content', '$target_role', '$expiry_date', $created_by)";

  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Announcement posted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error posting announcement!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/announcements.php');
}

// Handle announcement deletion
if (isset($_GET['delete'])) {
  $announcement_id = (int)$_GET['delete'];
  $query = "DELETE FROM announcements WHERE id = $announcement_id";
  if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = 'Announcement deleted successfully!';
    $_SESSION['message_type'] = 'success';
  } else {
    $_SESSION['message'] = 'Error deleting announcement!';
    $_SESSION['message_type'] = 'danger';
  }
  redirect('admin/announcements.php');
}

// Get all announcements
$announcements = mysqli_query($conn, "SELECT a.*, u.full_name as author 
                                      FROM announcements a 
                                      JOIN users u ON a.created_by = u.id 
                                      ORDER BY a.created_at DESC");

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>All Announcements</h6>
      </div>
      <div class="card-body">
        <?php if (mysqli_num_rows($announcements) > 0): ?>
          <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
            <div class="announcement-item mb-4 pb-3 border-bottom">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5><?php echo htmlspecialchars($announcement['title']); ?></h5>
                  <div class="mb-2">
                    <span class="badge bg-<?php
                                          echo $announcement['target_role'] == 'all' ? 'primary' : ($announcement['target_role'] == 'teacher' ? 'info' : ($announcement['target_role'] == 'parent' ? 'success' : 'warning'));
                                          ?>">
                      <i class="fas fa-users me-1"></i>
                      Target: <?php echo ucfirst($announcement['target_role']); ?>
                    </span>
                    <?php if ($announcement['expiry_date']): ?>
                      <span class="badge bg-secondary">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Expires: <?php echo date('M d, Y', strtotime($announcement['expiry_date'])); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                  <div class="text-muted small">
                    <i class="fas fa-user me-1"></i> Posted by: <?php echo $announcement['author']; ?>
                    <i class="fas fa-clock ms-3 me-1"></i> <?php echo date('M d, Y h:i A', strtotime($announcement['created_at'])); ?>
                  </div>
                </div>
                <div>
                  <a href="?delete=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                    <i class="fas fa-trash"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
            <p class="text-muted">No announcements yet</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Post New Announcement</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" class="form-control" name="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Content *</label>
            <textarea class="form-control" name="content" rows="5" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Target Audience *</label>
            <select class="form-select" name="target_role" required>
              <option value="all">Everyone (All Users)</option>
              <option value="admin">Administrators Only</option>
              <option value="teacher">Teachers Only</option>
              <option value="parent">Parents Only</option>
              <option value="student">Students Only</option>
              <option value="librarian">Librarians Only</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Expiry Date (Optional)</label>
            <input type="date" class="form-control" name="expiry_date">
            <small class="text-muted">Leave empty for no expiry</small>
          </div>
          <button type="submit" name="add_announcement" class="btn btn-primary w-100">
            <i class="fas fa-paper-plane me-2"></i>Post Announcement
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>