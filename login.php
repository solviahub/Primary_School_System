<?php
require_once 'config/database.php';

if (isLoggedIn()) {
  redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = sanitize($_POST['username']);
  $password = $_POST['password'];

  $query = "SELECT * FROM users WHERE username = '$username' AND status = 'active'";
  $result = mysqli_query($conn, $query);

  if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    if (password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_name'] = $user['full_name'];
      $_SESSION['user_role'] = $user['role'];
      $_SESSION['user_email'] = $user['email'];

      // Log login activity
      $login_time = date('Y-m-d H:i:s');
      $user_id = $user['id'];
      mysqli_query($conn, "INSERT INTO login_logs (user_id, login_time, ip_address) 
                                 VALUES ($user_id, '$login_time', '" . $_SERVER['REMOTE_ADDR'] . "')");

      // Redirect based on role
      switch ($user['role']) {
        case 'admin':
          redirect('admin/dashboard.php');
          break;
        case 'teacher':
          redirect('teacher/dashboard.php');
          break;
        case 'parent':
          redirect('parent/dashboard.php');
          break;
        case 'student':
          redirect('student/dashboard.php');
          break;
        case 'librarian':
          redirect('librarian/dashboard.php');
          break;
        default:
          redirect('index.php');
      }
    } else {
      $error = 'Invalid password!';
    }
  } else {
    $error = 'Username not found or account inactive!';
  }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?php echo SITE_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .login-container {
      width: 100%;
      max-width: 450px;
      padding: 20px;
    }

    .login-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      animation: fadeInUp 0.6s ease;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      text-align: center;
    }

    .login-header i {
      font-size: 60px;
      margin-bottom: 15px;
    }

    .login-header h3 {
      margin: 0;
      font-weight: 600;
    }

    .login-header p {
      margin: 5px 0 0;
      opacity: 0.9;
    }

    .login-body {
      padding: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      display: block;
    }

    .input-group-text {
      background: #f8f9fa;
      border-right: none;
    }

    .form-control {
      border-left: none;
      padding: 12px;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: none;
    }

    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      padding: 12px;
      font-weight: 600;
      font-size: 16px;
      width: 100%;
      transition: all 0.3s ease;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .login-footer {
      text-align: center;
      padding: 20px 30px;
      background: #f8f9fa;
      border-top: 1px solid #e0e0e0;
    }

    .role-badge {
      display: inline-block;
      padding: 5px 10px;
      margin: 5px;
      border-radius: 5px;
      font-size: 12px;
      font-weight: 600;
    }

    .role-admin {
      background: #dc3545;
      color: white;
    }

    .role-teacher {
      background: #007bff;
      color: white;
    }

    .role-parent {
      background: #28a745;
      color: white;
    }

    .role-student {
      background: #17a2b8;
      color: white;
    }

    .role-librarian {
      background: #fd7e14;
      color: white;
    }

    .alert {
      border-radius: 10px;
      border: none;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <i class="fas fa-school"></i>
        <h3><?php echo SITE_NAME; ?></h3>
        <p>Login to your account</p>
      </div>

      <div class="login-body">
        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="form-group">
            <label>Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" class="form-control" name="username" required autofocus placeholder="Enter your username">
            </div>
          </div>

          <div class="form-group">
            <label>Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" class="form-control" name="password" required placeholder="Enter your password">
            </div>
          </div>

          <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
          </div>

          <button type="submit" class="btn btn-primary btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>Login
          </button>
        </form>
      </div>

      <div class="login-footer">
        <small class="text-muted">
          <i class="fas fa-question-circle me-1"></i>
          Need help? Contact your school administrator
        </small>
        <div class="mt-2">
          <span class="role-badge role-admin">Admin</span>
          <span class="role-badge role-teacher">Teacher</span>
          <span class="role-badge role-parent">Parent</span>
          <span class="role-badge role-student">Student</span>
          <span class="role-badge role-librarian">Librarian</span>
        </div>
        <div class="mt-3">
          <small class="text-muted">
            Default login: Use credentials provided by your school administrator
          </small>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>