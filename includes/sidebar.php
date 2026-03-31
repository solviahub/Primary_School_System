<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* Sidebar Styles - Enhanced with Proper Positioning */
    .sidebar-menu {
        background: linear-gradient(135deg, #1e3a5f 0%, #2b5f8a 100%) !important;
        border-right: 1px solid #08e9c7;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 1rem 0;
        transition: all 0.3s ease;
        position: relative;
    }

    /* Sidebar Collapsed State */
    .sidebar.collapsed {
        width: 70px;
    }

    .sidebar.collapsed .sidebar-menu {
        width: 70px;
    }

    .sidebar.collapsed .nav-link span,
    .sidebar.collapsed .sidebar-heading span,
    .sidebar.collapsed .action-text strong,
    .sidebar.collapsed .action-text small {
        display: none;
    }

    .sidebar.collapsed .nav-link {
        justify-content: center;
        padding: 0.7rem;
    }

    .sidebar.collapsed .nav-link i {
        margin: 0;
        font-size: 1.2rem;
    }

    .sidebar.collapsed .sidebar-heading {
        text-align: center;
        padding: 0.75rem 0;
    }

    .sidebar.collapsed .sidebar-heading i {
        margin: 0;
        font-size: 1rem;
    }

    .sidebar.collapsed .sidebar-heading span {
        display: none;
    }

    /* Toggle Button */
    .sidebar-toggle {
        position: absolute;
        right: -12px;
        top: 20px;
        width: 24px;
        height: 24px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 100;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .sidebar-toggle:hover {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .sidebar.collapsed .sidebar-toggle {
        transform: rotate(180deg);
    }

    /* Modern Scrollbar */
    .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-menu::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Sidebar Heading */
    .sidebar-heading {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: #94a3b8;
        padding: 0.75rem 1.2rem 0.5rem;
        margin: 0;
        white-space: nowrap;
    }

    /* Navigation */
    .nav {
        display: flex;
        flex-direction: column;
        padding-left: 0;
        margin-bottom: 0;
        list-style: none;
    }

    .nav-item {
        margin: 0.15rem 0.6rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.7rem 1rem;
        color: #878d94;
        font-size: 0.85rem;
        font-weight: 500;
        border-radius: 12px;
        transition: all 0.2s ease;
        text-decoration: none;
        position: relative;
        white-space: nowrap;
    }

    .nav-link i {
        width: 22px;
        font-size: 1rem;
        color: #94a3b8;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .nav-link:hover {
        background: #f8fafc;
        color: #3b82f6;
        transform: translateX(4px);
    }

    .nav-link:hover i {
        color: #3b82f6;
    }

    .nav-link.active {
        background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
        color: #3b82f6;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.08);
    }

    .nav-link.active i {
        color: #3b82f6;
    }

    /* Menu Sections */
    .menu-section {
        margin-bottom: 1rem;
    }

    .menu-divider {
        height: 1px;
        background: linear-gradient(90deg, #eef2f8, transparent);
        margin: 0.8rem 1.2rem;
    }

    /* Badge for notifications */
    .nav-badge {
        margin-left: auto;
        background: #ef4444;
        color: white;
        font-size: 0.6rem;
        padding: 0.15rem 0.45rem;
        border-radius: 20px;
        font-weight: 600;
    }

    /* Active indicator */
    .nav-link.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 0 4px 4px 0;
    }

    /* Hover effect for touch devices */
    @media (hover: none) {
        .nav-link:hover {
            transform: none;
        }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            left: -280px;
            z-index: 1050;
            transition: left 0.3s ease;
            height: 100vh;
            top: 60px;
            width: 280px !important;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-menu {
            height: 100%;
        }

        .sidebar-overlay {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .sidebar-toggle-mobile {
            display: flex;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: #3b82f6;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            z-index: 1060;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            border: none;
        }

        .sidebar.collapsed {
            width: auto;
        }
    }

    @media (min-width: 769px) {
        .sidebar-toggle-mobile {
            display: none;
        }

        .sidebar-overlay {
            display: none;
        }
    }
</style>

<div class="sidebar-menu">
    <!-- Toggle Button for Desktop -->
    <div class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-chevron-left"></i>
    </div>

    <?php if ($role == 'admin'): ?>
        <!-- Admin Menu -->
        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-chart-line" style="font-size: 0.65rem; margin-right: 0.5rem;"></i>
                <span>MAIN NAVIGATION</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-users" style="font-size: 0.65rem; margin-right: 0.5rem;"></i>
                <span>USER MANAGEMENT</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/manage_users.php">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_students.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/manage_students.php">
                        <i class="fas fa-user-graduate"></i>
                        <span>Manage Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_teachers.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/manage_teachers.php">
                        <i class="fas fa-chalkboard-user"></i>
                        <span>Manage Teachers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'assign_subject_teacher.php' ? 'active' : ''; ?>"
                        href="<?php echo SITE_URL; ?>admin/assign_subject_teacher.php">
                        <i class="fas fa-chalkboard-user"></i> Assign Subject Teachers
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-graduation-cap" style="font-size: 0.65rem; margin-right: 0.5rem;"></i>
                <span>ACADEMIC</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_classes.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/manage_classes.php">
                        <i class="fas fa-chalkboard"></i>
                        <span>Manage Classes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_subjects.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/manage_subjects.php">
                        <i class="fas fa-book"></i>
                        <span>Manage Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'promote_students.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/promote_students.php">
                        <i class="fas fa-arrow-up"></i>
                        <span>Promote Students</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-chart-bar" style="font-size: 0.65rem; margin-right: 0.5rem;"></i>
                <span>REPORTS & ANALYTICS</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'attendance_reports.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/attendance_reports.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Attendance Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'financial_reports.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/financial_reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Financial Reports</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-tools" style="font-size: 0.65rem; margin-right: 0.5rem;"></i>
                <span>COMMUNICATION & SETTINGS</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/calendar.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>School Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'announcements.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/announcements.php">
                        <i class="fas fa-bullhorn"></i>
                        <span>Announcements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>admin/settings.php">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                </li>
            </ul>
        </div>

    <?php elseif ($role == 'teacher'): ?>
        <!-- Teacher Menu -->
        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-chalkboard-user"></i>
                <span>TEACHER DASHBOARD</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>teacher/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'view_classes.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>teacher/view_classes.php">
                        <i class="fas fa-chalkboard"></i>
                        <span>My Classes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'view_students.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>teacher/view_students.php">
                        <i class="fas fa-users"></i>
                        <span>View Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'mark_attendance.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>teacher/mark_attendance.php">
                        <i class="fas fa-check-circle"></i>
                        <span>Mark Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'upload_marks.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>teacher/upload_marks.php">
                        <i class="fas fa-upload"></i>
                        <span>Upload Marks</span>
                    </a>
                </li>
            </ul>
        </div>

    <?php elseif ($role == 'parent'): ?>
        <!-- Parent Menu -->
        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-user-friends"></i>
                <span>PARENT PORTAL</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>parent/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'view_results.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>parent/view_results.php">
                        <i class="fas fa-chart-line"></i>
                        <span>View Results</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'view_attendance.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>parent/view_attendance.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>View Attendance</span>
                    </a>
                </li>
            </ul>
        </div>

    <?php elseif ($role == 'student'): ?>
        <!-- Student Menu -->
        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-user-graduate"></i>
                <span>STUDENT DASHBOARD</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>student/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'view_results.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>student/view_results.php">
                        <i class="fas fa-chart-line"></i>
                        <span>My Results</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'view_attendance.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>student/view_attendance.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>My Attendance</span>
                    </a>
                </li>
            </ul>
        </div>

    <?php elseif ($role == 'librarian'): ?>
        <!-- Librarian Menu -->
        <div class="menu-section">
            <h6 class="sidebar-heading">
                <i class="fas fa-book"></i>
                <span>LIBRARY MANAGEMENT</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>librarian/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_books.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>librarian/manage_books.php">
                        <i class="fas fa-book"></i>
                        <span>Manage Books</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'issue_books.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>librarian/issue_books.php">
                        <i class="fas fa-hand-holding"></i>
                        <span>Issue Books</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'return_books.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>librarian/return_books.php">
                        <i class="fas fa-undo-alt"></i>
                        <span>Return Books</span>
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Footer Section in Sidebar -->
    <div class="menu-divider"></div>
    <div class="menu-section">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Mobile Toggle Button -->
<button class="sidebar-toggle-mobile" onclick="toggleMobileSidebar()">
    <i class="fas fa-bars"></i>
</button>
<div class="sidebar-overlay" onclick="toggleMobileSidebar()"></div>

<script>
    // Toggle sidebar collapse/expand
    let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('main');

        sidebarCollapsed = !sidebarCollapsed;
        localStorage.setItem('sidebarCollapsed', sidebarCollapsed);

        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            if (mainContent) mainContent.style.marginLeft = '70px';
            document.querySelector('.sidebar-toggle i').className = 'fas fa-chevron-right';
        } else {
            sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.style.marginLeft = '280px';
            document.querySelector('.sidebar-toggle i').className = 'fas fa-chevron-left';
        }
    }

    // Mobile sidebar toggle
    function toggleMobileSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }

    // Apply saved state on load
    document.addEventListener('DOMContentLoaded', function() {
        const mainContent = document.querySelector('main');
        const sidebar = document.querySelector('.sidebar');

        if (sidebarCollapsed && window.innerWidth > 768) {
            sidebar.classList.add('collapsed');
            if (mainContent) mainContent.style.marginLeft = '70px';
            const toggleIcon = document.querySelector('.sidebar-toggle i');
            if (toggleIcon) toggleIcon.className = 'fas fa-chevron-right';
        } else if (window.innerWidth > 768) {
            if (mainContent) mainContent.style.marginLeft = '280px';
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                if (sidebarCollapsed) {
                    if (mainContent) mainContent.style.marginLeft = '70px';
                } else {
                    if (mainContent) mainContent.style.marginLeft = '280px';
                }
                sidebar.classList.remove('show');
                document.querySelector('.sidebar-overlay')?.classList.remove('show');
            } else {
                if (mainContent) mainContent.style.marginLeft = '0';
            }
        });

        // Highlight current page
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href) && href !== '#') {
                link.classList.add('active');
            }
        });
    });
</script>