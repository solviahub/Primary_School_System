<?php
require_once '../config/database.php';

// Get teacher_id from request
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// If no teacher_id provided, show message to select a teacher
if ($teacher_id == 0) {
  echo '<div class="text-center py-4">
            <i class="fas fa-user-graduate fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">No teacher selected.</p>
            <small>Please click the "Subjects" button for a specific teacher.</small>
          </div>';
  exit;
}

// Get teacher name first
$teacher_query = "SELECT full_name FROM users WHERE id = $teacher_id AND role = 'teacher'";
$teacher_result = mysqli_query($conn, $teacher_query);

if (mysqli_num_rows($teacher_result) == 0) {
  echo '<div class="alert alert-danger m-3">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Teacher not found. Please refresh the page and try again.
          </div>';
  exit;
}

$teacher = mysqli_fetch_assoc($teacher_result);

// Get all subjects assigned to this teacher
$query = "SELECT cs.*, c.class_name, c.section, s.subject_name, s.subject_code 
          FROM class_subjects cs 
          JOIN classes c ON cs.class_id = c.id 
          JOIN subjects s ON cs.subject_id = s.id 
          WHERE cs.teacher_id = $teacher_id 
          ORDER BY c.class_name, s.subject_name";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
  echo '<div class="p-3">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Subject Code</th>
                        </tr>
                    </thead>
                    <tbody>';

  while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>
                <td><strong>' . htmlspecialchars($row['class_name'] . ' ' . $row['section']) . '</strong></td>
                <td>' . htmlspecialchars($row['subject_name']) . '</td>
                <td><code>' . htmlspecialchars($row['subject_code']) . '</code></td>
              </tr>';
  }

  echo '</tbody>
                </table>
            </div>
          </div>';
} else {
  echo '<div class="text-center py-4">
            <i class="fas fa-book-open fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">No subjects assigned to this teacher yet.</p>
            <a href="assign_subject_teacher.php" class="btn btn-sm btn-primary mt-2">
                <i class="fas fa-plus me-1"></i> Assign Subjects
            </a>
          </div>';
}
