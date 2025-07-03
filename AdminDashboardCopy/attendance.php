<?php
require_once 'config.php';

$page_title = 'Attendance Management';

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'mark_attendance':
                    // Mark attendance for multiple students
                    $date = $_POST['attendance_date'];
                    $course_id = $_POST['course_id'];
                    
                    // Get all enrollments for the course
                    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = ?");
                    $stmt->execute([$course_id]);
                    $enrollments = $stmt->fetchAll();
                    
                    foreach ($enrollments as $enrollment) {
                        $enrollment_id = $enrollment['id'];
                        $status = isset($_POST['attendance'][$enrollment_id]) ? 'Present' : 'Absent';
                        
                        // Check if attendance already exists for this date
                        $check_stmt = $pdo->prepare("
                            SELECT id FROM attendance 
                            WHERE enrollment_id = ? AND date = ?
                        ");
                        $check_stmt->execute([$enrollment_id, $date]);
                        
                        if ($check_stmt->fetch()) {
                            // Update existing attendance
                            $update_stmt = $pdo->prepare("
                                UPDATE attendance 
                                SET status = ? 
                                WHERE enrollment_id = ? AND date = ?
                            ");
                            $update_stmt->execute([$status, $enrollment_id, $date]);
                        } else {
                            // Insert new attendance
                            $insert_stmt = $pdo->prepare("
                                INSERT INTO attendance (enrollment_id, date, status) 
                                VALUES (?, ?, ?)
                            ");
                            $insert_stmt->execute([$enrollment_id, $date, $status]);
                        }
                    }
                    
                    $message = 'Attendance marked successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'update_attendance':
                    $stmt = $pdo->prepare("
                        UPDATE attendance 
                        SET status = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$_POST['status'], $_POST['attendance_id']]);
                    $message = 'Attendance updated successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete_attendance':
                    $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
                    $stmt->execute([$_POST['attendance_id']]);
                    $message = 'Attendance record deleted successfully!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch courses for dropdown
$courses_stmt = $pdo->query("
    SELECT c.id, c.name, c.code, d.name as department_name 
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.id 
    ORDER BY c.name
");
$courses = $courses_stmt->fetchAll();

// Fetch attendance records with filters
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if ($course_filter) {
    $where_conditions[] = "c.id = ?";
    $params[] = $course_filter;
}

if ($date_filter) {
    $where_conditions[] = "a.date = ?";
    $params[] = $date_filter;
}

if ($status_filter) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM attendance a
    JOIN enrollments e ON a.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Fetch attendance records
$sql = "
    SELECT 
        a.id,
        a.date,
        a.status,
        s.full_name as student_name,
        s.matricule,
        c.name as course_name,
        c.code as course_code
    FROM attendance a
    JOIN enrollments e ON a.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    $where_clause
    ORDER BY a.date DESC, c.name, s.full_name
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

include 'includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Attendance Management</h2>
        <p class="text-muted mb-0">Track and manage student attendance records</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
        <i class="fas fa-calendar-check me-2"></i>Mark Attendance
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
                    <i class="fas fa-user-check"></i>
                </div>
                <h4 class="text-success">
                    <?php
                    $present_stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance WHERE status = 'Present' AND date = CURDATE()");
                    echo $present_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Present Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(231, 76, 60, 0.1); color: var(--danger-color);">
                    <i class="fas fa-user-times"></i>
                </div>
                <h4 class="text-danger">
                    <?php
                    $absent_stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance WHERE status = 'Absent' AND date = CURDATE()");
                    echo $absent_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Absent Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                    <i class="fas fa-percentage"></i>
                </div>
                <h4 class="text-primary">
                    <?php
                    $rate_stmt = $pdo->query("
                        SELECT 
                            COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
                            COUNT(*) as total
                        FROM attendance 
                        WHERE date = CURDATE()
                    ");
                    $rate_data = $rate_stmt->fetch();
                    $rate = $rate_data['total'] > 0 ? round(($rate_data['present'] / $rate_data['total']) * 100, 1) : 0;
                    echo $rate;
                    ?>%
                </h4>
                <p class="text-muted mb-0">Attendance Rate</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(253, 203, 110, 0.1); color: var(--warning-color);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h4 class="text-warning">
                    <?php
                    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance WHERE date = CURDATE()");
                    echo $total_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Total Records</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="course" class="form-label">Course</label>
                <select class="form-select" id="course" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>" 
                            <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['name'] . ' (' . $course['code'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" 
                       value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="Present" <?php echo $status_filter === 'Present' ? 'selected' : ''; ?>>Present</option>
                    <option value="Absent" <?php echo $status_filter === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="attendance.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
            <div class="col-md-2 d-flex align-items-end justify-content-end">
                <span class="text-muted">Total: <?php echo $total_records; ?> records</span>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Records Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Attendance Records
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($attendance_records)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Matricule</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo date('M d, Y', strtotime($record['date'])); ?></div>
                            <small class="text-muted"><?php echo date('l', strtotime($record['date'])); ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($record['student_name'], 0, 1)); ?>
                                </div>
                                <?php echo htmlspecialchars($record['student_name']); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($record['matricule']); ?></span>
                        </td>
                        <td>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($record['course_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($record['course_code']); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $record['status'] === 'Present' ? 'success' : 'danger'; ?>">
                                <i class="fas fa-<?php echo $record['status'] === 'Present' ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo $record['status']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editAttendance(<?php echo $record['id']; ?>, '<?php echo $record['status']; ?>')"
                                        data-bs-toggle="tooltip" title="Edit Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteAttendance(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['student_name']); ?>', '<?php echo $record['date']; ?>')"
                                        data-bs-toggle="tooltip" title="Delete Record">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Attendance pagination">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&course=<?php echo urlencode($course_filter); ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&course=<?php echo urlencode($course_filter); ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&course=<?php echo urlencode($course_filter); ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No attendance records found</h5>
            <p class="text-muted">Try adjusting your filters or mark attendance for a course.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                <i class="fas fa-calendar-check me-2"></i>Mark Attendance
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Mark Attendance Modal -->
<div class="modal fade" id="markAttendanceModal" tabindex="-1" aria-labelledby="markAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markAttendanceModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="markAttendanceForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="mark_attendance">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="attendance_date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="course_id" class="form-label">Course *</label>
                            <select class="form-select" id="course_id" name="course_id" required onchange="loadStudents()">
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['name'] . ' (' . $course['code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="studentsContainer" style="display: none;">
                        <h6 class="mb-3">
                            <i class="fas fa-users me-2"></i>Students Enrolled
                            <button type="button" class="btn btn-sm btn-outline-success ms-2" onclick="markAllPresent()">
                                <i class="fas fa-check-double me-1"></i>Mark All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-1" onclick="markAllAbsent()">
                                <i class="fas fa-times me-1"></i>Mark All Absent
                            </button>
                        </h6>
                        <div id="studentsList" class="row g-2">
                            <!-- Students will be loaded here via AJAX -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitAttendance" disabled>
                        <i class="fas fa-save me-2"></i>Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-labelledby="editAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAttendanceModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Attendance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_attendance">
                    <input type="hidden" name="attendance_id" id="editAttendanceId">
                    
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAttendanceModal" tabindex="-1" aria-labelledby="deleteAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteAttendanceModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the attendance record for:</p>
                <p><strong id="deleteAttendanceStudent"></strong> on <strong id="deleteAttendanceDate"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_attendance">
                    <input type="hidden" name="attendance_id" id="deleteAttendanceId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function loadStudents() {
    const courseId = document.getElementById('course_id').value;
    const date = document.getElementById('attendance_date').value;
    
    if (!courseId || !date) {
        document.getElementById('studentsContainer').style.display = 'none';
        document.getElementById('submitAttendance').disabled = true;
        return;
    }
    
    // Show loading
    document.getElementById('studentsList').innerHTML = '<div class="text-center"><div class="loading"></div> Loading students...</div>';
    document.getElementById('studentsContainer').style.display = 'block';
    
    // Fetch students enrolled in the course
    fetch(`get_enrolled_students.php?course_id=${courseId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStudents(data.students);
                document.getElementById('submitAttendance').disabled = false;
            } else {
                document.getElementById('studentsList').innerHTML = '<div class="alert alert-warning">No students enrolled in this course.</div>';
                document.getElementById('submitAttendance').disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('studentsList').innerHTML = '<div class="alert alert-danger">Error loading students.</div>';
            document.getElementById('submitAttendance').disabled = true;
        });
}

function displayStudents(students) {
    let html = '';
    students.forEach(student => {
        html += `
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                ${student.full_name.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">${student.full_name}</div>
                                <small class="text-muted">${student.matricule}</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       name="attendance[${student.enrollment_id}]" 
                                       id="student_${student.enrollment_id}"
                                       ${student.current_status === 'Present' ? 'checked' : ''}>
                                <label class="form-check-label" for="student_${student.enrollment_id}">
                                    Present
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    document.getElementById('studentsList').innerHTML = html;
}

function markAllPresent() {
    const checkboxes = document.querySelectorAll('#studentsList input[type="checkbox"]');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function markAllAbsent() {
    const checkboxes = document.querySelectorAll('#studentsList input[type="checkbox"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}

function editAttendance(id, status) {
    document.getElementById('editAttendanceId').value = id;
    document.getElementById('editStatus').value = status;
    const modal = new bootstrap.Modal(document.getElementById('editAttendanceModal'));
    modal.show();
}

function deleteAttendance(id, studentName, date) {
    document.getElementById('deleteAttendanceId').value = id;
    document.getElementById('deleteAttendanceStudent').textContent = studentName;
    document.getElementById('deleteAttendanceDate').textContent = new Date(date).toLocaleDateString();
    const modal = new bootstrap.Modal(document.getElementById('deleteAttendanceModal'));
    modal.show();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>

