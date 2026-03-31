<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['teacher']);

$page_title = 'Generate Report Cards';
$teacher_id = $_SESSION['user_id'];

// Get teacher's classes
$classes_query = "SELECT DISTINCT c.* 
                  FROM classes c 
                  JOIN class_subjects cs ON c.id = cs.class_id 
                  WHERE cs.teacher_id = $teacher_id";
$classes = mysqli_query($conn, $classes_query);

// Get selected class and student
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'final_term';

// Get students in selected class
$students = [];
if ($selected_class) {
  $students_query = "SELECT s.*, u.full_name 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.class_id = $selected_class AND s.status = 'active'
                       ORDER BY u.full_name";
  $students = mysqli_query($conn, $students_query);
}

// Handle report card generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_report'])) {
  $student_id = (int)$_POST['student_id'];
  $class_id = (int)$_POST['class_id'];
  $exam_type = sanitize($_POST['exam_type']);
  $teacher_comments = sanitize($_POST['teacher_comments']);

  // Save teacher comments
  $comment_query = "INSERT INTO report_card_comments (student_id, class_id, exam_type, teacher_comments, created_by) 
                      VALUES ($student_id, $class_id, '$exam_type', '$teacher_comments', $teacher_id)
                      ON DUPLICATE KEY UPDATE teacher_comments = '$teacher_comments'";
  mysqli_query($conn, $comment_query);

  $_SESSION['message'] = 'Report card generated successfully!';
  $_SESSION['message_type'] = 'success';
  redirect("teacher/report_cards.php?class_id=$class_id&student_id=$student_id&exam_type=$exam_type");
}

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-filter me-2"></i>Select Student
        </h6>
      </div>
      <div class="card-body">
        <form method="GET" action="">
          <div class="mb-3">
            <label class="form-label fw-semibold">Select Class</label>
            <select class="form-select" name="class_id" onchange="this.form.submit()" required>
              <option value="">-- Select Class --</option>
              <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                  <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <?php if ($selected_class): ?>
            <div class="mb-3">
              <label class="form-label fw-semibold">Select Student</label>
              <select class="form-select" name="student_id" onchange="this.form.submit()" required>
                <option value="">-- Select Student --</option>
                <?php while ($student = mysqli_fetch_assoc($students)): ?>
                  <option value="<?php echo $student['id']; ?>" <?php echo $selected_student == $student['id'] ? 'selected' : ''; ?>>
                    <?php echo $student['full_name'] . ' (' . $student['admission_number'] . ')'; ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          <?php endif; ?>

          <?php if ($selected_student): ?>
            <div class="mb-3">
              <label class="form-label fw-semibold">Exam Type</label>
              <select class="form-select" name="exam_type" onchange="this.form.submit()" required>
                <option value="quiz" <?php echo $exam_type == 'quiz' ? 'selected' : ''; ?>>Quiz</option>
                <option value="assignment" <?php echo $exam_type == 'assignment' ? 'selected' : ''; ?>>Assignment</option>
                <option value="mid_term" <?php echo $exam_type == 'mid_term' ? 'selected' : ''; ?>>Mid Term</option>
                <option value="final_term" <?php echo $exam_type == 'final_term' ? 'selected' : ''; ?>>Final Term</option>
              </select>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <?php if ($selected_student):
      // Get student details
      $student_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, u.full_name, u.email, u.phone, c.class_name 
                                                                   FROM students s 
                                                                   JOIN users u ON s.user_id = u.id 
                                                                   JOIN classes c ON s.class_id = c.id 
                                                                   WHERE s.id = $selected_student"));

      // Get subjects for this class
      $subjects_query = "SELECT s.*, cs.teacher_id, u.full_name as teacher_name 
                               FROM subjects s 
                               JOIN class_subjects cs ON s.id = cs.subject_id 
                               LEFT JOIN users u ON cs.teacher_id = u.id 
                               WHERE cs.class_id = $selected_class 
                               ORDER BY s.subject_name";
      $subjects = mysqli_query($conn, $subjects_query);

      // Get marks for this student
      $marks_data = [];
      $total_marks = 0;
      $total_max_marks = 0;

      while ($subject = mysqli_fetch_assoc($subjects)) {
        $marks_query = "SELECT marks_obtained, max_marks, remarks 
                                FROM marks 
                                WHERE student_id = $selected_student 
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
          'teacher' => $subject['teacher_name'],
          'obtained' => $obtained,
          'max' => $max,
          'percentage' => $max > 0 ? round(($obtained / $max) * 100, 2) : 0,
          'remarks' => $marks ? $marks['remarks'] : 'Not graded'
        ];

        $total_marks += $obtained;
        $total_max_marks += $max;
      }

      $overall_percentage = $total_max_marks > 0 ? round(($total_marks / $total_max_marks) * 100, 2) : 0;

      // Get previous comments
      $comment_query = "SELECT teacher_comments FROM report_card_comments 
                              WHERE student_id = $selected_student AND class_id = $selected_class AND exam_type = '$exam_type'";
      $comment_result = mysqli_query($conn, $comment_query);
      $existing_comment = mysqli_fetch_assoc($comment_result);
    ?>

      <!-- Report Card Preview -->
      <div class="card shadow-sm" id="reportCard">
        <div class="card-header bg-white border-bottom-0 pt-4">
          <div class="text-center">
            <h3 class="fw-bold mb-1" style="color: #3b82f6;"><?php echo getSetting('school_name', 'School Name'); ?></h3>
            <p class="text-muted mb-0"><?php echo getSetting('school_address', 'School Address'); ?></p>
            <p class="text-muted">Tel: <?php echo getSetting('school_phone', 'Phone'); ?> | Email: <?php echo getSetting('school_email', 'Email'); ?></p>
            <hr>
            <h5 class="fw-bold">ACADEMIC REPORT CARD</h5>
            <p class="mb-0"><?php echo strtoupper(str_replace('_', ' ', $exam_type)); ?> - <?php echo getCurrentAcademicYear(); ?></p>
          </div>
        </div>

        <div class="card-body">
          <!-- Student Information -->
          <div class="row mb-4">
            <div class="col-md-6">
              <table class="table table-borderless table-sm">
                <tr>
                  <td class="fw-semibold" style="width: 120px;">Student Name:</td>
                  <td><strong><?php echo $student_info['full_name']; ?></strong></td>
                </tr>
                <tr>
                  <td class="fw-semibold">Admission No:</td>
                  <td><?php echo $student_info['admission_number']; ?></td>
                </tr>
                <tr>
                  <td class="fw-semibold">Class:</td>
                  <td><?php echo $student_info['class_name']; ?></td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-borderless table-sm">
                <tr>
                  <td class="fw-semibold" style="width: 120px;">Date of Birth:</td>
                  <td><?php echo formatDate($student_info['date_of_birth']); ?></td>
                </tr>
                <tr>
                  <td class="fw-semibold">Gender:</td>
                  <td><?php echo ucfirst($student_info['gender']); ?></td>
                </tr>
                <tr>
                  <td class="fw-semibold">Report Date:</td>
                  <td><?php echo date('F d, Y'); ?></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Marks Table -->
          <div class="table-responsive mb-4">
            <table class="table table-bordered">
              <thead style="background-color: #3b82f6; color: white;">
                <tr class="text-center">
                  <th style="width: 5%;">#</th>
                  <th style="width: 30%;">Subject</th>
                  <th style="width: 15%;">Marks Obtained</th>
                  <th style="width: 15%;">Max Marks</th>
                  <th style="width: 15%;">Percentage</th>
                  <th style="width: 20%;">Grade</th>
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
                    <td>
                      <strong><?php echo $data['subject']; ?></strong>
                      <br><small class="text-muted">(<?php echo $data['subject_code']; ?>)</small>
                    </td>
                    <td class="text-center"><?php echo $data['obtained']; ?></td>
                    <td class="text-center"><?php echo $data['max']; ?></td>
                    <td class="text-center"><?php echo $data['percentage']; ?>%</td>
                    <td class="text-center">
                      <span class="fw-bold <?php echo $grade_class; ?>" style="font-size: 1.1rem;"><?php echo $grade; ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot style="background-color: #f8f9fa;">
                <tr class="fw-bold">
                  <td colspan="2" class="text-end">TOTAL / OVERALL:</td>
                  <td class="text-center"><?php echo $total_marks; ?></td>
                  <td class="text-center"><?php echo $total_max_marks; ?></td>
                  <td class="text-center"><?php echo $overall_percentage; ?>%</td>
                  <td class="text-center">
                    <?php
                    if ($overall_percentage >= 80) {
                      echo '<span class="fw-bold text-success">A+ (Excellent)</span>';
                    } elseif ($overall_percentage >= 70) {
                      echo '<span class="fw-bold text-success">A (Very Good)</span>';
                    } elseif ($overall_percentage >= 60) {
                      echo '<span class="fw-bold text-primary">B+ (Good)</span>';
                    } elseif ($overall_percentage >= 50) {
                      echo '<span class="fw-bold text-primary">B (Above Average)</span>';
                    } elseif ($overall_percentage >= 40) {
                      echo '<span class="fw-bold text-warning">C (Average)</span>';
                    } else {
                      echo '<span class="fw-bold text-danger">D (Needs Improvement)</span>';
                    }
                    ?>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Performance Summary -->
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="card bg-light">
                <div class="card-body">
                  <h6 class="fw-bold mb-3" style="color: #3b82f6;">Performance Summary</h6>
                  <table class="table table-sm table-borderless">
                    <tr>
                      <td>Highest Score:</td>
                      <td class="fw-bold"><?php
                                          $max_score = max(array_column($marks_data, 'percentage'));
                                          echo $max_score . '%';
                                          ?></td>
                    </tr>
                    <tr>
                      <td>Lowest Score:</td>
                      <td class="fw-bold"><?php
                                          $min_score = min(array_column($marks_data, 'percentage'));
                                          echo $min_score . '%';
                                          ?></td>
                    </tr>
                    <tr>
                      <td>Average Score:</td>
                      <td class="fw-bold"><?php echo $overall_percentage; ?>%</td>
                    </tr>
                    <tr>
                      <td>Subjects Passed:</td>
                      <td class="fw-bold"><?php
                                          $passed = 0;
                                          foreach ($marks_data as $data) {
                                            if ($data['percentage'] >= 40) $passed++;
                                          }
                                          echo $passed . ' / ' . count($marks_data);
                                          ?></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-light">
                <div class="card-body">
                  <h6 class="fw-bold mb-3" style="color: #3b82f6;">Grading Scale</h6>
                  <div class="row">
                    <div class="col-6">
                      <small>80% - 100%: <span class="fw-bold text-success">A+ (Excellent)</span></small><br>
                      <small>70% - 79%: <span class="fw-bold text-success">A (Very Good)</span></small><br>
                      <small>60% - 69%: <span class="fw-bold text-primary">B+ (Good)</span></small>
                    </div>
                    <div class="col-6">
                      <small>50% - 59%: <span class="fw-bold text-primary">B (Above Average)</span></small><br>
                      <small>40% - 49%: <span class="fw-bold text-warning">C (Average)</span></small><br>
                      <small>0% - 39%: <span class="fw-bold text-danger">D (Needs Improvement)</span></small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Teacher Comments Form -->
          <form method="POST" action="" class="mb-4">
            <input type="hidden" name="student_id" value="<?php echo $selected_student; ?>">
            <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
            <input type="hidden" name="exam_type" value="<?php echo $exam_type; ?>">

            <div class="card">
              <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
                  <i class="fas fa-comment me-2"></i>Teacher's Comments
                </h6>
              </div>
              <div class="card-body">
                <textarea class="form-control" name="teacher_comments" rows="4" placeholder="Enter comments about student's performance, strengths, areas for improvement, and recommendations..."><?php echo htmlspecialchars($existing_comment['teacher_comments'] ?? ''); ?></textarea>
              </div>
              <div class="card-footer bg-white">
                <button type="submit" name="generate_report" class="btn" style="background-color: #3b82f6; color: white;">
                  <i class="fas fa-save me-2"></i>Save Report Card
                </button>
                <button type="button" class="btn btn-outline-secondary ms-2" onclick="printReportCard()">
                  <i class="fas fa-print me-2"></i>Print Report Card
                </button>
                <button type="button" class="btn btn-outline-success ms-2" onclick="downloadAsPDF()">
                  <i class="fas fa-download me-2"></i>Download PDF
                </button>
              </div>
            </div>
          </form>

          <!-- Display saved comments -->
          <?php if ($existing_comment && $existing_comment['teacher_comments']): ?>
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Teacher's Remarks:</strong><br>
              <?php echo nl2br(htmlspecialchars($existing_comment['teacher_comments'])); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="card-footer bg-white text-center py-3">
          <div class="row">
            <div class="col-md-6 text-md-start">
              <p class="mb-0"><small>Class Teacher Signature: ___________________</small></p>
            </div>
            <div class="col-md-6 text-md-end">
              <p class="mb-0"><small>Principal/Headmaster Signature: ___________________</small></p>
            </div>
          </div>
          <hr>
          <p class="text-muted small mb-0">This is a computer-generated report card and does not require a physical signature.</p>
        </div>
      </div>

    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
          <h5>Select a student to generate report card</h5>
          <p class="text-muted">Choose a class and student from the left panel to view and generate report cards</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add html2pdf library for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
  function printReportCard() {
    var printContents = document.getElementById('reportCard').innerHTML;
    var originalContents = document.body.innerHTML;

    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Student Report Card</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                @media print {
                    body { margin: 0; padding: 0; }
                    .btn, .no-print { display: none; }
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

  function downloadAsPDF() {
    var element = document.getElementById('reportCard');
    var opt = {
      margin: [0.5, 0.5, 0.5, 0.5],
      filename: 'Report_Card_<?php echo $student_info['admission_number'] ?? 'Student'; ?>_<?php echo $exam_type; ?>.pdf',
      image: {
        type: 'jpeg',
        quality: 0.98
      },
      html2canvas: {
        scale: 2,
        letterRendering: true
      },
      jsPDF: {
        unit: 'in',
        format: 'a4',
        orientation: 'portrait'
      }
    };
    html2pdf().set(opt).from(element).save();
  }
</script>

<style>
  @media print {

    .sidebar,
    .navbar,
    .btn-toolbar,
    .col-md-4,
    .btn,
    .no-print {
      display: none !important;
    }

    .col-md-8 {
      width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
    }
  }
</style>

<?php include '../includes/footer.php'; ?>