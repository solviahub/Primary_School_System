<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

checkPermission(['admin']);

$page_title = 'System Settings';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['update_general'])) {
    // Update general settings
    updateSetting('school_name', sanitize($_POST['school_name']));
    updateSetting('school_address', sanitize($_POST['school_address']));
    updateSetting('school_phone', sanitize($_POST['school_phone']));
    updateSetting('school_email', sanitize($_POST['school_email']));
    updateSetting('school_website', sanitize($_POST['school_website']));

    $_SESSION['message'] = 'General settings updated successfully!';
    $_SESSION['message_type'] = 'success';
  } elseif (isset($_POST['update_academic'])) {
    // Update academic settings
    updateSetting('academic_year', sanitize($_POST['academic_year']));
    updateSetting('term', sanitize($_POST['term']));
    updateSetting('term_start_date', sanitize($_POST['term_start_date']));
    updateSetting('term_end_date', sanitize($_POST['term_end_date']));
    updateSetting('session_start_year', sanitize($_POST['session_start_year']));
    updateSetting('session_end_year', sanitize($_POST['session_end_year']));

    $_SESSION['message'] = 'Academic settings updated successfully!';
    $_SESSION['message_type'] = 'success';
  } elseif (isset($_POST['update_fee'])) {
    // Update fee settings
    updateSetting('late_fee_percentage', sanitize($_POST['late_fee_percentage']));
    updateSetting('late_fee_days', sanitize($_POST['late_fee_days']));
    updateSetting('discount_percentage', sanitize($_POST['discount_percentage']));
    updateSetting('payment_reminder_days', sanitize($_POST['payment_reminder_days']));

    $_SESSION['message'] = 'Fee settings updated successfully!';
    $_SESSION['message_type'] = 'success';
  } elseif (isset($_POST['update_attendance'])) {
    // Update attendance settings
    updateSetting('attendance_threshold', sanitize($_POST['attendance_threshold']));
    updateSetting('max_late_minutes', sanitize($_POST['max_late_minutes']));
    updateSetting('attendance_report_frequency', sanitize($_POST['attendance_report_frequency']));

    $_SESSION['message'] = 'Attendance settings updated successfully!';
    $_SESSION['message_type'] = 'success';
  } elseif (isset($_POST['update_library'])) {
    // Update library settings
    updateSetting('max_books_per_student', sanitize($_POST['max_books_per_student']));
    updateSetting('loan_duration_days', sanitize($_POST['loan_duration_days']));
    updateSetting('late_fee_per_day', sanitize($_POST['late_fee_per_day']));

    $_SESSION['message'] = 'Library settings updated successfully!';
    $_SESSION['message_type'] = 'success';
  } elseif (isset($_POST['update_notification'])) {
    // Update notification settings
    updateSetting('enable_email_notifications', sanitize($_POST['enable_email_notifications']));
    updateSetting('enable_sms_notifications', sanitize($_POST['enable_sms_notifications']));
    updateSetting('admin_email', sanitize($_POST['admin_email']));
    updateSetting('smtp_host', sanitize($_POST['smtp_host']));
    updateSetting('smtp_port', sanitize($_POST['smtp_port']));
    updateSetting('smtp_user', sanitize($_POST['smtp_user']));
    updateSetting('smtp_pass', sanitize($_POST['smtp_pass']));

    $_SESSION['message'] = 'Notification settings updated successfully!';
    $_SESSION['message_type'] = 'success';
  } elseif (isset($_POST['update_security'])) {
    // Update security settings
    updateSetting('session_timeout', sanitize($_POST['session_timeout']));
    updateSetting('max_login_attempts', sanitize($_POST['max_login_attempts']));
    updateSetting('password_expiry_days', sanitize($_POST['password_expiry_days']));
    updateSetting('enable_2fa', sanitize($_POST['enable_2fa']));

    $_SESSION['message'] = 'Security settings updated successfully!';
    $_SESSION['message_type'] = 'success';
  }

  redirect('admin/settings.php');
}

// Get all current settings
$settings = [];
$settings_query = "SELECT * FROM settings";
$settings_result = mysqli_query($conn, $settings_query);
while ($row = mysqli_fetch_assoc($settings_result)) {
  $settings[$row['setting_key']] = $row['setting_value'];
}

include '../includes/header.php';
?>

<style>
  .settings-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
  }

  .settings-card .card-header {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    color: white;
    padding: 1rem 1.5rem;
    border: none;
  }

  .settings-card .card-header h6 {
    margin: 0;
    font-weight: 600;
  }

  .settings-card .card-body {
    padding: 1.5rem;
  }

  .setting-group {
    margin-bottom: 1.25rem;
  }

  .setting-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1e293b;
    display: block;
  }

  .setting-group .form-control,
  .setting-group .form-select {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 0.625rem 1rem;
  }

  .setting-group .form-control:focus,
  .setting-group .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
  }

  .setting-group .form-check {
    margin-top: 0.5rem;
  }

  .help-text {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
  }

  .nav-settings {
    background: white;
    border-radius: 15px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .nav-settings .nav-link {
    color: #1e293b;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    transition: all 0.3s ease;
  }

  .nav-settings .nav-link:hover {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
  }

  .nav-settings .nav-link.active {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    color: white;
  }

  .nav-settings .nav-link i {
    width: 25px;
    margin-right: 10px;
  }
</style>

<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 15px; padding: 2rem;">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="text-white">System Settings</h2>
          <p class="text-white-50 mb-0">Configure and manage your school management system.</p>
        </div>
        <div class="col-md-4 text-end">
          <i class="fas fa-cog" style="font-size: 4rem; opacity: 0.3; color: white;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-3 mb-4">
    <div class="nav-settings">
      <nav class="nav flex-column nav-pills">
        <a class="nav-link active" data-bs-toggle="pill" href="#general">
          <i class="fas fa-building"></i> General Settings
        </a>
        <a class="nav-link" data-bs-toggle="pill" href="#academic">
          <i class="fas fa-calendar-alt"></i> Academic Settings
        </a>
        <a class="nav-link" data-bs-toggle="pill" href="#fee">
          <i class="fas fa-credit-card"></i> Fee Settings
        </a>
        <a class="nav-link" data-bs-toggle="pill" href="#attendance">
          <i class="fas fa-check-circle"></i> Attendance Settings
        </a>
        <a class="nav-link" data-bs-toggle="pill" href="#library">
          <i class="fas fa-book"></i> Library Settings
        </a>
        <a class="nav-link" data-bs-toggle="pill" href="#notification">
          <i class="fas fa-bell"></i> Notification Settings
        </a>
        <a class="nav-link" data-bs-toggle="pill" href="#security">
          <i class="fas fa-shield-alt"></i> Security Settings
        </a>
      </nav>
    </div>
  </div>

  <div class="col-md-9">
    <div class="tab-content">
      <!-- General Settings -->
      <div class="tab-pane fade show active" id="general">
        <div class="settings-card">
          <div class="card-header">
            <h6><i class="fas fa-building me-2"></i>General Settings</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-12 setting-group">
                  <label>School Name *</label>
                  <input type="text" class="form-control" name="school_name"
                    value="<?php echo $settings['school_name'] ?? 'School Management System'; ?>" required>
                </div>
                <div class="col-md-12 setting-group">
                  <label>School Address</label>
                  <textarea class="form-control" name="school_address" rows="2"><?php echo $settings['school_address'] ?? ''; ?></textarea>
                </div>
                <div class="col-md-6 setting-group">
                  <label>Phone Number</label>
                  <input type="text" class="form-control" name="school_phone"
                    value="<?php echo $settings['school_phone'] ?? ''; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Email Address</label>
                  <input type="email" class="form-control" name="school_email"
                    value="<?php echo $settings['school_email'] ?? ''; ?>">
                </div>
                <div class="col-md-12 setting-group">
                  <label>Website</label>
                  <input type="url" class="form-control" name="school_website"
                    value="<?php echo $settings['school_website'] ?? ''; ?>">
                  <div class="help-text">Your school's website URL</div>
                </div>
              </div>
              <button type="submit" name="update_general" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save General Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Academic Settings -->
      <div class="tab-pane fade" id="academic">
        <div class="settings-card">
          <div class="card-header">
            <h6><i class="fas fa-calendar-alt me-2"></i>Academic Settings</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-6 setting-group">
                  <label>Current Academic Year</label>
                  <input type="text" class="form-control" name="academic_year"
                    value="<?php echo $settings['academic_year'] ?? date('Y') . '-' . (date('Y') + 1); ?>"
                    placeholder="e.g., 2024-2025">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Current Term</label>
                  <select class="form-select" name="term">
                    <option value="First Term" <?php echo ($settings['term'] ?? 'First Term') == 'First Term' ? 'selected' : ''; ?>>First Term</option>
                    <option value="Second Term" <?php echo ($settings['term'] ?? '') == 'Second Term' ? 'selected' : ''; ?>>Second Term</option>
                    <option value="Third Term" <?php echo ($settings['term'] ?? '') == 'Third Term' ? 'selected' : ''; ?>>Third Term</option>
                  </select>
                </div>
                <div class="col-md-6 setting-group">
                  <label>Term Start Date</label>
                  <input type="date" class="form-control" name="term_start_date"
                    value="<?php echo $settings['term_start_date'] ?? ''; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Term End Date</label>
                  <input type="date" class="form-control" name="term_end_date"
                    value="<?php echo $settings['term_end_date'] ?? ''; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Session Start Year</label>
                  <input type="text" class="form-control" name="session_start_year"
                    value="<?php echo $settings['session_start_year'] ?? date('Y'); ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Session End Year</label>
                  <input type="text" class="form-control" name="session_end_year"
                    value="<?php echo $settings['session_end_year'] ?? date('Y') + 1; ?>">
                </div>
              </div>
              <button type="submit" name="update_academic" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save Academic Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Fee Settings -->
      <div class="tab-pane fade" id="fee">
        <div class="settings-card">
          <div class="card-header">
            <h6><i class="fas fa-credit-card me-2"></i>Fee Settings</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-6 setting-group">
                  <label>Late Fee Percentage (%)</label>
                  <input type="number" step="0.01" class="form-control" name="late_fee_percentage"
                    value="<?php echo $settings['late_fee_percentage'] ?? '5'; ?>">
                  <div class="help-text">Percentage added for late payments</div>
                </div>
                <div class="col-md-6 setting-group">
                  <label>Late Fee After (Days)</label>
                  <input type="number" class="form-control" name="late_fee_days"
                    value="<?php echo $settings['late_fee_days'] ?? '30'; ?>">
                  <div class="help-text">Days after which late fee applies</div>
                </div>
                <div class="col-md-6 setting-group">
                  <label>Early Discount Percentage (%)</label>
                  <input type="number" step="0.01" class="form-control" name="discount_percentage"
                    value="<?php echo $settings['discount_percentage'] ?? '0'; ?>">
                  <div class="help-text">Discount for early payment</div>
                </div>
                <div class="col-md-6 setting-group">
                  <label>Payment Reminder (Days before due)</label>
                  <input type="number" class="form-control" name="payment_reminder_days"
                    value="<?php echo $settings['payment_reminder_days'] ?? '7'; ?>">
                  <div class="help-text">Send reminder before due date</div>
                </div>
              </div>
              <button type="submit" name="update_fee" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save Fee Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Attendance Settings -->
      <div class="tab-pane fade" id="attendance">
        <div class="settings-card">
          <div class="card-header">
            <h6><i class="fas fa-check-circle me-2"></i>Attendance Settings</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-6 setting-group">
                  <label>Minimum Attendance Threshold (%)</label>
                  <input type="number" step="0.01" class="form-control" name="attendance_threshold"
                    value="<?php echo $settings['attendance_threshold'] ?? '75'; ?>">
                  <div class="help-text">Minimum attendance required</div>
                </div>
                <div class="col-md-6 setting-group">
                  <label>Maximum Late Minutes</label>
                  <input type="number" class="form-control" name="max_late_minutes"
                    value="<?php echo $settings['max_late_minutes'] ?? '15'; ?>">
                  <div class="help-text">After this, marked as absent</div>
                </div>
                <div class="col-md-12 setting-group">
                  <label>Attendance Report Frequency</label>
                  <select class="form-select" name="attendance_report_frequency">
                    <option value="daily" <?php echo ($settings['attendance_report_frequency'] ?? 'weekly') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo ($settings['attendance_report_frequency'] ?? 'weekly') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo ($settings['attendance_report_frequency'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                  </select>
                </div>
              </div>
              <button type="submit" name="update_attendance" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save Attendance Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Library Settings -->
      <div class="tab-pane fade" id="library">
        <div class="settings-card">
          <div class="card-header">
            <h6><i class="fas fa-book me-2"></i>Library Settings</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-6 setting-group">
                  <label>Max Books Per Student</label>
                  <input type="number" class="form-control" name="max_books_per_student"
                    value="<?php echo $settings['max_books_per_student'] ?? '5'; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Loan Duration (Days)</label>
                  <input type="number" class="form-control" name="loan_duration_days"
                    value="<?php echo $settings['loan_duration_days'] ?? '14'; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Late Fee Per Day ($)</label>
                  <input type="number" step="0.01" class="form-control" name="late_fee_per_day"
                    value="<?php echo $settings['late_fee_per_day'] ?? '0.50'; ?>">
                </div>
              </div>
              <button type="submit" name="update_library" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save Library Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Notification Settings -->
      <div class="tab-pane fade" id="notification">
        <div class="settings-card">
          <div class="card-header">
            <h6><i class="fas fa-bell me-2"></i>Notification Settings</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-12 setting-group">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enable_email_notifications" value="1"
                      <?php echo ($settings['enable_email_notifications'] ?? '1') == '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label">Enable Email Notifications</label>
                  </div>
                </div>
                <div class="col-md-12 setting-group">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enable_sms_notifications" value="1"
                      <?php echo ($settings['enable_sms_notifications'] ?? '0') == '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label">Enable SMS Notifications</label>
                  </div>
                </div>
                <div class="col-md-12 setting-group">
                  <label>Admin Email</label>
                  <input type="email" class="form-control" name="admin_email"
                    value="<?php echo $settings['admin_email'] ?? ''; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>SMTP Host</label>
                  <input type="text" class="form-control" name="smtp_host"
                    value="<?php echo $settings['smtp_host'] ?? 'smtp.gmail.com'; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>SMTP Port</label>
                  <input type="text" class="form-control" name="smtp_port"
                    value="<?php echo $settings['smtp_port'] ?? '587'; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>SMTP Username</label>
                  <input type="text" class="form-control" name="smtp_user"
                    value="<?php echo $settings['smtp_user'] ?? ''; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>SMTP Password</label>
                  <input type="password" class="form-control" name="smtp_pass"
                    value="<?php echo $settings['smtp_pass'] ?? ''; ?>">
                </div>
              </div>
              <button type="submit" name="update_notification" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save Notification Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div class="tab-pane fade" id="security">
        <div class="settings-card">
          <div class="card-header">
            <h6><i class="fas fa-shield-alt me-2"></i>Security Settings</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-6 setting-group">
                  <label>Session Timeout (Minutes)</label>
                  <input type="number" class="form-control" name="session_timeout"
                    value="<?php echo $settings['session_timeout'] ?? '30'; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Max Login Attempts</label>
                  <input type="number" class="form-control" name="max_login_attempts"
                    value="<?php echo $settings['max_login_attempts'] ?? '5'; ?>">
                </div>
                <div class="col-md-6 setting-group">
                  <label>Password Expiry (Days)</label>
                  <input type="number" class="form-control" name="password_expiry_days"
                    value="<?php echo $settings['password_expiry_days'] ?? '90'; ?>">
                  <div class="help-text">0 = never expires</div>
                </div>
                <div class="col-md-12 setting-group">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enable_2fa" value="1"
                      <?php echo ($settings['enable_2fa'] ?? '0') == '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label">Enable Two-Factor Authentication</label>
                  </div>
                </div>
              </div>
              <button type="submit" name="update_security" class="btn" style="background-color: #3b82f6; color: white;">
                <i class="fas fa-save me-2"></i>Save Security Settings
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Keep the active tab after form submission
  document.addEventListener('DOMContentLoaded', function() {
    // Get the active tab from URL hash or localStorage
    let activeTab = window.location.hash || localStorage.getItem('activeTab');
    if (activeTab) {
      let tab = document.querySelector(`.nav-settings .nav-link[href="${activeTab}"]`);
      if (tab) {
        bootstrap.Tab.getOrCreateInstance(tab).show();
      }
    }

    // Save active tab when clicked
    document.querySelectorAll('.nav-settings .nav-link').forEach(tab => {
      tab.addEventListener('shown.bs.tab', function(e) {
        localStorage.setItem('activeTab', e.target.getAttribute('href'));
        window.location.hash = e.target.getAttribute('href');
      });
    });
  });
</script>

<?php include '../includes/footer.php'; ?>