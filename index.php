<?php
require_once 'config/database.php';

if (isLoggedIn()) {
  // Redirect to appropriate dashboard based on role
  switch ($_SESSION['user_role']) {
    case 'admin':
      header("Location: admin/dashboard.php");
      break;
    case 'teacher':
      header("Location: teacher/dashboard.php");
      break;
    case 'parent':
      header("Location: parent/dashboard.php");
      break;
    case 'student':
      header("Location: student/dashboard.php");
      break;
    case 'librarian':
      header("Location: librarian/dashboard.php");
      break;
    default:
      header("Location: login.php");
  }
  exit();
} else {
  header("Location: login.php");
  exit();
}
