<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['parent']);

$page_title = 'View Results';
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
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'final_term';

$student_info = null;
$marks_data = [];
$total_marks = 0;
$total_max_marks = 0;
$overall_percentage = 0;

if ($selected_child) {
  // Get student details
  $student_query = "SELECT s.*, u.full_name, c.class_name 
                      FROM students s 
                      JOIN users u ON s.user_id = u.id 
                      JOIN classes c ON s.class_id = c.id 
                      WHERE s.id = $selected_child";
  $student_info = mysqli_fetch_assoc(mysqli_query($conn, $student_query));

  if ($student_info) {
    // Get subjects for this class
    $subjects_query = "SELECT s.* 
                           FROM subjects s 
                           JOIN class_subjects cs ON s.id = cs.subject_id 
                           WHERE cs.class_id = {$student_info['class_id']} 
                           ORDER BY s.subject_name";
    $subjects = mysqli_query($conn, $subjects_query);

    // Get marks for this student
    while ($subject = mysqli_fetch_assoc($subjects)) {
      $marks_query = "SELECT marks_obtained, max_marks, remarks 
                            FROM marks 
                            WHERE student_id = $selected_child 
                            AND subject_id = {$subject['id']} 
                            AND exam_type = '$exam_type'
                            ORDER BY exam_date DESC LIMIT 1";
      $marks_result = mysqli_query($conn, $marks_query);
      $marks = mysqli_fetch_assoc($marks_result);

      $obtained = $marks ? $marks['marks_obtained'] : 0;
      $max = $marks ? $marks['max_marks'] : 0;

      $marks_data[] = [
        'subject' => $subject['subject_name'],
        'subject_code' => $subject['subject_code'],
        'obtained' => $obtained,
        'max' => $max,
        'percentage' => $max > 0 ? round(($obtained / $max) * 100, 2) : 0
      ];

      $total_marks += $obtained;
      $total_max_marks += $max;
    }

    $overall_percentage = $total_max_marks > 0 ? round(($total_marks / $total_max_marks) * 100, 2) : 0;

    // Get teacher comments
    $comment_query = "SELECT teacher_comments FROM report_card_comments 
                          WHERE student_id = $selected_child AND class_id = {$student_info['class_id']} AND exam_type = '$exam_type'";
    $comment_result = mysqli_query($conn, $comment_query);
    $comment = mysqli_fetch_assoc($comment_result);
  }
}

include '../includes/header.php';
?>

<style>
  .result-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .grade-A {
    color: #10b981;
  }

  .grade-B {
    color: #3b82f6;
  }

  .grade-C {
    color: #f59e0b;
  }

  .grade-D {
    color: #ef4444;
  }
</style>

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
            <a href="?student_id=<?php echo $child['id']; ?>&exam_type=<?php echo $exam_type; ?>"
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

    <?php if ($selected_child && $student_info): ?>
      <div class="card shadow-sm mt-3">
        <div class="card-header bg-white">
          <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
            <i class="fas fa-filter me-2"></i>Exam Type
          </h6>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="?student_id=<?php echo $selected_child; ?>&exam_type=quiz"
              class="btn <?php echo $exam_type == 'quiz' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
              Quiz
            </a>
            <a href="?student_id=<?php echo $selected_child; ?>&exam_type=assignment"
              class="btn <?php echo $exam_type == 'assignment' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
              Assignment
            </a>
            <a href="?student_id=<?php echo $selected_child; ?>&exam_type=mid_term"
              class="btn <?php echo $exam_type == 'mid_term' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
              Mid Term Exam
            </a>
            <a href="?student_id=<?php echo $selected_child; ?>&exam_type=final_term"
              class="btn <?php echo $exam_type == 'final_term' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
              Final Term Exam
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-md-9">
    <?php if ($selected_child && $student_info): ?>
      <div class="card shadow-sm" id="reportCard">
        <div class="card-header bg-white border-bottom-0 pt-4">
          <div class="text-center">
            <h3 class="fw-bold mb-1" style="color: #3b82f6;"><?php echo getSetting('school_name', 'School Name'); ?></h3>
            <p class="text-muted mb-0"><?php echo getSetting('school_address', 'School Address'); ?></p>
            <hr>
            <h5 class="fw-bold">ACADEMIC REPORT CARD</h5>
            <p><?php echo strtoupper(str_replace('_', ' ', $exam_type)); ?> - <?php echo getCurrentAcademicYear(); ?></p>
          </div>
        </div>
        <div class="card-body">
          <!-- Student Info -->
          <div class="row mb-4">
            <div class="col-md-6">
              <p><strong>Student Name:</strong> <?php echo $student_info['full_name']; ?></p>
              <p><strong>Admission No:</strong> <?php echo $student_info['admission_number']; ?></p>
            </div>
            <div class="col-md-6">
              <p><strong>Class:</strong> <?php echo $student_info['class_name']; ?></p>
              <p><strong>Report Date:</strong> <?php echo date('F d, Y'); ?></p>
            </div>
          </div>

          <!-- Marks Table -->
          <div class="table-responsive mb-4">
            <table class="table table-bordered">
              <thead style="background-color: #3b82f6; color: white;">
                <tr class="text-center">
                  <th>#</th>
                  <th>Subject</th>
                  <th>Marks Obtained</th>
                  <th>Max Marks</th>
                  <th>Percentage</th>
                  <th>Grade</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $counter = 1;
                foreach ($marks_data as $data):
                  $grade = '';
                  $grade_class = '';
                  if ($data['percentage'] >= 80) {
                    $grade = 'A+';
                    $grade_class = 'text-success';
                  } elseif ($data['percentage'] >= 70) {
                    $grade = 'A';
                    $grade_class = 'text-success';
                  } elseif ($data['percentage'] >= 60) {
                    $grade = 'B+';
                    $grade_class = 'text-primary';
                  } elseif ($data['percentage'] >= 50) {
                    $grade = 'B';
                    $grade_class = 'text-primary';
                  } elseif ($data['percentage'] >= 40) {
                    $grade = 'C';
                    $grade_class = 'text-warning';
                  } else {
                    $grade = 'D';
                    $grade_class = 'text-danger';
                  }
                ?>
                  <tr>
                    <td class="text-center"><?php echo $counter++; ?></td>
                    <td><strong><?php echo $data['subject']; ?></strong></td>
                    <td class="text-center"><?php echo $data['obtained']; ?></td>
                    <td class="text-center"><?php echo $data['max']; ?></td>
                    <td class="text-center"><?php echo $data['percentage']; ?>%</td>
                    <td class="text-center"><span class="fw-bold <?php echo $grade_class; ?>"><?php echo $grade; ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot style="background-color: #f8f9fa;">
                <tr class="fw-bold">
                  <td colspan="2" class="text-end">TOTAL:</td>
                  <td class="text-center"><?php echo $total_marks; ?></td>
                  <td class="text-center"><?php echo $total_max_marks; ?></td>
                  <td class="text-center"><?php echo $overall_percentage; ?>%</td>
                  <td class="text-center">
                    <?php
                    if ($overall_percentage >= 80) echo '<span class="text-success">Excellent</span>';
                    elseif ($overall_percentage >= 60) echo '<span class="text-primary">Good</span>';
                    elseif ($overall_percentage >= 40) echo '<span class="text-warning">Average</span>';
                    else echo '<span class="text-danger">Needs Improvement</span>';
                    ?>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Teacher Comments -->
          <?php if ($comment && $comment['teacher_comments']): ?>
            <div class="alert alert-info">
              <i class="fas fa-chalkboard-user me-2"></i>
              <strong>Teacher's Remarks:</strong>
              <hr class="my-2">
              <?php echo nl2br(htmlspecialchars($comment['teacher_comments'])); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-3 text-end">
        <button class="btn" style="background-color: #3b82f6; color: white;" onclick="window.print()">
          <i class="fas fa-print me-2"></i>Print Report Card
        </button>
      </div>

    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-child fa-4x text-muted mb-3"></i>
          <h5>Select a child to view results</h5>
          <p class="text-muted">Choose a child from the left panel to view their academic performance.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>