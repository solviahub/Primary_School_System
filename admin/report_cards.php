<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'Generate Report Cards';

// Get all classes
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name, section");

// Get selected filters
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$selected_exam = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'final_term';
$generation_type = isset($_GET['type']) ? $_GET['type'] : 'single';

// Get students for selected class
$students = [];
if ($selected_class) {
  $students_query = "SELECT s.*, u.full_name, u.email 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.class_id = $selected_class AND s.status = 'active' 
                       ORDER BY u.full_name";
  $students = mysqli_query($conn, $students_query);
}

// Get school settings
$school_name = getSetting('school_name', 'Secondary School');
$school_address = getSetting('school_address', '123 Education Street');
$school_phone = getSetting('school_phone', '+1234567890');
$school_email = getSetting('school_email', 'info@school.com');
$school_logo = getSetting('school_logo', '');
$academic_year = getCurrentAcademicYear();

include '../includes/header.php';
?>

<style>
  .report-card-container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    page-break-after: always;
  }

  .report-header {
    text-align: center;
    margin-bottom: 30px;
    border-bottom: 2px solid #3b82f6;
    padding-bottom: 20px;
  }

  .report-title {
    color: #3b82f6;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
  }

  .grade-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
  }

  .grade-table th {
    background: #3b82f6;
    color: white;
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
  }

  .grade-table td {
    padding: 8px;
    text-align: center;
    border: 1px solid #ddd;
  }

  .signature-line {
    margin-top: 40px;
    display: flex;
    justify-content: space-between;
  }

  .bulk-actions {
    position: sticky;
    top: 0;
    background: white;
    z-index: 100;
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 20px;
  }

  @media print {
    .no-print {
      display: none !important;
    }

    .report-card-container {
      box-shadow: none;
      padding: 0;
      margin: 0;
      page-break-after: always;
    }

    .report-card-container:last-child {
      page-break-after: auto;
    }
  }
</style>

<div class="no-print">
  <!-- Generation Type Tabs -->
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?php echo $generation_type == 'single' ? 'active' : ''; ?>"
        href="?type=single&class_id=<?php echo $selected_class; ?>&student_id=<?php echo $selected_student; ?>&exam_type=<?php echo $selected_exam; ?>">
        <i class="fas fa-user me-2"></i>Single Student Report Card
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $generation_type == 'bulk' ? 'active' : ''; ?>"
        href="?type=bulk&class_id=<?php echo $selected_class; ?>&exam_type=<?php echo $selected_exam; ?>">
        <i class="fas fa-users me-2"></i>Bulk Report Cards (Entire Class)
      </a>
    </li>
  </ul>

  <!-- Filters -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
      <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
        <i class="fas fa-filter me-2"></i>Select Options
      </h6>
    </div>
    <div class="card-body">
      <form method="GET" action="" id="filterForm">
        <input type="hidden" name="type" value="<?php echo $generation_type; ?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Select Class *</label>
            <select class="form-select" name="class_id" required onchange="updateStudents()">
              <option value="">-- Select Class --</option>
              <?php while ($class = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                  <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <?php if ($generation_type == 'single'): ?>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Select Student</label>
              <select class="form-select" name="student_id" id="studentSelect">
                <option value="">-- Select Student --</option>
                <?php if ($students): ?>
                  <?php while ($student = mysqli_fetch_assoc($students)): ?>
                    <option value="<?php echo $student['id']; ?>" <?php echo $selected_student == $student['id'] ? 'selected' : ''; ?>>
                      <?php echo $student['full_name'] . ' (' . $student['admission_number'] . ')'; ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Exam Type</label>
            <select class="form-select" name="exam_type">
              <option value="quiz" <?php echo $selected_exam == 'quiz' ? 'selected' : ''; ?>>Quiz</option>
              <option value="assignment" <?php echo $selected_exam == 'assignment' ? 'selected' : ''; ?>>Assignment</option>
              <option value="mid_term" <?php echo $selected_exam == 'mid_term' ? 'selected' : ''; ?>>Mid Term Exam</option>
              <option value="final_term" <?php echo $selected_exam == 'final_term' ? 'selected' : ''; ?>>Final Term Exam</option>
            </select>
          </div>

          <div class="col-md-12">
            <button type="submit" class="btn" style="background-color: #3b82f6; color: white;">
              <i class="fas fa-search me-2"></i>Generate Report Card
            </button>

            <?php if ($generation_type == 'bulk' && $selected_class): ?>
              <button type="button" class="btn btn-success ms-2" onclick="printAllReportCards()">
                <i class="fas fa-print me-2"></i>Print All (<?php echo mysqli_num_rows($students); ?> Students)
              </button>
              <button type="button" class="btn btn-info ms-2" onclick="downloadAllPDF()">
                <i class="fas fa-download me-2"></i>Download All as PDF
              </button>
            <?php endif; ?>

            <?php if ($generation_type == 'single' && $selected_student): ?>
              <button type="button" class="btn btn-success ms-2" onclick="printSingleReportCard()">
                <i class="fas fa-print me-2"></i>Print
              </button>
              <button type="button" class="btn btn-info ms-2" onclick="downloadSinglePDF()">
                <i class="fas fa-download me-2"></i>Download PDF
              </button>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function updateStudents() {
    var classId = document.querySelector('select[name="class_id"]').value;
    var type = '<?php echo $generation_type; ?>';
    if (type == 'single') {
      window.location.href = '?type=single&class_id=' + classId + '&exam_type=<?php echo $selected_exam; ?>';
    } else {
      window.location.href = '?type=bulk&class_id=' + classId + '&exam_type=<?php echo $selected_exam; ?>';
    }
  }
</script>

<?php
// Function to get student marks and generate report card
function getStudentMarksData($conn, $student_id, $class_id, $exam_type)
{
  // Get subjects for this class
  $subjects_query = "SELECT s.*, cs.teacher_id, u.full_name as teacher_name 
                       FROM subjects s 
                       JOIN class_subjects cs ON s.id = cs.subject_id 
                       LEFT JOIN users u ON cs.teacher_id = u.id 
                       WHERE cs.class_id = $class_id 
                       ORDER BY s.subject_name";
  $subjects = mysqli_query($conn, $subjects_query);

  $marks_data = [];
  $total_marks = 0;
  $total_max_marks = 0;

  while ($subject = mysqli_fetch_assoc($subjects)) {
    $marks_query = "SELECT marks_obtained, max_marks, remarks 
                        FROM marks 
                        WHERE student_id = $student_id 
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
      'percentage' => $max > 0 ? round(($obtained / $max) * 100, 2) : 0
    ];

    $total_marks += $obtained;
    $total_max_marks += $max;
  }

  $overall_percentage = $total_max_marks > 0 ? round(($total_marks / $total_max_marks) * 100, 2) : 0;

  // Get teacher comments
  $comment_query = "SELECT teacher_comments FROM report_card_comments 
                      WHERE student_id = $student_id AND class_id = $class_id AND exam_type = '$exam_type'";
  $comment_result = mysqli_query($conn, $comment_query);
  $comment = mysqli_fetch_assoc($comment_result);

  return [
    'marks_data' => $marks_data,
    'total_marks' => $total_marks,
    'total_max_marks' => $total_max_marks,
    'overall_percentage' => $overall_percentage,
    'comments' => $comment ? $comment['teacher_comments'] : null
  ];
}

// Display Report Cards
if ($selected_class) {
  if ($generation_type == 'single' && $selected_student) {
    // Single Report Card
    $student_query = "SELECT s.*, u.full_name, u.email, c.class_name 
                         FROM students s 
                         JOIN users u ON s.user_id = u.id 
                         JOIN classes c ON s.class_id = c.id 
                         WHERE s.id = $selected_student";
    $student = mysqli_fetch_assoc(mysqli_query($conn, $student_query));

    if ($student) {
      $marks = getStudentMarksData($conn, $selected_student, $selected_class, $selected_exam);
      displayReportCard($student, $marks, $school_name, $school_address, $school_phone, $school_email, $academic_year, $selected_exam);
    }
  } elseif ($generation_type == 'bulk') {
    // Bulk Report Cards for entire class
    $students_query = "SELECT s.*, u.full_name, u.email, c.class_name 
                          FROM students s 
                          JOIN users u ON s.user_id = u.id 
                          JOIN classes c ON s.class_id = c.id 
                          WHERE s.class_id = $selected_class AND s.status = 'active' 
                          ORDER BY u.full_name";
    $bulk_students = mysqli_query($conn, $students_query);

    if (mysqli_num_rows($bulk_students) > 0) {
      echo '<div id="bulkReportCards">';
      $counter = 1;
      while ($student = mysqli_fetch_assoc($bulk_students)) {
        $marks = getStudentMarksData($conn, $student['id'], $selected_class, $selected_exam);
        displayReportCard($student, $marks, $school_name, $school_address, $school_phone, $school_email, $academic_year, $selected_exam, $counter, mysqli_num_rows($bulk_students));
        $counter++;
      }
      echo '</div>';
    } else {
      echo '<div class="alert alert-warning">No students found in this class!</div>';
    }
  }
}

function displayReportCard($student, $marks, $school_name, $school_address, $school_phone, $school_email, $academic_year, $exam_type, $counter = null, $total = null)
{
  $grade = '';
  $grade_text = '';
  if ($marks['overall_percentage'] >= 80) {
    $grade = 'A+';
    $grade_text = 'Excellent';
  } elseif ($marks['overall_percentage'] >= 70) {
    $grade = 'A';
    $grade_text = 'Very Good';
  } elseif ($marks['overall_percentage'] >= 60) {
    $grade = 'B+';
    $grade_text = 'Good';
  } elseif ($marks['overall_percentage'] >= 50) {
    $grade = 'B';
    $grade_text = 'Above Average';
  } elseif ($marks['overall_percentage'] >= 40) {
    $grade = 'C';
    $grade_text = 'Average';
  } else {
    $grade = 'D';
    $grade_text = 'Needs Improvement';
  }
?>
  <div class="report-card-container" id="reportCard_<?php echo $student['id']; ?>">
    <?php if ($counter): ?>
      <div class="text-end mb-2 no-print">
        <small class="text-muted">Report <?php echo $counter; ?> of <?php echo $total; ?></small>
      </div>
    <?php endif; ?>

    <div class="report-header">
      <h2 class="report-title"><?php echo $school_name; ?></h2>
      <p><?php echo $school_address; ?></p>
      <p>Tel: <?php echo $school_phone; ?> | Email: <?php echo $school_email; ?></p>
      <hr>
      <h4>ACADEMIC REPORT CARD</h4>
      <p><strong><?php echo strtoupper(str_replace('_', ' ', $exam_type)); ?></strong> - <?php echo $academic_year; ?></p>
    </div>

    <div class="row mb-4">
      <div class="col-md-6">
        <table style="width: 100%;">
          <tr>
            <td><strong>Student Name:</strong></td>
            <td><?php echo $student['full_name']; ?></td>
          </tr>
          <tr>
            <td><strong>Admission No:</strong></td>
            <td><?php echo $student['admission_number']; ?></td>
          </tr>
          <tr>
            <td><strong>Class:</strong></td>
            <td><?php echo $student['class_name']; ?></td>
          </tr>
          <tr>
            <td><strong>Date of Birth:</strong></td>
            <td><?php echo date('d M, Y', strtotime($student['date_of_birth'])); ?></td>
          </tr>
        </table>
      </div>
      <div class="col-md-6">
        <table style="width: 100%;">
          <tr>
            <td><strong>Gender:</strong></td>
            <td><?php echo ucfirst($student['gender']); ?></td>
          </tr>
          <tr>
            <td><strong>Report Date:</strong></td>
            <td><?php echo date('d M, Y'); ?></td>
          </tr>
          <tr>
            <td><strong>Academic Year:</strong></td>
            <td><?php echo $academic_year; ?></td>
          </tr>
        </table>
      </div>
    </div>

    <table class="grade-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Subject</th>
          <th>Subject Code</th>
          <th>Marks Obtained</th>
          <th>Max Marks</th>
          <th>Percentage</th>
          <th>Grade</th>
        </tr>
      </thead>
      <tbody>
        <?php $counter = 1;
        foreach ($marks['marks_data'] as $data):
          $subj_grade = '';
          if ($data['percentage'] >= 80) $subj_grade = 'A+';
          elseif ($data['percentage'] >= 70) $subj_grade = 'A';
          elseif ($data['percentage'] >= 60) $subj_grade = 'B+';
          elseif ($data['percentage'] >= 50) $subj_grade = 'B';
          elseif ($data['percentage'] >= 40) $subj_grade = 'C';
          else $subj_grade = 'D';
        ?>
          <tr>
            <td><?php echo $counter++; ?></td>
            <td><?php echo $data['subject']; ?></td>
            <td><?php echo $data['subject_code']; ?></td>
            <td><?php echo $data['obtained']; ?></td>
            <td><?php echo $data['max']; ?></td>
            <td><?php echo $data['percentage']; ?>%</td>
            <td><strong><?php echo $subj_grade; ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background: #f8f9fa; font-weight: bold;">
          <td colspan="3" style="text-align: right;">TOTAL / OVERALL:</td>
          <td><?php echo $marks['total_marks']; ?></td>
          <td><?php echo $marks['total_max_marks']; ?></td>
          <td colspan="2"><?php echo $marks['overall_percentage']; ?>% (<?php echo $grade; ?> - <?php echo $grade_text; ?>)</td>
        </tr>
      </tfoot>
    </table>

    <?php if ($marks['comments']): ?>
      <div class="alert alert-info mt-3">
        <strong>Teacher's Remarks:</strong><br>
        <?php echo nl2br(htmlspecialchars($marks['comments'])); ?>
      </div>
    <?php endif; ?>

    <div class="signature-line">
      <div>
        <p>_____________________</p>
        <p><small>Class Teacher</small></p>
      </div>
      <div>
        <p>_____________________</p>
        <p><small>Principal/Headmaster</small></p>
      </div>
      <div>
        <p>_____________________</p>
        <p><small>Parent/Guardian</small></p>
      </div>
    </div>

    <div class="text-center mt-3 no-print">
      <small class="text-muted">Generated by School Management System</small>
    </div>
  </div>
<?php
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
  function printSingleReportCard() {
    var printContents = document.querySelector('.report-card-container').innerHTML;
    var originalContents = document.body.innerHTML;

    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Student Report Card</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                .no-print { display: none; }
                @media print {
                    body { margin: 0; padding: 0; }
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

  function printAllReportCards() {
    var printContents = document.getElementById('bulkReportCards').innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Class Report Cards</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                .no-print { display: none; }
                .report-card-container {
                    page-break-after: always;
                    margin-bottom: 30px;
                }
                @media print {
                    body { margin: 0; padding: 0; }
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

  function downloadSinglePDF() {
    var element = document.querySelector('.report-card-container');
    var opt = {
      margin: [0.5, 0.5, 0.5, 0.5],
      filename: 'Report_Card_<?php echo $student['admission_number'] ?? 'Student'; ?>.pdf',
      image: {
        type: 'jpeg',
        quality: 0.98
      },
      html2canvas: {
        scale: 2
      },
      jsPDF: {
        unit: 'in',
        format: 'a4',
        orientation: 'portrait'
      }
    };
    html2pdf().set(opt).from(element).save();
  }

  function downloadAllPDF() {
    var element = document.getElementById('bulkReportCards');
    var opt = {
      margin: [0.5, 0.5, 0.5, 0.5],
      filename: 'Class_Report_Cards.pdf',
      image: {
        type: 'jpeg',
        quality: 0.98
      },
      html2canvas: {
        scale: 2
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

<?php include '../includes/footer.php'; ?>