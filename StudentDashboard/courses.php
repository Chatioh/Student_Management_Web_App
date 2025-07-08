<?php
session_start();
require_once "config.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

$student_id = $_SESSION["student_id"];

// Get student data for profile icon
$student_data = [];
$sql = "SELECT full_name FROM students WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Get courses by semester for the logged-in student
$first_semester_courses = [];
$second_semester_courses = [];

$sql = "SELECT c.id, c.code, c.name, c.credit_hours, c.semester, 
               COALESCE(er.total_score, 0) as grade,
               CASE 
                   WHEN er.total_score >= 90 THEN 'A'
                   WHEN er.total_score >= 80 THEN 'B'
                   WHEN er.total_score >= 70 THEN 'C'
                   WHEN er.total_score >= 60 THEN 'D'
                   ELSE 'F'
               END as letter_grade
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN exam_results er ON er.enrollment_id = e.id
        WHERE e.student_id = ?
        ORDER BY c.semester, c.code";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['semester'] == 'First Semester') {
            $first_semester_courses[] = $row;
        } else if ($row['semester'] == 'Second Semester') {
            $second_semester_courses[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Calculate statistics for each semester
function calculateSemesterStats($courses) {
    $total_courses = count($courses);
    $total_credits = array_sum(array_column($courses, 'credit_hours'));
    $avg_grade = $total_courses > 0 ? array_sum(array_column($courses, 'grade')) / $total_courses : 0;
    $completed_courses = count(array_filter($courses, function($course) {
        return $course['grade'] > 0;
    }));
    
    return [
        'total_courses' => $total_courses,
        'total_credits' => $total_credits,
        'avg_grade' => $avg_grade,
        'completed_courses' => $completed_courses,
        'progress' => $total_courses > 0 ? ($completed_courses / $total_courses) * 100 : 0
    ];
}

$first_sem_stats = calculateSemesterStats($first_semester_courses);
$second_sem_stats = calculateSemesterStats($second_semester_courses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - Student Management Dashboard</title>
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
            <a href="courses.php" class="nav-link active">
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

              <div class="progress-grid row">
                <!-- First Semester Course Progress Card -->
                <div class="col-lg-6 d-grid row-gap-3 py-2">
                    <div class="chart-container course-progress-container m-0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">First Semester Courses</h5>
                            <span class="badge bg-primary"><?php echo $first_sem_stats['total_courses']; ?> Courses</span>
                        </div>
                        
                        <!-- Scrollable Course List -->
                        <div class="courses-scroll-container">
                            <?php if (!empty($first_semester_courses)): ?>
                                <?php foreach ($first_semester_courses as $course): ?>
                                    <div class="course-item mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <div class="course-icon me-3">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($course['name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($course['code']); ?> • <?php echo $course['credit_hours']; ?> Credits</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="grade-badge grade-<?php echo strtolower($course['letter_grade']); ?>">
                                                    <?php echo $course['letter_grade']; ?>
                                                </span>
                                                <div class="course-percentage"><?php echo number_format($course['grade'], 0); ?>%</div>
                                            </div>
                                        </div>
                                        <div class="progress custom-course-progress mb-2">
                                            <div class="progress-bar <?php echo $course['grade'] >= 70 ? 'bg-success' : ($course['grade'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo min($course['grade'], 100); ?>%" 
                                                aria-valuenow="<?php echo $course['grade']; ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted"><?php echo $course['credit_hours']; ?> credit hours</small>
                                            <small class="<?php echo $course['grade'] >= 70 ? 'text-success' : ($course['grade'] >= 60 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $course['grade'] >= 70 ? 'Passed' : ($course['grade'] > 0 ? 'Needs Improvement' : 'Pending'); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No courses enrolled for first semester</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Overall Summary -->
                        <div class="course-summary mt-3 pt-3 border-top">
                            <div class="row text-center d-flex gap-0">
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-primary fs-6"><?php echo number_format($first_sem_stats['avg_grade'], 1); ?>%</div>
                                    <small class="text-muted">Avg Grade</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-success fs-6"><?php echo $first_sem_stats['total_credits']; ?></div>
                                    <small class="text-muted">Credits</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-info fs-6"><?php echo $first_sem_stats['completed_courses']; ?>/<?php echo $first_sem_stats['total_courses']; ?></div>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Semester Course Progress Card -->
                <div class="col-lg-6 d-grid row-gap-3 py-2">
                    <div class="chart-container course-progress-container m-0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Second Semester Courses</h5>
                            <span class="badge bg-secondary"><?php echo $second_sem_stats['total_courses']; ?> Courses</span>
                        </div>
                        
                        <!-- Scrollable Course List -->
                        <div class="courses-scroll-container">
                            <?php if (!empty($second_semester_courses)): ?>
                                <?php foreach ($second_semester_courses as $course): ?>
                                    <div class="course-item mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <div class="course-icon me-3">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($course['name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($course['code']); ?> • <?php echo $course['credit_hours']; ?> Credits</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="grade-badge grade-<?php echo strtolower($course['letter_grade']); ?>">
                                                    <?php echo $course['letter_grade']; ?>
                                                </span>
                                                <div class="course-percentage"><?php echo number_format($course['grade'], 0); ?>%</div>
                                            </div>
                                        </div>
                                        <div class="progress custom-course-progress mb-2">
                                            <div class="progress-bar <?php echo $course['grade'] >= 70 ? 'bg-success' : ($course['grade'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo min($course['grade'], 100); ?>%" 
                                                aria-valuenow="<?php echo $course['grade']; ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted"><?php echo $course['credit_hours']; ?> credit hours</small>
                                            <small class="<?php echo $course['grade'] >= 70 ? 'text-success' : ($course['grade'] >= 60 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $course['grade'] >= 70 ? 'Passed' : ($course['grade'] > 0 ? 'Needs Improvement' : 'Pending'); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No courses enrolled for second semester</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Overall Summary -->
                        <div class="course-summary mt-3 pt-3 border-top">
                            <div class="row text-center d-flex gap-0">
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-primary fs-6"><?php echo number_format($second_sem_stats['avg_grade'], 1); ?>%</div>
                                    <small class="text-muted">Avg Grade</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-success fs-6"><?php echo $second_sem_stats['total_credits']; ?></div>
                                    <small class="text-muted">Credits</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-info fs-6"><?php echo $second_sem_stats['completed_courses']; ?>/<?php echo $second_sem_stats['total_courses']; ?></div>
                                    <small class="text-muted">Completed</small>
                                </div>
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
</body>
</html>

