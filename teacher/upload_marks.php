<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'Upload Marks';
$teacher_id = $_SESSION['user_id'];

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'quiz';

// Get class details
if ($class_id) {
  $class_query = "SELECT * FROM classes WHERE id = $class_id";
  $class = mysqli_fetch_assoc(mysqli_query($conn, $class_query));
}

// Get subject details
if ($subject_id) {
  $subject_query = "SELECT * FROM subjects WHERE id = $subject_id";
  $subject = mysqli_fetch_assoc(mysqli_query($conn, $subject_query));
}

// Get teacher's classes and subjects for dropdown
$teacher_classes = mysqli_query($conn, "SELECT DISTINCT c.* 
                                        FROM classes c 
                                        JOIN class_subjects cs ON c.id = cs.class_id 
                                        WHERE cs.teacher_id = $teacher_id");

$teacher_subjects = mysqli_query($conn, "SELECT DISTINCT s.* 
                                         FROM subjects s 
                                         JOIN class_subjects cs ON s.id = cs.subject_id 
                                         WHERE cs.teacher_id = $teacher_id");

// Get students when class is selected
$students = [];
if ($class_id) {
  $students_query = "SELECT s.*, u.full_name 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.class_id = $class_id AND s.status = 'active' 
                       ORDER BY u.full_name";
  $students = mysqli_query($conn, $students_query);
}

// Get existing marks
$marks_data = [];
if ($class_id && $subject_id) {
  $marks_query = "SELECT student_id, marks_obtained, max_marks, remarks 
                    FROM marks 
                    WHERE class_id = $class_id AND subject_id = $subject_id AND exam_type = '$exam_type'";
  $marks_result = mysqli_query($conn, $marks_query);
  while ($mark = mysqli_fetch_assoc($marks_result)) {
    $marks_data[$mark['student_id']] = $mark;
  }
}

// Handle marks submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
  $marks = $_POST['marks'];
  $max_marks_default = (int)$_POST['max_marks'];
  $exam_date = sanitize($_POST['exam_date']);

  foreach ($marks as $student_id => $data) {
    $marks_obtained = (float)$data['obtained'];
    $max_marks = isset($data['max']) && !empty($data['max']) ? (float)$data['max'] : $max_marks_default;
    $remarks = sanitize($data['remarks']);

    // Check if marks already exist
    $check_query = "SELECT id FROM marks 
                        WHERE student_id = $student_id AND class_id = $class_id 
                        AND subject_id = $subject_id AND exam_type = '$exam_type'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
      // Update existing
      $update_query = "UPDATE marks 
                            SET marks_obtained = $marks_obtained, max_marks = $max_marks, 
                                remarks = '$remarks', exam_date = '$exam_date', added_by = $teacher_id 
                            WHERE student_id = $student_id AND class_id = $class_id 
                            AND subject_id = $subject_id AND exam_type = '$exam_type'";
      mysqli_query($conn, $update_query);
    } else {
      // Insert new
      $insert_query = "INSERT INTO marks (student_id, subject_id, class_id, exam_type, marks_obtained, max_marks, exam_date, remarks, added_by) 
                            VALUES ($student_id, $subject_id, $class_id, '$exam_type', $marks_obtained, $max_marks, '$exam_date', '$remarks', $teacher_id)";
      mysqli_query($conn, $insert_query);
    }
  }

  // Log activity
  logActivity($teacher_id, 'Uploaded marks for ' . $subject['subject_name'], "Class: {$class['class_name']}, Exam: $exam_type");

  $_SESSION['message'] = 'Marks saved successfully!';
  $_SESSION['message_type'] = 'success';
  redirect("teacher/upload_marks.php?class_id=$class_id&subject_id=$subject_id&exam_type=$exam_type");
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-12">
    <!-- Selection Panel -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-filter me-2"></i>Select Class, Subject & Exam
        </h6>
      </div>
      <div class="card-body">
        <form method="GET" action="" class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Select Class</label>
            <select class="form-select" name="class_id" onchange="this.form.submit()" required>
              <option value="">-- Select Class --</option>
              <?php while ($c = mysqli_fetch_assoc($teacher_classes)): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $class_id == $c['id'] ? 'selected' : ''; ?>>
                  <?php echo $c['class_name'] . ' ' . $c['section']; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Select Subject</label>
            <select class="form-select" name="subject_id" onchange="this.form.submit()" <?php echo !$class_id ? 'disabled' : ''; ?> required>
              <option value="">-- Select Subject --</option>
              <?php
              if ($class_id) {
                $subj_query = "SELECT s.* FROM subjects s 
                                              JOIN class_subjects cs ON s.id = cs.subject_id 
                                              WHERE cs.class_id = $class_id AND cs.teacher_id = $teacher_id";
                $subj_result = mysqli_query($conn, $subj_query);
                while ($s = mysqli_fetch_assoc($subj_result)):
              ?>
                  <option value="<?php echo $s['id']; ?>" <?php echo $subject_id == $s['id'] ? 'selected' : ''; ?>>
                    <?php echo $s['subject_name']; ?>
                  </option>
              <?php endwhile;
              } ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Exam Type</label>
            <select class="form-select" name="exam_type" onchange="this.form.submit()" <?php echo !$subject_id ? 'disabled' : ''; ?>>
              <option value="quiz" <?php echo $exam_type == 'quiz' ? 'selected' : ''; ?>>Quiz</option>
              <option value="assignment" <?php echo $exam_type == 'assignment' ? 'selected' : ''; ?>>Assignment</option>
              <option value="mid_term" <?php echo $exam_type == 'mid_term' ? 'selected' : ''; ?>>Mid Term Exam</option>
              <option value="final_term" <?php echo $exam_type == 'final_term' ? 'selected' : ''; ?>>Final Term Exam</option>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Marks Entry Form -->
    <?php if ($class_id && $subject_id): ?>
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
              <i class="fas fa-upload me-2"></i>Enter Marks - <?php echo $subject['subject_name']; ?> (<?php echo ucfirst(str_replace('_', ' ', $exam_type)); ?>)
            </h6>
            <span class="badge bg-primary"><?php echo $class['class_name'] . ' ' . $class['section']; ?></span>
          </div>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Default Max Marks</label>
                <input type="number" class="form-control" name="max_marks" value="100" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Exam Date</label>
                <input type="date" class="form-control" name="exam_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered">
                <thead style="background-color: #3b82f6; color: white;">
                  <tr class="text-center">
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Student Name</th>
                    <th style="width: 15%;">Admission No</th>
                    <th style="width: 15%;">Marks Obtained</th>
                    <th style="width: 15%;">Max Marks</th>
                    <th style="width: 15%;">Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $count = 1;
                  while ($student = mysqli_fetch_assoc($students)):
                    $existing = isset($marks_data[$student['id']]) ? $marks_data[$student['id']] : null;
                    $obtained = $existing ? $existing['marks_obtained'] : '';
                    $max = $existing ? $existing['max_marks'] : '';
                    $remarks = $existing ? $existing['remarks'] : '';
                  ?>
                    <tr>
                      <td class="text-center"><?php echo $count++; ?></td>
                      <td><?php echo $student['full_name']; ?></td>
                      <td class="text-center"><?php echo $student['admission_number']; ?></td>
                      <td>
                        <input type="number" step="0.01"
                          name="marks[<?php echo $student['id']; ?>][obtained]"
                          class="form-control form-control-sm"
                          value="<?php echo $obtained; ?>"
                          min="0" required>
                      </td>
                      <td>
                        <input type="number" step="0.01"
                          name="marks[<?php echo $student['id']; ?>][max]"
                          class="form-control form-control-sm"
                          value="<?php echo $max; ?>"
                          placeholder="Leave blank for default">
                      </td>
                      <td>
                        <input type="text"
                          name="marks[<?php echo $student['id']; ?>][remarks]"
                          class="form-control form-control-sm"
                          value="<?php echo htmlspecialchars($remarks); ?>"
                          placeholder="Optional">
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              <button type="submit" name="save_marks" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save Marks
              </button>
              <a href="dashboard.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
              </a>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>