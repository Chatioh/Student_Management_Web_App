<?php
session_start();
require_once "config.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

$student_id = $_SESSION["student_id"];

// Get complete student data
$student_data = [];
$sql = "SELECT 
            s.id, s.matricule, s.full_name, s.email, s.phone_number, s.created_at,
            sch.name AS school_name, sch.code AS school_code,
            d.name AS department_name, d.code AS department_code,
            l.name AS level_name
        FROM 
            students s
        LEFT JOIN 
            schools sch ON s.school_id = sch.id
        LEFT JOIN 
            departments d ON s.department_name = d.id
        LEFT JOIN 
            levels l ON d.level_id = l.id
        WHERE 
            s.id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Get academic statistics
$academic_stats = [];

// Get total courses enrolled
$sql = "SELECT COUNT(*) as total_courses FROM enrollments WHERE student_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $academic_stats['total_courses'] = mysqli_fetch_assoc($result)['total_courses'];
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
    $academic_stats['total_credits'] = mysqli_fetch_assoc($result)['total_credits'] ?? 0;
    mysqli_stmt_close($stmt);
}

// Get overall GPA
$sql = "SELECT AVG(er.total_score) as avg_grade 
        FROM exam_results er 
        JOIN enrollments e ON er.enrollment_id = e.id 
        WHERE e.student_id = ? AND er.total_score > 0";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $avg_score = mysqli_fetch_assoc($result)['avg_grade'] ?? 0;
    
    // Convert to GPA scale (4.0)
    if ($avg_score >= 90) $academic_stats['gpa'] = 4.0;
    elseif ($avg_score >= 80) $academic_stats['gpa'] = 3.0;
    elseif ($avg_score >= 70) $academic_stats['gpa'] = 2.0;
    elseif ($avg_score >= 60) $academic_stats['gpa'] = 1.0;
    else $academic_stats['gpa'] = 0.0;
    
    mysqli_stmt_close($stmt);
}

// Get completed courses
$sql = "SELECT COUNT(*) as completed_courses 
        FROM enrollments e 
        JOIN exam_results er ON er.enrollment_id = e.id 
        WHERE e.student_id = ? AND er.total_score >= 60";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $academic_stats['completed_courses'] = mysqli_fetch_assoc($result)['completed_courses'];
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Management Dashboard</title>
    <link href="assets/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/fontawesome-free-6.7.2-web/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/courses.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">SMA</div>
        
        <div class="nav-section">
            <a href="home.php" class="nav-link">
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
            <a href="profile.php" class="nav-link active">
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

              <!-- Profile Header -->
              <div class="row mb-4">
                  <div class="col-12">
                      <div class="chart-container">
                          <div class="row align-items-center">
                              <div class="col-md-3 text-center">
                                  <div class="profile-avatar mx-auto mb-3">
                                      <?php echo strtoupper(substr($student_data["full_name"], 0, 2)); ?>
                                  </div>
                                  <h5 class="mb-1"><?php echo htmlspecialchars($student_data["full_name"]); ?></h5>
                                  <p class="text-muted mb-0"><?php echo htmlspecialchars($student_data["matricule"]); ?></p>
                              </div>
                              <div class="col-md-9">
                                  <div class="row">
                                      <div class="col-md-6">
                                          <h6 class="text-muted mb-2">Academic Information</h6>
                                          <p class="mb-1"><strong>School:</strong> <?php echo htmlspecialchars($student_data["school_name"]); ?></p>
                                          <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($student_data["department_name"]); ?></p>
                                          <p class="mb-1"><strong>Level:</strong> <?php echo htmlspecialchars($student_data["level_name"]); ?></p>
                                      </div>
                                      <div class="col-md-6">
                                          <h6 class="text-muted mb-2">Contact Information</h6>
                                          <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($student_data["email"]); ?></p>
                                          <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($student_data["phone_number"]); ?></p>
                                          <p class="mb-1"><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($student_data["created_at"])); ?></p>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Academic Statistics -->
              <div class="row mb-4">
                  <div class="col-md-3">
                      <div class="stat-card text-center">
                          <div class="stat-icon mx-auto mb-2" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                              <i class="fas fa-book"></i>
                          </div>
                          <div class="stat-value"><?php echo $academic_stats['total_courses']; ?></div>
                          <div class="stat-label">Total Courses</div>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="stat-card text-center">
                          <div class="stat-icon mx-auto mb-2" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
                              <i class="fas fa-graduation-cap"></i>
                          </div>
                          <div class="stat-value"><?php echo $academic_stats['total_credits']; ?></div>
                          <div class="stat-label">Credit Hours</div>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="stat-card text-center">
                          <div class="stat-icon mx-auto mb-2" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                              <i class="fas fa-chart-line"></i>
                          </div>
                          <div class="stat-value"><?php echo number_format($academic_stats['gpa'], 2); ?></div>
                          <div class="stat-label">GPA</div>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="stat-card text-center">
                          <div class="stat-icon mx-auto mb-2" style="background-color: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                              <i class="fas fa-check-circle"></i>
                          </div>
                          <div class="stat-value"><?php echo $academic_stats['completed_courses']; ?></div>
                          <div class="stat-label">Completed</div>
                      </div>
                  </div>
              </div>

              <!-- Detailed Information -->
              <div class="row">
                  <div class="col-md-6">
                      <div class="chart-container">
                          <h5 class="mb-3">Personal Information</h5>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Full Name</label>
                              <div class="form-control-plaintext"><?php echo htmlspecialchars($student_data["full_name"]); ?></div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Matricule Number</label>
                              <div class="form-control-plaintext"><?php echo htmlspecialchars($student_data["matricule"]); ?></div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Email Address</label>
                              <div class="form-control-plaintext"><?php echo htmlspecialchars($student_data["email"]); ?></div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Phone Number</label>
                              <div class="form-control-plaintext"><?php echo htmlspecialchars($student_data["phone_number"]); ?></div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Registration Date</label>
                              <div class="form-control-plaintext"><?php echo date('F j, Y \a\t g:i A', strtotime($student_data["created_at"])); ?></div>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-6">
                      <div class="chart-container">
                          <h5 class="mb-3">Academic Information</h5>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">School</label>
                              <div class="form-control-plaintext">
                                  <?php echo htmlspecialchars($student_data["school_name"]); ?>
                                  <small class="text-muted">(<?php echo htmlspecialchars($student_data["school_code"]); ?>)</small>
                              </div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Department</label>
                              <div class="form-control-plaintext">
                                  <?php echo htmlspecialchars($student_data["department_name"]); ?>
                                  <small class="text-muted">(<?php echo htmlspecialchars($student_data["department_code"]); ?>)</small>
                              </div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Level</label>
                              <div class="form-control-plaintext"><?php echo htmlspecialchars($student_data["level_name"]); ?></div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Student ID</label>
                              <div class="form-control-plaintext"><?php echo htmlspecialchars($student_data["id"]); ?></div>
                          </div>
                          <div class="info-item mb-3">
                              <label class="form-label text-muted">Academic Status</label>
                              <div class="form-control-plaintext">
                                  <span class="badge bg-success">Active</span>
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
    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

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
    </script>

    <style>
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c5ce7, #74b9ff);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .info-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .form-control-plaintext {
            padding: 0.375rem 0;
            font-weight: 500;
        }
    </style>
</body>
</html>

