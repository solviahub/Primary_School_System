<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: " . SITE_URL . "login.php");
  exit();
}

// Check user role and redirect if accessing wrong section
$current_role = $_SESSION['user_role'];
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Validate directory access based on role
if ($current_dir != $current_role && $current_dir != 'includes' && $current_dir != 'assets') {
  // Redirect to appropriate dashboard based on role
  switch ($current_role) {
    case 'admin':
      header("Location: " . SITE_URL . "admin/dashboard.php");
      break;
    case 'teacher':
      header("Location: " . SITE_URL . "teacher/dashboard.php");
      break;
    case 'parent':
      header("Location: " . SITE_URL . "parent/dashboard.php");
      break;
    case 'student':
      header("Location: " . SITE_URL . "student/dashboard.php");
      break;
    case 'librarian':
      header("Location: " . SITE_URL . "librarian/dashboard.php");
      break;
    default:
      header("Location: " . SITE_URL . "logout.php");
  }
  exit();
}
