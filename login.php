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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated background elements */
    body::before {
      content: '';
      position: absolute;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: moveGrid 20s linear infinite;
      opacity: 0.3;
    }

    @keyframes moveGrid {
      0% {
        transform: translate(0, 0);
      }
      100% {
        transform: translate(50px, 50px);
      }
    }

    body::after {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
      background-size: cover;
      bottom: 0;
      opacity: 0.1;
      pointer-events: none;
    }

    .login-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 480px;
      padding: 20px;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      border-radius: 32px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      padding: 48px 40px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      animation: fadeInUp 0.8s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    .login-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.3);
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .logo-wrapper {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }

    .logo-wrapper i {
      font-size: 40px;
      color: white;
    }

    .login-header h3 {
      font-size: 28px;
      font-weight: 800;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 8px;
    }

    .login-header p {
      color: #6c757d;
      font-size: 14px;
      margin: 0;
    }

    .form-group {
      margin-bottom: 24px;
    }

    .form-label {
      font-weight: 600;
      font-size: 14px;
      color: #2d3748;
      margin-bottom: 8px;
      display: block;
    }

    .input-group-modern {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 16px;
      color: #a0aec0;
      font-size: 16px;
      pointer-events: none;
      transition: all 0.3s ease;
    }

    .form-control-modern {
      width: 100%;
      padding: 12px 16px 12px 48px;
      border: 2px solid #e2e8f0;
      border-radius: 16px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #f8fafc;
      font-family: 'Inter', sans-serif;
    }

    .form-control-modern:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .form-control-modern:focus + .input-icon {
      color: #667eea;
    }

    .checkbox-modern {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .checkbox-label {
      display: flex;
      align-items: center;
      cursor: pointer;
      user-select: none;
    }

    .checkbox-label input {
      position: absolute;
      opacity: 0;
      cursor: pointer;
    }

    .checkmark {
      width: 20px;
      height: 20px;
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 6px;
      margin-right: 10px;
      position: relative;
      transition: all 0.2s ease;
    }

    .checkbox-label input:checked ~ .checkmark {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-color: #667eea;
    }

    .checkbox-label input:checked ~ .checkmark::after {
      content: '\f00c';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-size: 10px;
    }

    .checkbox-label span {
      font-size: 14px;
      color: #4a5568;
    }

    .forgot-link {
      font-size: 14px;
      color: #667eea;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .forgot-link:hover {
      color: #764ba2;
      text-decoration: underline;
    }

    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      padding: 14px;
      font-weight: 700;
      font-size: 16px;
      border-radius: 16px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      color: white;
      width: 100%;
      cursor: pointer;
    }

    .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-login:hover::before {
      left: 100%;
    }

    .btn-login i {
      margin-right: 8px;
    }

    .alert-modern {
      background: linear-gradient(135deg, #fef2f2 0%, #fff5f5 100%);
      border: 1px solid #fecaca;
      border-radius: 16px;
      padding: 12px 16px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: shake 0.5s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .alert-modern i {
      color: #dc2626;
      font-size: 18px;
    }

    .alert-modern span {
      color: #991b1b;
      font-size: 14px;
      flex: 1;
    }

    .alert-modern .close-alert {
      cursor: pointer;
      color: #9ca3af;
      transition: color 0.2s;
    }

    .alert-modern .close-alert:hover {
      color: #4b5563;
    }

    .demo-credentials {
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid #e2e8f0;
      text-align: center;
    }

    .demo-credentials small {
      font-size: 12px;
      color: #6c757d;
      display: block;
      margin-bottom: 8px;
    }

    .credential-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #f1f5f9;
      padding: 6px 12px;
      border-radius: 40px;
      font-size: 12px;
      font-weight: 500;
      color: #475569;
    }

    .credential-badge i {
      color: #667eea;
      font-size: 12px;
    }

    @media (max-width: 576px) {
      .login-card {
        padding: 32px 24px;
      }
      
      .login-header h3 {
        font-size: 24px;
      }
      
      .logo-wrapper {
        width: 64px;
        height: 64px;
      }
      
      .logo-wrapper i {
        font-size: 32px;
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-wrapper">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <h3><?php echo SITE_NAME; ?></h3>
        <p>Welcome back! Please login to your account</p>
      </div>

      <?php if ($error): ?>
        <div class="alert-modern" id="alertMessage">
          <i class="fas fa-exclamation-circle"></i>
          <span><?php echo $error; ?></span>
          <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="loginForm">
        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-group-modern">
            <i class="fas fa-user input-icon"></i>
            <input type="text" class="form-control-modern" id="username" name="username" 
                   placeholder="Enter your username" required autofocus value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-group-modern">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" class="form-control-modern" id="password" name="password" 
                   placeholder="Enter your password" required>
          </div>
        </div>
        
        <div class="checkbox-modern">
          <label class="checkbox-label">
            <input type="checkbox" name="remember" id="remember">
            <span class="checkmark"></span>
            <span>Remember me</span>
          </label>
          <a href="#" class="forgot-link">Forgot Password?</a>
        </div>
        
        <button type="submit" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
      </form>

      <div class="demo-credentials">
        <small>Demo Credentials</small>
        <div class="credential-badge">
          <i class="fas fa-user-shield"></i>
          <span>admin / password</span>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Auto-dismiss alert after 5 seconds
    setTimeout(function() {
      const alert = document.getElementById('alertMessage');
      if (alert) {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
      }
    }, 5000);

    // Add loading state on form submit
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('.btn-login');
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
      submitBtn.disabled = true;
    });

    // Toggle password visibility (optional enhancement)
    const passwordInput = document.getElementById('password');
    const togglePassword = () => {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
    };

    // Add password visibility toggle button (optional)
    const passwordGroup = passwordInput.parentElement;
    const toggleBtn = document.createElement('i');
    toggleBtn.className = 'fas fa-eye-slash';
    toggleBtn.style.position = 'absolute';
    toggleBtn.style.right = '16px';
    toggleBtn.style.cursor = 'pointer';
    toggleBtn.style.color = '#a0aec0';
    toggleBtn.style.zIndex = '3';
    toggleBtn.onclick = function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      this.className = type === 'password' ? 'fas fa-eye-slash' : 'fas fa-eye';
    };
    passwordGroup.appendChild(toggleBtn);

    // Add remember me functionality
    const rememberCheckbox = document.getElementById('remember');
    const usernameInput = document.getElementById('username');
    
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
  </script>
</body>

</html>