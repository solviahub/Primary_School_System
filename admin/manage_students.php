<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Manage Students';

// Handle student deletion
if (isset($_GET['delete'])) {
    $student_id = (int)$_GET['delete'];
    
    // First get the user_id from students table
    $query = "SELECT user_id FROM students WHERE id = $student_id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);
        $user_id = $student['user_id'];
        
        // Delete from students table
        $query1 = "DELETE FROM students WHERE id = $student_id";
        // Delete from users table
        $query2 = "DELETE FROM users WHERE id = $user_id AND role = 'student'";
        
        if (mysqli_query($conn, $query1) && mysqli_query($conn, $query2)) {
            $_SESSION['message'] = 'Student deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting student!';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Student not found!';
        $_SESSION['message_type'] = 'danger';
    }
    
    redirect('admin/manage_students.php');
}

// Handle student update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $student_id = (int)$_POST['student_id'];
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : 'NULL';
    $date_of_birth = !empty($_POST['date_of_birth']) ? "'" . sanitize($_POST['date_of_birth']) . "'" : 'NULL';
    $gender = sanitize($_POST['gender']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 'NULL';
    
    // Get user_id from students table
    $query = "SELECT user_id FROM students WHERE id = $student_id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);
        $user_id = $student['user_id'];
        
        // Update users table
        $query1 = "UPDATE users SET full_name='$full_name', email='$email', phone='$phone', address='$address' WHERE id=$user_id";
        
        // Update students table
        $query2 = "UPDATE students SET class_id=$class_id, parent_id=$parent_id, date_of_birth=$date_of_birth, gender='$gender' WHERE id=$student_id";
        
        if (mysqli_query($conn, $query1) && mysqli_query($conn, $query2)) {
            $_SESSION['message'] = 'Student updated successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error updating student: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    redirect('admin/manage_students.php');
}

// Get all students with their details
$query = "SELECT s.*, u.username, u.full_name, u.email, u.phone, u.address, u.status,
          c.class_name, c.section, 
          p.full_name as parent_name
          FROM students s
          LEFT JOIN users u ON s.user_id = u.id
          LEFT JOIN classes c ON s.class_id = c.id
          LEFT JOIN users p ON s.parent_id = p.id
          ORDER BY s.id DESC";
$students = mysqli_query($conn, $query);

// FIXED: Removed WHERE status = 'active' since classes table doesn't have status column
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name, section");

// Get parents for dropdown - Check if status column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
$has_status_column = mysqli_num_rows($check_column) > 0;

if ($has_status_column) {
    $parents = mysqli_query($conn, "SELECT * FROM users WHERE role = 'parent' AND status = 'active' ORDER BY full_name");
} else {
    $parents = mysqli_query($conn, "SELECT * FROM users WHERE role = 'parent' ORDER BY full_name");
}

include '../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-12">
        <a href="add_student.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Student
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['message'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-user-graduate me-2"></i>All Students</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Admission No.</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Class</th>
                        <th>Parent</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = mysqli_fetch_assoc($students)): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <?php 
                                if ($student['class_name']) {
                                    echo htmlspecialchars($student['class_name'] . ' ' . ($student['section'] ?? ''));
                                } else {
                                    echo '<span class="text-muted">Not Assigned</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['parent_name'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td>
                                <?php if (isset($student['status'])): ?>
                                    <span class="badge bg-<?php echo $student['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info edit-student" 
                                    data-id="<?php echo $student['id']; ?>"
                                    data-fullname="<?php echo htmlspecialchars($student['full_name']); ?>"
                                    data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                    data-phone="<?php echo htmlspecialchars($student['phone']); ?>"
                                    data-address="<?php echo htmlspecialchars($student['address'] ?? ''); ?>"
                                    data-class="<?php echo $student['class_id']; ?>"
                                    data-parent="<?php echo $student['parent_id']; ?>"
                                    data-dob="<?php echo $student['date_of_birth']; ?>"
                                    data-gender="<?php echo $student['gender']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger delete-confirm" onclick="return confirm('Are you sure you want to delete this student? This will also remove their user account.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" id="edit_fullname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" id="edit_gender">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class</label>
                            <select class="form-select" name="class_id" id="edit_class">
                                <option value="">Select Class</option>
                                <?php 
                                mysqli_data_seek($classes, 0);
                                while ($class = mysqli_fetch_assoc($classes)): 
                                ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name'] . ' ' . ($class['section'] ?? '')); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Parent</label>
                            <select class="form-select" name="parent_id" id="edit_parent">
                                <option value="">Select Parent</option>
                                <?php 
                                if ($parents && mysqli_num_rows($parents) > 0):
                                    mysqli_data_seek($parents, 0);
                                    while ($parent = mysqli_fetch_assoc($parents)): 
                                ?>
                                    <option value="<?php echo $parent['id']; ?>">
                                        <?php echo htmlspecialchars($parent['full_name']); ?>
                                    </option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="edit_dob">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit student button click
    document.querySelectorAll('.edit-student').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_student_id').value = this.dataset.id;
            document.getElementById('edit_fullname').value = this.dataset.fullname;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_phone').value = this.dataset.phone;
            document.getElementById('edit_address').value = this.dataset.address;
            document.getElementById('edit_class').value = this.dataset.class;
            document.getElementById('edit_parent').value = this.dataset.parent;
            document.getElementById('edit_dob').value = this.dataset.dob;
            document.getElementById('edit_gender').value = this.dataset.gender;
            
            new bootstrap.Modal(document.getElementById('editStudentModal')).show();
        });
    });
</script>

<?php include '../includes/footer.php'; ?>