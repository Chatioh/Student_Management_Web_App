<?php
require_once 'config.php';
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../admin_login.php");
    exit;
}

$page_title = 'Courses Management';

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("
                        INSERT INTO courses (department_id, name, code, semester, credit_hours) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['department_id'],
                        $_POST['name'],
                        $_POST['code'],
                        $_POST['semester'],
                        $_POST['credit_hours']
                    ]);
                    $message = 'Course created successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE courses 
                        SET department_id = ?, name = ?, code = ?, semester = ?, credit_hours = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['department_id'],
                        $_POST['name'],
                        $_POST['code'],
                        $_POST['semester'],
                        $_POST['credit_hours'],
                        $_POST['course_id']
                    ]);
                    $message = 'Course updated successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                    $stmt->execute([$_POST['course_id']]);
                    $message = 'Course deleted successfully!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch courses with department names
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.name LIKE ? OR c.code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($department_filter) {
    $where_conditions[] = "c.department_id = ?";
    $params[] = $department_filter;
}

if ($semester_filter) {
    $where_conditions[] = "c.semester = ?";
    $params[] = $semester_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.id 
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_courses = $count_stmt->fetch()['total'];
$total_pages = ceil($total_courses / $limit);

// Fetch courses
$sql = "
    SELECT c.*, d.name as department_name,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollment_count
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.id 
    $where_clause
    ORDER BY c.name 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Fetch departments for dropdown
$dept_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $dept_stmt->fetchAll();

// Get unique semesters
$semester_stmt = $pdo->query("SELECT DISTINCT semester FROM courses WHERE semester IS NOT NULL ORDER BY semester");
$semesters = $semester_stmt->fetchAll();

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
        <h2 class="mb-1">Courses Management</h2>
        <p class="text-muted mb-0">Manage course catalog, enrollments, and academic programs</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal" onclick="openCreateModal()">
        <i class="fas fa-plus me-2"></i>Add Course
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                    <i class="fas fa-book"></i>
                </div>
                <h4 class="text-primary">
                    <?php
                    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
                    echo $total_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Total Courses</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
                    <i class="fas fa-users"></i>
                </div>
                <h4 class="text-success">
                    <?php
                    $enrolled_stmt = $pdo->query("SELECT COUNT(*) as total FROM enrollments");
                    echo $enrolled_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Total Enrollments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(253, 203, 110, 0.1); color: var(--warning-color);">
                    <i class="fas fa-building"></i>
                </div>
                <h4 class="text-warning">
                    <?php
                    $dept_count_stmt = $pdo->query("SELECT COUNT(DISTINCT department_id) as total FROM courses WHERE department_id IS NOT NULL");
                    echo $dept_count_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Active Departments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(116, 185, 255, 0.1); color: var(--info-color);">
                    <i class="fas fa-calculator"></i>
                </div>
                <h4 class="text-info">
                    <?php
                    $credits_stmt = $pdo->query("SELECT SUM(credit_hours) as total FROM courses WHERE credit_hours IS NOT NULL");
                    echo $credits_stmt->fetch()['total'] ?: 0;
                    ?>
                </h4>
                <p class="text-muted mb-0">Total Credit Hours</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Courses</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name or code..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label for="department" class="form-label">Department</label>
                <select class="form-select" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" 
                            <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="semester" class="form-label">Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem['semester']; ?>" 
                            <?php echo $semester_filter == $sem['semester'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sem['semester']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="courses.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>
<div class="table-responsive-sm">
    <!-- Courses Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Courses List
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($courses)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Course</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Credit Hours</th>
                            <th>Enrollments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($course['name']); ?></div>
                                    <small class="text-muted">ID: <?php echo $course['id']; ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($course['code']); ?></span>
                            </td>
                            <td>
                                <?php if ($course['department_name']): ?>
                                    <span class="badge badge-success"><?php echo htmlspecialchars($course['department_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($course['semester']): ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($course['semester']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock me-2 text-muted"></i>
                                    <?php echo $course['credit_hours'] ?: 'N/A'; ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users me-2 text-muted"></i>
                                    <span class="fw-bold"><?php echo $course['enrollment_count']; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($course)); ?>)"
                                            data-bs-toggle="tooltip" title="Edit Course">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="viewEnrollments(<?php echo $course['id']; ?>)"
                                            data-bs-toggle="tooltip" title="View Enrollments">
                                        <i class="fas fa-users"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['name']); ?>')"
                                            data-bs-toggle="tooltip" title="Delete Course">
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
                <nav aria-label="Courses pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>">
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
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No courses found</h5>
                <p class="text-muted">Try adjusting your search criteria or add a new course.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal" onclick="openCreateModal()">
                    <i class="fas fa-plus me-2"></i>Add First Course
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>


<!-- Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="courseModalLabel">
                    <i class="fas fa-book me-2"></i>Add Course
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="courseForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="course_id" id="courseId">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Course Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label">Course Code *</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">Department *</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="semester" class="form-label">Semester</label>
                            <input type="text" class="form-control" id="semester" name="semester" 
                                   placeholder="e.g., Fall 2024">
                        </div>
                        <div class="col-md-3">
                            <label for="credit_hours" class="form-label">Credit Hours</label>
                            <input type="number" class="form-control" id="credit_hours" name="credit_hours" 
                                   min="1" max="10" step="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Save Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteCourseName"></strong>?</p>
                <p class="text-muted">This action cannot be undone and will also remove all related enrollments and attendance records.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="course_id" id="deleteCourseId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Course
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('courseModalLabel').innerHTML = '<i class="fas fa-book me-2"></i>Add Course';
    document.getElementById('formAction').value = 'create';
    document.getElementById('courseId').value = '';
    document.getElementById('courseForm').reset();
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Save Course';
}

function openEditModal(course) {
    document.getElementById('courseModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Course';
    document.getElementById('formAction').value = 'update';
    document.getElementById('courseId').value = course.id;
    document.getElementById('name').value = course.name;
    document.getElementById('code').value = course.code;
    document.getElementById('department_id').value = course.department_id || '';
    document.getElementById('semester').value = course.semester || '';
    document.getElementById('credit_hours').value = course.credit_hours || '';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Course';
    
    const modal = new bootstrap.Modal(document.getElementById('courseModal'));
    modal.show();
}

function deleteCourse(id, name) {
    document.getElementById('deleteCourseId').value = id;
    document.getElementById('deleteCourseName').textContent = name;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function viewEnrollments(courseId) {
    // Redirect to enrollments page with course filter
    window.location.href = `enrollments.php?course=${courseId}`;
}

// Form validation
document.getElementById('courseForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value;
    const code = document.getElementById('code').value;
    const departmentId = document.getElementById('department_id').value;
    
    if (!name || !code || !departmentId) {
        e.preventDefault();
        showErrorMessage('Please fill in all required fields.');
        return;
    }
    
    // Code validation (alphanumeric)
    const codeRegex = /^[A-Za-z0-9]+$/;
    if (!codeRegex.test(code)) {
        e.preventDefault();
        showErrorMessage('Course code should contain only letters and numbers.');
        return;
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>

