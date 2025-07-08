<?php
session_start();

require_once "config.php"; // Adjust path if necessary

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php"); // Adjust path if necessary
    exit;
}

$student_id = $_SESSION["student_id"];
$student_data = [];
$sql = "SELECT 
            s.full_name, s.matricule, s.email, s.phone_number, 
            sch.name AS school_name, d.name AS department_name
        FROM 
            students s
        LEFT JOIN 
            schools sch ON s.school_id = sch.id
        LEFT JOIN 
            departments d ON s.department_name = d.id
        WHERE 
            s.id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $student_data = mysqli_fetch_assoc($result);
        } else {
            // Handle case where student data is not found (shouldn't happen if logged in)
            echo "Error: Student data not found.";
            exit();
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
        exit();
    }
    mysqli_stmt_close($stmt);
}

// Get student statistics
$stats = [];

// Get total enrolled courses
$sql = "SELECT COUNT(*) as total_courses FROM enrollments WHERE student_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['total_courses'] = mysqli_fetch_assoc($result)['total_courses'];
    mysqli_stmt_close($stmt);
}

// Get total credit hours
$sql = "SELECT SUM(c.credit_hours) as total_credits 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['total_credits'] = mysqli_fetch_assoc($result)['total_credits'] ?? 0;
    mysqli_stmt_close($stmt);
}

// Get average grade
$sql = "SELECT AVG(er.total_score) as avg_grade 
        FROM exam_results er 
        JOIN enrollments e ON er.enrollment_id = e.id 
        WHERE e.student_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['avg_grade'] = mysqli_fetch_assoc($result)['avg_grade'] ?? 0;
    mysqli_stmt_close($stmt);
}

// Get unread notifications count
$sql = "SELECT COUNT(*) as unread_notifications FROM notifications WHERE student_id = ? AND is_read = 0";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['unread_notifications'] = mysqli_fetch_assoc($result)['unread_notifications'];
    mysqli_stmt_close($stmt);
}

// Get semester-wise course distribution
$sql = "SELECT c.semester, COUNT(*) as course_count 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_id = ? 
        GROUP BY c.semester";
$semester_courses = [];
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $semester_courses[$row['semester']] = $row['course_count'];
    }
    mysqli_stmt_close($stmt);
}

$display_message = "";
if (isset($_SESSION["display_message"])) {
    $display_message = $_SESSION["display_message"];
    unset($_SESSION["display_message"]); // Clear the message after displaying
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management Dashboard</title>
    <link href="assets/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="assets/libs/fontawesome-free-6.7.2-web/css/all.min.css" rel="stylesheet">
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"> -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">SMA</div>
        
        <div class="nav-section">
            <a href="#" class="nav-link active">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="courses.php" class="nav-link">
                <i class="fas fa-th-large"></i>
                Courses 
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="examresults.php" class="nav-link">
                <i class="fas fa-th-large"></i>
                Exam Results 
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="notifications.php" class="nav-link">
                <i class="fas fa-bell"></i>
                Notifications 
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="#" class="nav-link">
                <i class="fas fa-cog"></i>
                Settings
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user-alt"></i>
                Profile
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="#" class="nav-link" onclick="showLogoutModal()">
                <i class="fas fa-sign-out-alt"></i>
                Logout
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content px-0" id="mainContent">
        <div class="container-fluid">
          <div class="container">
              <!-- Top Bar -->
              <div class="top-bar">
                  <div class="d-flex align-items-center flex-grow-1">
                      <button class="mobile-toggle me-3" id="mobileToggle">
                          <i class="fas fa-bars"></i>
                      </button>
                      <div class="search-box">
                          <i class="fas fa-search"></i>
                          <input type="text" class="form-control" placeholder="Search...">
                      </div>
                      <div class="user-avatar ms-2"><?php echo strtoupper(substr($student_data["full_name"], 0, 1)); ?></div>
                  </div>
              </div>
              <?php if (!empty($display_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $display_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>
  
              <!-- Welcome Card -->
              <div class="welcome-card">
                  <div class="row align-items-center">
                      <div class="col-md-8">
                          <h2 class="mb-3">Welcome, <?php echo htmlspecialchars($student_data["full_name"]); ?>! 🎉</h2>
                          <p class="mb-3">Matricule: <?php echo htmlspecialchars($student_data["matricule"]); ?></p>
                          <p class="mb-3">Department: <?php echo htmlspecialchars($student_data["department_name"]); ?></p> 
                          <button class="btn btn-light">View Badges</button>
                      </div>
                      <div class="col-md-4">
                          <div class="welcome-illustration">
                              <i class="fas fa-user-graduate"></i>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="progress-grid row">
              </div>

              <!-- Stats Grid -->
              <div class="stats-grid">
                  <div class="stat-card">
                      <div class="stat-icon" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                          <i class="fas fa-book"></i>
                      </div>
                      <div class="stat-value"><?php echo $stats['total_courses']; ?></div>
                      <div class="stat-label">Enrolled Courses</div>
                      <div class="stat-change positive">
                          <i class="fas fa-arrow-up"></i> Active
                      </div>
                  </div>

                  <div class="stat-card">
                      <div class="stat-icon" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
                          <i class="fas fa-graduation-cap"></i>
                      </div>
                      <div class="stat-value"><?php echo $stats['total_credits']; ?></div>
                      <div class="stat-label">Credit Hours</div>
                      <div class="stat-change positive">
                          <i class="fas fa-arrow-up"></i> Total Credits
                      </div>
                  </div>

                  <div class="stat-card">
                      <div class="stat-icon" style="background-color: rgba(231, 76, 60, 0.1); color: var(--danger-color);">
                          <i class="fas fa-bell"></i>
                      </div>
                      <div class="stat-value"><?php echo $stats['unread_notifications']; ?></div>
                      <div class="stat-label">Notifications</div>
                      <div class="stat-change <?php echo $stats['unread_notifications'] > 0 ? 'negative' : 'positive'; ?>">
                          <i class="fas fa-<?php echo $stats['unread_notifications'] > 0 ? 'exclamation' : 'check'; ?>"></i> 
                          <?php echo $stats['unread_notifications'] > 0 ? 'Unread' : 'All Read'; ?>
                      </div>
                  </div>

                  <div class="stat-card">
                      <div class="stat-icon" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                          <i class="fas fa-chart-line"></i>
                      </div>
                      <div class="stat-value"><?php echo number_format($stats['avg_grade'], 1); ?>%</div>
                      <div class="stat-label">Average Grade</div>
                      <div class="stat-change <?php echo $stats['avg_grade'] >= 70 ? 'positive' : 'negative'; ?>">
                          <i class="fas fa-arrow-<?php echo $stats['avg_grade'] >= 70 ? 'up' : 'down'; ?>"></i> 
                          <?php echo $stats['avg_grade'] >= 70 ? 'Good' : 'Needs Improvement'; ?>
                      </div>
                  </div>
              </div>

              <!-- Charts Row -->
              <div class="row gy-3">
                <!-- Attendance & Credit Card -->
                <div class="col-lg-8 py-0">
                    <div class="chart-container">
                        <h5 class="mb-4">Student Progress</h5>
                        <!-- Attendance Progress -->
                        <div class="progress-item mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="progress-icon me-3">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Attendance</h6>
                                        <small class="text-muted">This month</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success">85%</div>
                                    <small class="text-muted">28/33 days</small>
                                </div>
                            </div>
                            <div class="progress custom-progress">
                                <div class="progress-bar bg-success progress-bar-animated" 
                                    role="progressbar" 
                                    style="width: 85%" 
                                    aria-valuenow="85" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                        </div>

                        <!-- Credit Progress -->
                        <div class="progress-item mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="progress-icon me-3">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Credits Earned</h6>
                                        <small class="text-muted">Current semester</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">72%</div>
                                    <small class="text-muted">18/25 credits</small>
                                </div>
                            </div>
                            <div class="progress custom-progress">
                                <div class="progress-bar bg-primary progress-bar-animated" 
                                    role="progressbar" 
                                    style="width: 72%" 
                                    aria-valuenow="72" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <!-- Credit Progress -->
                        <div class="progress-item mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="progress-icon me-3">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Credits Earned</h6>
                                        <small class="text-muted">Current semester</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">72%</div>
                                    <small class="text-muted">18/25 credits</small>
                                </div>
                            </div>
                            <div class="progress custom-progress">
                                <div class="progress-bar bg-primary progress-bar-animated" 
                                    role="progressbar" 
                                    style="width: 72%" 
                                    aria-valuenow="72" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Stats -->
                        <div class="row text-center mt-4">
                            <div class="col-6">
                                <div class="border-end">
                                    <div class="fw-bold text-success fs-5">28</div>
                                    <small class="text-muted">Present Days</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold text-primary fs-5">18</div>
                                <small class="text-muted">Credits</small>
                            </div>
                        </div>
                    </div>
                </div>
                  <div class="col-lg-4">
                      <div class="chart-container text-center">
                          <h5 class="mb-4">Growth</h5>
                          <div class="circular-progress">
                              <canvas id="growthChart" width="120" height="120"></canvas>
                          </div>
                          <h3 class="text-primary mb-1">78%</h3>
                          <p class="text-muted mb-3">Growth</p>
                          <p class="small text-muted mb-3">62% Company Growth</p>
                          <div class="row text-center">
                              <div class="col">
                                  <div class="small text-muted">2022</div>
                                  <div class="fw-bold">$32.5k</div>
                              </div>
                              <div class="col">
                                  <div class="small text-muted">2021</div>
                                  <div class="fw-bold">$41.2k</div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
        </div>
    </main>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');

        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });

        // Show logout modal
        function showLogoutModal() {
            const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
            logoutModal.show();
        }

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: '2021',
                    data: [18, 7, 15, 29, 18, 12, 9],
                    backgroundColor: '#6c5ce7',
                    borderRadius: 8,
                    maxBarThickness: 20
                }, {
                    label: '2020',
                    data: [-12, -19, -3, -17, -28, -24, -20],
                    backgroundColor: '#74b9ff',
                    borderRadius: 8,
                    maxBarThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Growth Circular Chart
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        new Chart(growthCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [78, 22],
                    backgroundColor: ['#6c5ce7', '#e0e6ed'],
                    borderWidth: 0,
                    cutout: '80%'
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>