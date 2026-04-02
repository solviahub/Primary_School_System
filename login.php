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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated Background */
    body::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
      animation: pulse 4s ease-in-out infinite;
    }

    body::after {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
      animation: pulse 4s ease-in-out infinite reverse;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 0.5;
        transform: scale(1);
      }

      50% {
        opacity: 1;
        transform: scale(1.1);
      }
    }

    /* Floating Shapes */
    .shape {
      position: absolute;
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
      border-radius: 50%;
      filter: blur(60px);
      animation: float 20s infinite ease-in-out;
      pointer-events: none;
    }

    .shape-1 {
      width: 300px;
      height: 300px;
      top: -150px;
      left: -150px;
    }

    .shape-2 {
      width: 400px;
      height: 400px;
      bottom: -200px;
      right: -200px;
      animation-delay: -5s;
    }

    .shape-3 {
      width: 200px;
      height: 200px;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      animation-delay: -10s;
    }

    @keyframes float {

      0%,
      100% {
        transform: translate(0, 0) rotate(0deg);
      }

      33% {
        transform: translate(30px, -30px) rotate(120deg);
      }

      66% {
        transform: translate(-20px, 20px) rotate(240deg);
      }
    }

    /* Main Container */
    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 1200px;
      margin: 20px;
    }

    /* Glass Morphism Card */
    .glass-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      border-radius: 32px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      overflow: hidden;
      transition: all 0.3s ease;
    }

    /* Left Panel */
    .left-panel {
      background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
      padding: 3rem;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .left-panel::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1%, transparent 1%);
      background-size: 50px 50px;
      animation: moveDots 20s linear infinite;
    }

    @keyframes moveDots {
      from {
        transform: translate(0, 0);
      }

      to {
        transform: translate(50px, 50px);
      }
    }

    .school-icon {
      position: relative;
      z-index: 1;
      text-align: center;
      margin-bottom: 2rem;
    }

    .school-icon i {
      font-size: 4rem;
      filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.2));
      animation: bounce 2s ease-in-out infinite;
    }

    @keyframes bounce {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-10px);
      }
    }

    .left-panel h2 {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 1rem;
      position: relative;
      z-index: 1;
    }

    .left-panel p {
      font-size: 1rem;
      opacity: 0.9;
      line-height: 1.6;
      position: relative;
      z-index: 1;
    }

    .feature-list {
      margin-top: 2rem;
      position: relative;
      z-index: 1;
    }

    .feature-item {
      display: flex;
      align-items: center;
      margin-bottom: 1rem;
      animation: slideIn 0.5s ease-out forwards;
      opacity: 0;
    }

    .feature-item:nth-child(1) {
      animation-delay: 0.1s;
    }

    .feature-item:nth-child(2) {
      animation-delay: 0.2s;
    }

    .feature-item:nth-child(3) {
      animation-delay: 0.3s;
    }

    .feature-item:nth-child(4) {
      animation-delay: 0.4s;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .feature-item i {
      width: 30px;
      font-size: 1.2rem;
      margin-right: 12px;
    }

    /* Right Panel */
    .right-panel {
      padding: 3rem;
    }

    .logo-badge {
      text-align: center;
      margin-bottom: 2rem;
    }

    .logo-badge .badge-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #3b82f6, #1e40af);
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
    }

    .logo-badge .badge-icon i {
      font-size: 2rem;
      color: white;
    }

    .logo-badge h3 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.25rem;
    }

    .logo-badge p {
      color: #64748b;
      font-size: 0.875rem;
    }

    /* Form Styles */
    .input-group-custom {
      margin-bottom: 1.5rem;
    }

    .input-group-custom label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 0.5rem;
    }

    .input-wrapper {
      position: relative;
    }

    .input-wrapper i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 1.1rem;
      transition: all 0.3s ease;
    }

    .input-wrapper input {
      width: 100%;
      padding: 12px 16px 12px 45px;
      border: 2px solid #e2e8f0;
      border-radius: 16px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      background: white;
      font-family: 'Inter', sans-serif;
    }

    .input-wrapper input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .input-wrapper input:hover {
      border-color: #3b82f6;
    }

    .toggle-password {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #94a3b8;
      transition: color 0.3s ease;
    }

    .toggle-password:hover {
      color: #3b82f6;
    }

    /* Checkbox */
    .checkbox-wrapper {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
    }

    .checkbox-custom {
      display: flex;
      align-items: center;
      cursor: pointer;
    }

    .checkbox-custom input {
      display: none;
    }

    .checkbox-custom .checkmark {
      width: 20px;
      height: 20px;
      border: 2px solid #cbd5e1;
      border-radius: 6px;
      margin-right: 10px;
      position: relative;
      transition: all 0.3s ease;
    }

    .checkbox-custom input:checked+.checkmark {
      background: #3b82f6;
      border-color: #3b82f6;
    }

    .checkbox-custom input:checked+.checkmark::after {
      content: '✓';
      position: absolute;
      color: white;
      font-size: 12px;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .forgot-link {
      color: #3b82f6;
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .forgot-link:hover {
      color: #1e40af;
    }

    /* Login Button */
    .btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
      border: none;
      border-radius: 16px;
      color: white;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-login::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.3);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    .btn-login:hover::before {
      width: 300px;
      height: 300px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
    }

    /* Alert Styles */
    .alert-custom {
      border-radius: 16px;
      border: none;
      padding: 1rem;
      margin-bottom: 1.5rem;
      animation: slideDown 0.5s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-danger {
      background: #fef2f2;
      color: #dc2626;
      border-left: 4px solid #dc2626;
    }

    /* Role Badges */
    .role-badges {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px;
      margin-top: 1rem;
    }

    .role-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      transition: all 0.3s ease;
      cursor: default;
    }

    .role-badge:hover {
      transform: translateY(-2px);
    }

    .role-admin {
      background: #dc2626;
      color: white;
    }

    .role-teacher {
      background: #3b82f6;
      color: white;
    }

    .role-parent {
      background: #10b981;
      color: white;
    }

    .role-student {
      background: #8b5cf6;
      color: white;
    }

    .role-librarian {
      background: #f59e0b;
      color: white;
    }

    /* Footer */
    .login-footer {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid #e2e8f0;
      text-align: center;
    }

    .login-footer small {
      color: #64748b;
      font-size: 0.75rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .left-panel {
        display: none;
      }

      .right-panel {
        padding: 2rem;
      }

      .glass-card {
        max-width: 450px;
        margin: 0 auto;
      }
    }

    /* Loading Animation */
    .btn-login.loading {
      pointer-events: none;
      opacity: 0.7;
    }

    .btn-login.loading i {
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      from {
        transform: rotate(0deg);
      }

      to {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <div class="shape shape-1"></div>
  <div class="shape shape-2"></div>
  <div class="shape shape-3"></div>

  <div class="login-wrapper">
    <div class="row g-0 glass-card">
      <!-- Left Panel - Info Section -->
      <div class="col-lg-6 left-panel">
        <div class="school-icon">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <h2>Welcome Back!</h2>
        <p>Access your school management portal to track academic progress, manage assignments, and stay connected with your educational journey.</p>

        <div class="feature-list">
          <div class="feature-item">
            <i class="fas fa-chalkboard-user"></i>
            <span>Track academic performance</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-calendar-check"></i>
            <span>Monitor attendance records</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-tasks"></i>
            <span>Manage assignments & grades</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-chart-line"></i>
            <span>Real-time progress updates</span>
          </div>
        </div>
      </div>

      <!-- Right Panel - Login Form -->
      <div class="col-lg-6 right-panel">
        <div class="logo-badge">
          <div class="badge-icon">
            <i class="fas fa-school"></i>
          </div>
          <h3><?php echo SITE_NAME; ?></h3>
          <p>Sign in to continue</p>
        </div>

        <?php if ($error): ?>
          <div class="alert-custom alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
          <div class="input-group-custom">
            <label>Username</label>
            <div class="input-wrapper">
              <i class="fas fa-user"></i>
              <input type="text" name="username" required autofocus placeholder="Enter your username">
            </div>
          </div>

          <div class="input-group-custom">
            <label>Password</label>
            <div class="input-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" id="password" required placeholder="Enter your password">
              <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>
          </div>

          <div class="checkbox-wrapper">
            <label class="checkbox-custom">
              <input type="checkbox" id="remember">
              <span class="checkmark"></span>
              <span style="font-size: 0.875rem; color: #64748b;">Remember me</span>
            </label>
            <a href="#" class="forgot-link">Forgot Password?</a>
          </div>

          <button type="submit" class="btn-login" id="loginBtn">
            <i class="fas fa-sign-in-alt me-2"></i> Sign In
          </button>
        </form>

        <div class="role-badges">
          <span class="role-badge role-admin">Admin</span>
          <span class="role-badge role-teacher">Teacher</span>
          <span class="role-badge role-parent">Parent</span>
          <span class="role-badge role-student">Student</span>
          <span class="role-badge role-librarian">Librarian</span>
        </div>

        <div class="login-footer">
          <small>
            <i class="fas fa-shield-alt me-1"></i>
            Secure login powered by SSL encryption
          </small>
          <br>
          <small class="text-muted mt-2 d-block">
            Need help? Contact your school administrator
          </small>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle password visibility
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.querySelector('.toggle-password');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }

    // Form submission with loading state
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const btn = document.getElementById('loginBtn');
      btn.classList.add('loading');
      btn.innerHTML = '<i class="fas fa-spinner me-2"></i> Signing in...';
    });

    // Remember me functionality
    const rememberCheckbox = document.getElementById('remember');
    const usernameInput = document.querySelector('input[name="username"]');

    if (localStorage.getItem('rememberedUsername')) {
      usernameInput.value = localStorage.getItem('rememberedUsername');
      rememberCheckbox.checked = true;
    }

    document.getElementById('loginForm').addEventListener('submit', function() {
      if (rememberCheckbox.checked) {
        localStorage.setItem('rememberedUsername', usernameInput.value);
      } else {
        localStorage.removeItem('rememberedUsername');
      }
    });

    // Animate on load
    document.addEventListener('DOMContentLoaded', function() {
      const formElements = document.querySelectorAll('.right-panel');
      formElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(20px)';
        setTimeout(() => {
          el.style.transition = 'all 0.6s ease';
          el.style.opacity = '1';
          el.style.transform = 'translateX(0)';
        }, 100);
      });
    });
  </script>
</body>

</html>