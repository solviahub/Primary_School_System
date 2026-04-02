<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = date('Y', strtotime($month));
$month_num = date('m', strtotime($month));

if (!$class_id) {
  $_SESSION['message'] = 'Please select a class first!';
  $_SESSION['message_type'] = 'danger';
  redirect('admin/attendance_reports.php');
}

// Get class name
$class_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT class_name, section FROM classes WHERE id = $class_id"));
$class_name = $class_info['class_name'] . ' ' . $class_info['section'];

// Get students
$students_query = "SELECT s.id, s.admission_number, u.full_name 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   WHERE s.class_id = $class_id AND s.status = 'active' 
                   ORDER BY u.full_name";
$students = mysqli_query($conn, $students_query);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Attendance_Report_' . $class_name . '_' . date('F_Y', strtotime($month)) . '.csv"');

$output = fopen('php://output', 'w');

// Add headers
fputcsv($output, ['Attendance Report']);
fputcsv($output, ['School: ' . getSetting('school_name', 'School Name')]);
fputcsv($output, ['Class: ' . $class_name]);
fputcsv($output, ['Month: ' . date('F Y', strtotime($month))]);
fputcsv($output, ['Generated Date: ' . date('Y-m-d H:i:s')]);
fputcsv($output, []);
fputcsv($output, ['Student Name', 'Admission No', 'Present', 'Absent', 'Late', 'Excused', 'Total Days', 'Attendance %']);

// Add data
while ($student = mysqli_fetch_assoc($students)) {
  $attendance_query = "SELECT 
                            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                            COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                            COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused,
                            COUNT(*) as total
                         FROM attendance 
                         WHERE student_id = {$student['id']} 
                         AND MONTH(date) = $month_num AND YEAR(date) = $year";
  $attendance_result = mysqli_query($conn, $attendance_query);
  $data = mysqli_fetch_assoc($attendance_result);

  $present = $data['present'] ?? 0;
  $total = $data['total'] ?? 0;
  $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

  fputcsv($output, [
    $student['full_name'],
    $student['admission_number'],
    $present,
    $data['absent'] ?? 0,
    $data['late'] ?? 0,
    $data['excused'] ?? 0,
    $total,
    $percentage . '%'
  ]);
}

fclose($output);
exit();
