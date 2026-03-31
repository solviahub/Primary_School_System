<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['parent']);

$page_title = 'View Children Results';
$parent_id = $_SESSION['user_id'];

// Get children
$children_query = "SELECT s.*, u.full_name, c.class_name 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   JOIN classes c ON s.class_id = c.id 
                   WHERE s.parent_id = $parent_id AND s.status = 'active'";
$children = mysqli_query($conn, $children_query);

// Get selected child and exam type
$selected_child = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'final_term';

include '../includes/header.php';
?>

<div class="row">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold" style="color: #3b82f6;">
          <i class="fas fa-child me-2"></i>My Children
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php while ($child = mysqli_fetch_assoc($children)): ?>
            <a href="?student_id=<?php echo $child['id']; ?>&exam_type=<?php echo $exam_type; ?>"
              class="list-group-item list-group-item-action <?php echo $selected_child == $child['id'] ? 'active' : ''; ?>"
              style="<?php echo $selected_child == $child['id'] ? 'background-color: #3b82f6; border-color: #3b82f6;' : ''; ?>">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <strong><?php echo $child['full_name']; ?></strong>
                  <br>
                  <small><?php echo $child['class_name']; ?> | <?php echo $child['admission_number']; ?></small>
                </div>
                <i class="fas fa-chevron-right"></i>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <?php if ($selected_child): ?>
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
    <?php if ($selected_child):
      // Get student details
      $student_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, u.full_name, u.email, c.class_name 
                                                                   FROM students s 
                                                                   JOIN users u ON s.user_id = u.id 
                                                                   JOIN classes c ON s.class_id = c.id 
                                                                   WHERE s.id = $selected_child"));

      // Get subjects for this class
      $subjects_query = "SELECT s.*, cs.teacher_id, u.full_name as teacher_name 
                               FROM subjects s 
                               JOIN class_subjects cs ON s.id = cs.subject_id 
                               LEFT JOIN users u ON cs.teacher_id = u.id 
                               WHERE cs.class_id = {$student_info['class_id']} 
                               ORDER BY s.subject_name";
      $subjects = mysqli_query($conn, $subjects_query);

      // Get marks for this student
      $marks_data = [];
      $total_marks = 0;
      $total_max_marks = 0;

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
                              WHERE student_id = $selected_child AND class_id = {$student_info['class_id']} AND exam_type = '$exam_type'";
      $comment_result = mysqli_query($conn, $comment_query);
      $comment = mysqli_fetch_assoc($comment_result);
    ?>

      <!-- Report Card View -->
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
                        <span class="fw-bold <?php echo $data['percentage'] >= 50 ? 'text-success' : 'text-danger'; ?>">
                          <?php echo $data['percentage']; ?>%
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
                  <td colspan="2" class="text-end">TOTAL / OVERALL:</td>
                  <td class="text-center"><?php echo $total_marks; ?></td>
                  <td class="text-center"><?php echo $total_max_marks; ?></td>
                  <td class="text-center"><?php echo $overall_percentage; ?>%</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Performance Chart -->
          <div class="row mb-4">
            <div class="col-12">
              <canvas id="performanceChart" style="max-height: 300px;"></canvas>
            </div>
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
          <div class="row">
            <div class="col-md-6 text-md-start">
              <p class="mb-0"><small>Class Teacher Signature: ___________________</small></p>
            </div>
            <div class="col-md-6 text-md-end">
              <p class="mb-0"><small>Principal/Headmaster Signature: ___________________</small></p>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3 text-end">
        <button class="btn" style="background-color: #3b82f6; color: white;" onclick="printReportCard()">
          <i class="fas fa-print me-2"></i>Print Report Card
        </button>
        <button class="btn btn-outline-secondary ms-2" onclick="downloadAsPDF()">
          <i class="fas fa-download me-2"></i>Download PDF
        </button>
      </div>

    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-child fa-4x text-muted mb-3"></i>
          <h5>Select a child to view report card</h5>
          <p class="text-muted">Choose a child from the left panel to view their academic performance</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
  <?php if ($selected_child && !empty($marks_data)): ?>
    // Create performance chart
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode(array_column($marks_data, 'subject')); ?>,
        datasets: [{
          label: 'Percentage (%)',
          data: <?php echo json_encode(array_column($marks_data, 'percentage')); ?>,
          backgroundColor: '#3b82f6',
          borderColor: '#3b82f6',
          borderWidth: 1,
          borderRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            title: {
              display: true,
              text: 'Percentage (%)'
            }
          },
          x: {
            ticks: {
              maxRotation: 45,
              minRotation: 45
            }
          }
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.parsed.y + '%';
              }
            }
          }
        }
      }
    });
  <?php endif; ?>

  function printReportCard() {
    var printContents = document.getElementById('reportCard').innerHTML;
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
      filename: 'Report_Card_<?php echo $student_info['admission_number'] ?? 'Student'; ?>.pdf',
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