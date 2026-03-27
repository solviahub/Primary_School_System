<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];

  if ($action == 'create') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $gender = sanitize($_POST['gender']);
    $class_id = (int)$_POST['class_id'];
    $parent_id = $_POST['parent_id'] ? (int)$_POST['parent_id'] : null;
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Generate username
    $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);

    // Create user account
    $query = "INSERT INTO users (username, password, email, full_name, role, phone, address, status) 
                  VALUES ('$username', '$password', '$email', '$full_name', 'student', '$phone', '$address', 'active')";

    if (mysqli_query($conn, $query)) {
      $user_id = mysqli_insert_id($conn);

      // Generate admission number
      $admission_number = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);

      // Create student record
      $query2 = "INSERT INTO students (user_id, admission_number, class_id, parent_id, date_of_birth, gender, enrollment_date) 
                      VALUES ($user_id, '$admission_number', $class_id, " . ($parent_id ?: 'NULL') . ", '$date_of_birth', '$gender', CURDATE())";

      if (mysqli_query($conn, $query2)) {
        $_SESSION['message'] = 'Student registered successfully! Admission Number: ' . $admission_number;
        $_SESSION['message_type'] = 'success';
      } else {
        $_SESSION['message'] = 'Error creating student record!';
        $_SESSION['message_type'] = 'danger';
      }
    } else {
      $_SESSION['message'] = 'Error creating user account!';
      $_SESSION['message_type'] = 'danger';
    }
  }

  header("Location: manage_students.php");
  exit();
}
