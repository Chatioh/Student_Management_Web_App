<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management Dashboard</title>
    <link href="assets/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/fontawesome-free-6.7.2-web/css/all.min.css" rel="stylesheet">
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"> -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
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
            <a href="" class="nav-link">
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
            <a href="#" class="nav-link">
                <i class="fas fa-user-alt"></i>
                Profile
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
                      <div class="user-avatar ms-2">J</div>
                  </div>
              </div>

              <div class="progress-grid row">
                <!-- Course Progress Card -->
                <div class="col-lg-12 d-grid row-gap-3 py-2">
                    <div class="chart-container course-progress-container m-0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">First Semester Course Progress</h5>
                            <span class="badge bg-primary">4 Active Courses</span>
                        </div>
                        
                        <!-- Scrollable Course List -->
                        <div class="courses-scroll-container">
                            <!-- Course Item 1 -->
                            <div class="course-item mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-3">
                                        <i class="fas fa-laptop-code"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Web Development</h6>
                                        <small class="text-muted">CS-301 • Prof. Johnson</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="grade-badge grade-a">A</span>
                                    <div class="course-percentage">92%</div>
                                </div>
                            </div>
                            <div class="progress custom-course-progress mb-2">
                                <div class="progress-bar bg-success" 
                                    role="progressbar" 
                                    style="width: 92%" 
                                    aria-valuenow="92" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">12/13 modules completed</small>
                                <small class="text-success">Next: Final Project</small>
                            </div>
                        </div>

                        <!-- Course Item 2 -->
                        <div class="course-item mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-3">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Database Systems</h6>
                                        <small class="text-muted">CS-205 • Prof. Smith</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="grade-badge grade-b">B+</span>
                                    <div class="course-percentage">78%</div>
                                </div>
                            </div>
                            <div class="progress custom-course-progress mb-2">
                                <div class="progress-bar bg-primary" 
                                    role="progressbar" 
                                    style="width: 78%" 
                                    aria-valuenow="78" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">9/12 modules completed</small>
                                <small class="text-warning">Due: Assignment 3</small>
                            </div>
                        </div>

                        <!-- Course Item 3 -->
                        <div class="course-item mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Data Analytics</h6>
                                        <small class="text-muted">MATH-401 • Prof. Wilson</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="grade-badge grade-b">B</span>
                                    <div class="course-percentage">85%</div>
                                </div>
                            </div>
                            <div class="progress custom-course-progress mb-2">
                                <div class="progress-bar bg-info" 
                                    role="progressbar" 
                                    style="width: 85%" 
                                    aria-valuenow="85" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">10/12 modules completed</small>
                                <small class="text-info">Next: Midterm Exam</small>
                            </div>
                        </div>

                            <!-- Course Item 4 -->
                            <div class="course-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-3">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Mobile App Design</h6>
                                            <small class="text-muted">DES-302 • Prof. Davis</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-c">B-</span>
                                        <div class="course-percentage">68%</div>
                                    </div>
                                </div>
                                <div class="progress custom-course-progress mb-2">
                                    <div class="progress-bar bg-warning" 
                                        role="progressbar" 
                                        style="width: 68%" 
                                        aria-valuenow="68" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">8/12 modules completed</small>
                                    <small class="text-danger">Overdue: Quiz 4</small>
                                </div>
                            </div>

                            <!-- Course Item 5 (Additional for scrolling demo) -->
                            <div class="course-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-3">
                                            <i class="fas fa-brain"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Machine Learning</h6>
                                            <small class="text-muted">CS-501 • Prof. Anderson</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-a">A-</span>
                                        <div class="course-percentage">89%</div>
                                    </div>
                                </div>
                                <div class="progress custom-course-progress mb-2">
                                    <div class="progress-bar bg-success" 
                                        role="progressbar" 
                                        style="width: 89%" 
                                        aria-valuenow="89" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">11/13 modules completed</small>
                                    <small class="text-success">Next: Research Project</small>
                                </div>
                            </div>

                            <!-- Course Item 6 (Additional for scrolling demo) -->
                            <div class="course-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-3">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Cybersecurity</h6>
                                            <small class="text-muted">CS-420 • Prof. Miller</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-b">B+</span>
                                        <div class="course-percentage">76%</div>
                                    </div>
                                </div>
                                <div class="progress custom-course-progress mb-2">
                                    <div class="progress-bar bg-danger" 
                                        role="progressbar" 
                                        style="width: 76%" 
                                        aria-valuenow="76" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">9/12 modules completed</small>
                                    <small class="text-info">Next: Security Audit</small>
                                </div>
                            </div>
                        </div>

                        <!-- Overall Summary (Fixed at bottom) -->
                        <div class="course-summary mt-3 pt-3 border-top">
                            <div class="row text-center d-flex gap-0">
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-primary fs-6">3.4</div>
                                    <small class="text-muted">Overall GPA</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-success fs-6">81%</div>
                                    <small class="text-muted">Avg Progress</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-warning fs-6">2</div>
                                    <small class="text-muted">Pending Tasks</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Course Progress Card -->
                <div class="col-lg-12 d-grid row-gap-3 py-2">
                    <div class="chart-container course-progress-container m-0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Second Semester Course Progress</h5>
                            <span class="badge bg-primary">4 Active Courses</span>
                        </div>
                        
                        <!-- Scrollable Course List -->
                        <div class="courses-scroll-container">
                            <!-- Course Item 1 -->
                            <div class="course-item mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-3">
                                        <i class="fas fa-laptop-code"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Web Development</h6>
                                        <small class="text-muted">CS-301 • Prof. Johnson</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="grade-badge grade-a">A</span>
                                    <div class="course-percentage">92%</div>
                                </div>
                            </div>
                            <div class="progress custom-course-progress mb-2">
                                <div class="progress-bar bg-success" 
                                    role="progressbar" 
                                    style="width: 92%" 
                                    aria-valuenow="92" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">12/13 modules completed</small>
                                <small class="text-success">Next: Final Project</small>
                            </div>
                        </div>

                        <!-- Course Item 2 -->
                        <div class="course-item mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-3">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Database Systems</h6>
                                        <small class="text-muted">CS-205 • Prof. Smith</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="grade-badge grade-b">B+</span>
                                    <div class="course-percentage">78%</div>
                                </div>
                            </div>
                            <div class="progress custom-course-progress mb-2">
                                <div class="progress-bar bg-primary" 
                                    role="progressbar" 
                                    style="width: 78%" 
                                    aria-valuenow="78" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">9/12 modules completed</small>
                                <small class="text-warning">Due: Assignment 3</small>
                            </div>
                        </div>

                        <!-- Course Item 3 -->
                        <div class="course-item mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Data Analytics</h6>
                                        <small class="text-muted">MATH-401 • Prof. Wilson</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="grade-badge grade-b">B</span>
                                    <div class="course-percentage">85%</div>
                                </div>
                            </div>
                            <div class="progress custom-course-progress mb-2">
                                <div class="progress-bar bg-info" 
                                    role="progressbar" 
                                    style="width: 85%" 
                                    aria-valuenow="85" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">10/12 modules completed</small>
                                <small class="text-info">Next: Midterm Exam</small>
                            </div>
                        </div>

                            <!-- Course Item 4 -->
                            <div class="course-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-3">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Mobile App Design</h6>
                                            <small class="text-muted">DES-302 • Prof. Davis</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-c">B-</span>
                                        <div class="course-percentage">68%</div>
                                    </div>
                                </div>
                                <div class="progress custom-course-progress mb-2">
                                    <div class="progress-bar bg-warning" 
                                        role="progressbar" 
                                        style="width: 68%" 
                                        aria-valuenow="68" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">8/12 modules completed</small>
                                    <small class="text-danger">Overdue: Quiz 4</small>
                                </div>
                            </div>

                            <!-- Course Item 5 (Additional for scrolling demo) -->
                            <div class="course-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-3">
                                            <i class="fas fa-brain"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Machine Learning</h6>
                                            <small class="text-muted">CS-501 • Prof. Anderson</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-a">A-</span>
                                        <div class="course-percentage">89%</div>
                                    </div>
                                </div>
                                <div class="progress custom-course-progress mb-2">
                                    <div class="progress-bar bg-success" 
                                        role="progressbar" 
                                        style="width: 89%" 
                                        aria-valuenow="89" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">11/13 modules completed</small>
                                    <small class="text-success">Next: Research Project</small>
                                </div>
                            </div>

                            <!-- Course Item 6 (Additional for scrolling demo) -->
                            <div class="course-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-3">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Cybersecurity</h6>
                                            <small class="text-muted">CS-420 • Prof. Miller</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-b">B+</span>
                                        <div class="course-percentage">76%</div>
                                    </div>
                                </div>
                                <div class="progress custom-course-progress mb-2">
                                    <div class="progress-bar bg-danger" 
                                        role="progressbar" 
                                        style="width: 76%" 
                                        aria-valuenow="76" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">9/12 modules completed</small>
                                    <small class="text-info">Next: Security Audit</small>
                                </div>
                            </div>
                        </div>

                        <!-- Overall Summary (Fixed at bottom) -->
                        <div class="course-summary mt-3 pt-3 border-top">
                            <div class="row text-center d-flex gap-0">
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-primary fs-6">3.4</div>
                                    <small class="text-muted">Overall GPA</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-success fs-6">81%</div>
                                    <small class="text-muted">Avg Progress</small>
                                </div>
                                <div class="col-4 m-0">
                                    <div class="fw-bold text-warning fs-6">2</div>
                                    <small class="text-muted">Pending Tasks</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
              </div>
          </div>
        </div>
    </main>

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