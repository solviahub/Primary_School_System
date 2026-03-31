<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['student']);

$page_title = 'My Results';
$user_id = $_SESSION['user_id'];

// Get student details
$student_query = "SELECT s.*, u.full_name, c.class_name 
                  FROM students s 
                  JOIN users u ON s.user_id = u.id 
                  JOIN classes c ON s.class_id = c.id 
                  WHERE s.user_id = $user_id";
$student = mysqli_fetch_assoc(mysqli_query($conn, $student_query));

$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'final_term';

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-chart-line me-2"></i>Exam Type
        </h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="?exam_type=quiz"
            class="btn <?php echo $exam_type == 'quiz' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            Quiz
          </a>
          <a href="?exam_type=assignment"
            class="btn <?php echo $exam_type == 'assignment' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            Assignment
          </a>
          <a href="?exam_type=mid_term"
            class="btn <?php echo $exam_type == 'mid_term' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            Mid Term Exam
          </a>
          <a href="?exam_type=final_term"
            class="btn <?php echo $exam_type == 'final_term' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            Final Term Exam
          </a>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-info-circle me-2"></i>Student Info
        </h6>
      </div>
      <div class="card-body">
        <p class="mb-1"><strong>Name:</strong> <?php echo $student['full_name']; ?></p>
        <p class="mb-1"><strong>Admission No:</strong> <?php echo $student['admission_number']; ?></p>
        <p class="mb-1"><strong>Class:</strong> <?php echo $student['class_name']; ?></p>
        <p class="mb-0"><strong>Academic Year:</strong> <?php echo getCurrentAcademicYear(); ?></p>
      </div>
    </div>
  </div>

  <div class="col-md-9">
    <?php
    // Get subjects for this class
    $subjects_query = "SELECT s.* 
                           FROM subjects s 
                           JOIN class_subjects cs ON s.id = cs.subject_id 
                           WHERE cs.class_id = {$student['class_id']} 
                           ORDER BY s.subject_name";
    $subjects = mysqli_query($conn, $subjects_query);

    // Get marks for this student
    $marks_data = [];
    $total_marks = 0;
    $total_max_marks = 0;

    while ($subject = mysqli_fetch_assoc($subjects)) {
      $marks_query = "SELECT marks_obtained, max_marks, remarks 
                            FROM marks 
                            WHERE student_id = {$student['id']} 
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
        'percentage' => $max > 0 ? round(($obtained / $max) * 100, 2) : 0,
        'remarks' => $marks ? $marks['remarks'] : 'Not graded'
      ];

      $total_marks += $obtained;
      $total_max_marks += $max;
    }

    $overall_percentage = $total_max_marks > 0 ? round(($total_marks / $total_max_marks) * 100, 2) : 0;

    // Get teacher comments
    $comment_query = "SELECT teacher_comments FROM report_card_comments 
                          WHERE student_id = {$student['id']} AND class_id = {$student['class_id']} AND exam_type = '$exam_type'";
    $comment_result = mysqli_query($conn, $comment_query);
    $comment = mysqli_fetch_assoc($comment_result);
    ?>

    <!-- Report Card View -->
    <div class="card shadow-sm" id="reportCard">
      <div class="card-header bg-white border-bottom-0 pt-4">
        <div class="text-center">
          <h3 class="fw-bold mb-1" style="color: #3b82f6;"><?php echo getSetting('school_name', 'School Name'); ?></h3>
          <p class="text-muted mb-0"><?php echo getSetting('school_address', 'School Address'); ?></p>
          <hr>
          <h5 class="fw-bold">MY ACADEMIC REPORT CARD</h5>
          <p class="mb-0"><?php echo strtoupper(str_replace('_', ' ', $exam_type)); ?> - <?php echo getCurrentAcademicYear(); ?></p>
        </div>
      </div>

      <div class="card-body">
        <!-- Marks Table -->
        <div class="table-responsive mb-4">
          <table class="table table-bordered">
            <thead style="background-color: #3b82f6; color: white;">
              <tr class="text-center">
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">Subject</th>
                <th style="width: 20%;">Marks Obtained</th>
                <th style="width: 20%;">Max Marks</th>
                <th style="width: 15%;">Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $counter = 1;
              foreach ($marks_data as $data):
                $grade = '';
                $grade_color = '';
                if ($data['percentage'] >= 80) {
                  $grade = 'A+';
                  $grade_color = 'text-success';
                } elseif ($data['percentage'] >= 70) {
                  $grade = 'A';
                  $grade_color = 'text-success';
                } elseif ($data['percentage'] >= 60) {
                  $grade = 'B+';
                  $grade_color = 'text-primary';
                } elseif ($data['percentage'] >= 50) {
                  $grade = 'B';
                  $grade_color = 'text-primary';
                } elseif ($data['percentage'] >= 40) {
                  $grade = 'C';
                  $grade_color = 'text-warning';
                } else {
                  $grade = 'D';
                  $grade_color = 'text-danger';
                }
              ?>
                <tr>
                  <td class="text-center"><?php echo $counter++; ?></td>
                  <td>
                    <strong><?php echo $data['subject']; ?></strong>
                    <br><small class="text-muted">(<?php echo $data['subject_code']; ?>)</small>
                  </td>
                  <td class="text-center <?php echo $data['obtained'] == 0 ? 'text-danger' : ''; ?>">
                    <?php echo $data['obtained'] == 0 ? 'Not graded' : $data['obtained']; ?>
                  </td>
                  <td class="text-center"><?php echo $data['max']; ?></td>
                  <td class="text-center">
                    <?php if ($data['max'] > 0): ?>
                      <span class="fw-bold <?php echo $grade_color; ?>">
                        <?php echo $data['percentage']; ?>% (<?php echo $grade; ?>)
                      </span>
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot style="background-color: #f8f9fa;">
              <tr class="fw-bold">
                <td colspan="2" class="text-end">OVERALL PERFORMANCE:</td>
                <td class="text-center"><?php echo $total_marks; ?></td>
                <td class="text-center"><?php echo $total_max_marks; ?></td>
                <td class="text-center">
                  <?php
                  if ($overall_percentage >= 80) {
                    echo '<span class="fw-bold text-success">' . $overall_percentage . '% (Excellent)</span>';
                  } elseif ($overall_percentage >= 70) {
                    echo '<span class="fw-bold text-success">' . $overall_percentage . '% (Very Good)</span>';
                  } elseif ($overall_percentage >= 60) {
                    echo '<span class="fw-bold text-primary">' . $overall_percentage . '% (Good)</span>';
                  } elseif ($overall_percentage >= 50) {
                    echo '<span class="fw-bold text-primary">' . $overall_percentage . '% (Above Average)</span>';
                  } elseif ($overall_percentage >= 40) {
                    echo '<span class="fw-bold text-warning">' . $overall_percentage . '% (Average)</span>';
                  } else {
                    echo '<span class="fw-bold text-danger">' . $overall_percentage . '% (Needs Improvement)</span>';
                  }
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

      <div class="card-footer bg-white text-center py-3">
        <p class="text-muted small mb-0">This is an official report card. Keep it for your records.</p>
      </div>
    </div>

    <div class="mt-3 text-end">
      <button class="btn" style="background-color: #3b82f6; color: white;" onclick="printReportCard()">
        <i class="fas fa-print me-2"></i>Print Report Card
      </button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
  function printReportCard() {
    var printContents = document.getElementById('reportCard').innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>My Report Card</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                @media print {
                    body { margin: 0; padding: 0; }
                    .btn { display: none; }
                }
            </style>
        </head>
        <body>
            ${printContents}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
  }
</script>

<?php include '../includes/footer.php'; ?>