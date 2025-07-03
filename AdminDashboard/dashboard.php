<?php
require_once 'config.php';

$page_title = 'Dashboard';

// Fetch dashboard statistics
try {
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $total_students = $stmt->fetch()['total'];
    
    // Total courses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
    $total_courses = $stmt->fetch()['total'];
    
    // Total departments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
    $total_departments = $stmt->fetch()['total'];
    
    // Total enrollments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM enrollments");
    $total_enrollments = $stmt->fetch()['total'];
    
    // Recent students (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_students = $stmt->fetch()['total'];
    
    // Attendance rate for current month
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
            COUNT(*) as total
        FROM attendance 
        WHERE MONTH(date) = MONTH(CURRENT_DATE()) 
        AND YEAR(date) = YEAR(CURRENT_DATE())
    ");
    $attendance_data = $stmt->fetch();
    $attendance_rate = $attendance_data['total'] > 0 ? round(($attendance_data['present'] / $attendance_data['total']) * 100, 1) : 0;
    
    // Recent enrollments
    $stmt = $pdo->query("
        SELECT 
            s.full_name,
            c.name as course_name,
            e.enrolled_at
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        JOIN courses c ON e.course_id = c.id
        ORDER BY e.enrolled_at DESC
        LIMIT 5
    ");
    $recent_enrollments = $stmt->fetchAll();
    
    // Department-wise student count
    $stmt = $pdo->query("
        SELECT 
            d.name as department_name,
            COUNT(s.id) as student_count
        FROM departments d
        LEFT JOIN students s ON d.id = s.department_name
        GROUP BY d.id, d.name
        ORDER BY student_count DESC
        LIMIT 5
    ");
    $department_stats = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}

include 'includes/header.php';
?>

<!-- Welcome Card -->
<div class="welcome-card mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="mb-3 text-white">Welcome to Admin Dashboard! 👋</h2>
            <p class="mb-3 text-white opacity-75">Manage your student management system efficiently with comprehensive tools and insights.</p>
            <button class="btn btn-light" onclick="window.location.href='students.php'">
                <i class="fas fa-users me-2"></i>Manage Students
            </button>
        </div>
        <div class="col-md-4">
            <div class="welcome-illustration">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
</div>

<style>
.welcome-card {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 16px;
    padding: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.welcome-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: translate(50%, -50%);
}

.welcome-illustration {
    position: absolute;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

@media (max-width: 768px) {
    .welcome-illustration {
        position: static;
        transform: none;
        margin: 1rem auto 0;
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
}
</style>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_students); ?></div>
        <div class="stat-label">Total Students</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> +<?php echo $recent_students; ?> this week
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
            <i class="fas fa-book"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_courses); ?></div>
        <div class="stat-label">Total Courses</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> Active courses
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(253, 203, 110, 0.1); color: var(--warning-color);">
            <i class="fas fa-building"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_departments); ?></div>
        <div class="stat-label">Departments</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> All departments
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(116, 185, 255, 0.1); color: var(--info-color);">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-value"><?php echo $attendance_rate; ?>%</div>
        <div class="stat-label">Attendance Rate</div>
        <div class="stat-change <?php echo $attendance_rate >= 80 ? 'positive' : 'negative'; ?>">
            <i class="fas fa-<?php echo $attendance_rate >= 80 ? 'arrow-up' : 'arrow-down'; ?>"></i> This month
        </div>
    </div>
</div>

<!-- Charts and Tables Row -->
<div class="row gy-4">
    <div class="col-lg-8">
        <div class="row gy-4">
            <!-- Recent Enrollments -->
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>Recent Enrollments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_enrollments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Enrolled Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_enrollments as $enrollment): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                                        <?php echo strtoupper(substr($enrollment['full_name'], 0, 1)); ?>
                                                    </div>
                                                    <?php echo htmlspecialchars($enrollment['full_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($enrollment['enrolled_at'])); ?></td>
                                            <td><span class="badge badge-success">Active</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent enrollments found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="students.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                                    <span>Add Student</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="courses.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                    <i class="fas fa-book-open fa-2x mb-2"></i>
                                    <span>Add Course</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="attendance.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <span>Mark Attendance</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="exam_results.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <span>Add Results</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="row">
            <!-- Department Statistics -->
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Department Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($department_stats)): ?>
                            <?php foreach ($department_stats as $dept): ?>
                            <div class="progress-item mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($dept['department_name']); ?></h6>
                                        <small class="text-muted"><?php echo $dept['student_count']; ?> students</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary">
                                            <?php echo $total_students > 0 ? round(($dept['student_count'] / $total_students) * 100, 1) : 0; ?>%
                                        </div>
                                    </div>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" 
                                        role="progressbar" 
                                        style="width: <?php echo $total_students > 0 ? ($dept['student_count'] / $total_students) * 100 : 0; ?>%" 
                                        aria-valuenow="<?php echo $dept['student_count']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $total_students; ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No department data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>                    
    </div>
</div>

<style>
.progress-item {
    padding: 0.5rem 0;
}

.progress-item:last-child {
    margin-bottom: 0 !important;
}

.quick-action-btn {
    transition: all 0.3s ease;
    border: 2px solid;
    background: transparent;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}
</style>

<?php include 'includes/footer.php'; ?>

