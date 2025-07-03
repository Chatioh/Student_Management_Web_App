<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span>Admin Panel</span>
                </div>
                <!-- This button is for mobile to open/close sidebar -->
                <button type="button" id="sidebarCollapse" class="btn btn-link d-md-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <ul class="list-unstyled components">
                <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "dashboard.php" ? "active" : ""; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "students.php" ? "active" : ""; ?>">
                    <a href="students.php">
                        <i class="fas fa-users"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "attendance.php" ? "active" : ""; ?>">
                    <a href="attendance.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "courses.php" ? "active" : ""; ?>">
                    <a href="courses.php">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "departments.php" ? "active" : ""; ?>">
                    <a href="departments.php">
                        <i class="fas fa-building"></i>
                        <span>Departments</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "exam_results.php" ? "active" : ""; ?>">
                    <a href="exam_results.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Exam Results</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER["PHP_SELF"]) == "transcripts.php" ? "active" : ""; ?>">
                    <a href="transcripts.php">
                        <i class="fas fa-file-alt"></i>
                        <span>Transcripts</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-details">
                        <div class="admin-name">Administrator</div>
                        <div class="admin-role">System Admin</div>
                    </div>
                </div>
                <div class="sidebar-actions">
                    <a href="#" class="btn btn-outline-light btn-sm me-2" title="Settings">
                        <i class="fas fa-cog"></i>
                    </a>
                    <a href="../index.php" class="btn btn-outline-light btn-sm" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div id="content" class="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <!-- This button is for mobile to open/close sidebar -->
                    <button type="button" id="sidebarCollapseTop" class="btn btn-link d-md-none">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger">3</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-plus me-2"></i>New student registered</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-calendar me-2"></i>Attendance due today</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-chart-line me-2"></i>Exam results pending</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">View all notifications</a></li>
                            </ul>
                        </div>
                        
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar-small">A</div>
                                <span class="ms-2">Admin</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="container-fluid p-0">
