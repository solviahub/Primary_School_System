<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'School Management System');
define('SITE_URL', 'http://localhost/school-management-system/');

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to redirect
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Function to display messages
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        $icon = $type == 'success' ? 'check-circle' : ($type == 'danger' ? 'exclamation-circle' : 'info-circle');
        return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                <i class='fas fa-{$icon} me-2'></i>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return '';
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    if ($data === null) return null;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to get user details
function getUserDetails($user_id) {
    global $conn;
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Function to check permissions
function checkPermission($allowed_roles) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        $_SESSION['message'] = 'You do not have permission to access this page!';
        $_SESSION['message_type'] = 'danger';
        redirect('index.php');
    }
}

// Function to log user activity
function logActivity($user_id, $action, $details = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $details = sanitize($details);
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address) 
              VALUES ($user_id, '$action', '$details', '$ip')";
    mysqli_query($conn, $query);
}

// Function to get student by user_id
function getStudentByUserId($user_id) {
    global $conn;
    $query = "SELECT s.*, c.class_name 
              FROM students s 
              LEFT JOIN classes c ON s.class_id = c.id 
              WHERE s.user_id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Function to get children for parent
function getChildrenByParentId($parent_id) {
    global $conn;
    $query = "SELECT s.*, c.class_name, u.full_name as student_name 
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              LEFT JOIN classes c ON s.class_id = c.id 
              WHERE s.parent_id = $parent_id AND s.status = 'active'";
    $result = mysqli_query($conn, $query);
    $children = [];
    while($row = mysqli_fetch_assoc($result)) {
        $children[] = $row;
    }
    return $children;
}

// Function to get classes taught by teacher
function getTeacherClasses($teacher_id) {
    global $conn;
    $query = "SELECT DISTINCT c.* 
              FROM classes c 
              JOIN class_subjects cs ON c.id = cs.class_id 
              WHERE cs.teacher_id = $teacher_id";
    $result = mysqli_query($conn, $query);
    $classes = [];
    while($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row;
    }
    return $classes;
}

// Function to get subjects taught by teacher
function getTeacherSubjects($teacher_id) {
    global $conn;
    $query = "SELECT DISTINCT s.* 
              FROM subjects s 
              JOIN class_subjects cs ON s.id = cs.subject_id 
              WHERE cs.teacher_id = $teacher_id";
    $result = mysqli_query($conn, $query);
    $subjects = [];
    while($row = mysqli_fetch_assoc($result)) {
        $subjects[] = $row;
    }
    return $subjects;
}

// FIXED: Function to get school settings
function getSetting($key, $default = null) {
    global $conn;
    $query = "SELECT setting_value FROM settings WHERE setting_key = '$key'";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    return $default;
}

// Function to update school settings
function updateSetting($key, $value) {
    global $conn;
    $query = "INSERT INTO settings (setting_key, setting_value) 
              VALUES ('$key', '$value') 
              ON DUPLICATE KEY UPDATE setting_value = '$value'";
    return mysqli_query($conn, $query);
}

// Function to get current academic year
function getCurrentAcademicYear() {
    return getSetting('academic_year', date('Y') . '-' . (date('Y') + 1));
}

// Function to get current term
function getCurrentTerm() {
    return getSetting('term', 'First Term');
}

// Function to format date
function formatDate($date, $format = 'M d, Y') {
    if (!$date || $date == '0000-00-00') return '';
    return date($format, strtotime($date));
}

// Function to calculate age from date of birth
function calculateAge($dob) {
    if (!$dob) return '';
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>