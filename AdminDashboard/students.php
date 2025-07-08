<?php
require_once 'config.php';

$page_title = 'Students Management';

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                // Insert student
                $stmt = $pdo->prepare("
                    INSERT INTO students (matricule, full_name, email, phone_number, password, school_id, department_name) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['matricule'],
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['phone_number'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['school_id'],
                    $_POST['department_name']
                ]);
                
                $student_id = $pdo->lastInsertId();
                
                // Auto-enroll in department courses if department is selected
                if (!empty($_POST['department_name'])) {
                    $course_stmt = $pdo->prepare("SELECT id FROM courses WHERE department_id = ?");
                    $course_stmt->execute([$_POST['department_name']]);
                    $courses = $course_stmt->fetchAll();
                    
                    foreach ($courses as $course) {
                        $enroll_stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                        $enroll_stmt->execute([$student_id, $course['id']]);
                    }
                }
                
                $message = 'Student created successfully!';
                $message_type = 'success';
                break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE students 
                        SET matricule = ?, full_name = ?, email = ?, phone_number = ?, school_id = ?, department_name = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['matricule'],
                        $_POST['full_name'],
                        $_POST['email'],
                        $_POST['phone_number'],
                        $_POST['school_id'],
                        $_POST['department_name'],
                        $_POST['student_id']
                    ]);
                    $message = 'Student updated successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                    $stmt->execute([$_POST['student_id']]);
                    $message = 'Student deleted successfully!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch students with department names
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(s.full_name LIKE ? OR s.email LIKE ? OR s.matricule LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($department_filter) {
    $where_conditions[] = "s.department_name = ?";
    $params[] = $department_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM students s 
    LEFT JOIN departments d ON s.department_name = d.id 
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_students = $count_stmt->fetch()['total'];
$total_pages = ceil($total_students / $limit);

// Fetch students
$sql = "
    SELECT s.*, d.name as department_name_text
    FROM students s 
    LEFT JOIN departments d ON s.department_name = d.id 
    $where_clause
    ORDER BY s.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch departments for dropdown
$dept_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $dept_stmt->fetchAll();

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
        <h2 class="mb-1">Students Management</h2>
        <p class="text-muted mb-0">Manage student records, enrollments, and information</p>
    </div>
    <div class="btn-group" role="group">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#matriculeModal" onclick="openMatriculeModal()">
            <i class="fas fa-id-card me-2"></i>Add Matricule
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="openCreateModal()">
            <i class="fas fa-plus me-2"></i>Add Student
        </button>
    </div>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Students</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name, email, or matricule..." 
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
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="students.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
            <div class="col-md-2 d-flex align-items-end justify-content-end">
                <span class="text-muted">Total: <?php echo $total_students; ?> students</span>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>Students List
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($students)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Matricule</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                    <small class="text-muted">ID: <?php echo $student['id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($student['matricule']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                        <td>
                            <?php if ($student['department_name_text']): ?>
                                <span class="badge badge-success"><?php echo htmlspecialchars($student['department_name_text']); ?></span>
                            <?php else: ?>
                                <span class="badge badge-warning">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                                        data-bs-toggle="tooltip" title="Edit Student">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="viewStudent(<?php echo $student['id']; ?>)"
                                        data-bs-toggle="tooltip" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')"
                                        data-bs-toggle="tooltip" title="Delete Student">
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
            <nav aria-label="Students pagination">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>">
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
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No students found</h5>
            <p class="text-muted">Try adjusting your search criteria or add a new student.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="openCreateModal()">
                <i class="fas fa-plus me-2"></i>Add First Student
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add Student
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="studentForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="student_id" id="studentId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="matricule" class="form-label">Matricule Number *</label>
                            <input type="text" class="form-control" id="matricule" name="matricule" required>
                        </div>
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone_number" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                        </div>
                        <div class="col-md-6">
                            <label for="school_id" class="form-label">School ID</label>
                            <input type="text" class="form-control" id="school_id" name="school_id">
                        </div>
                        <div class="col-md-6">
                            <label for="department_name" class="form-label">Department</label>
                            <select class="form-select" id="department_name" name="department_name">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12" id="passwordField">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text">Leave blank to keep current password (for updates)</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Save Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Matricule Modal -->
<div class="modal fade" id="matriculeModal" tabindex="-1" aria-labelledby="matriculeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="matriculeModalLabel">
                    <i class="fas fa-id-card me-2"></i>Add Matricule
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="matriculeForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_matricule" class="form-label">Matricule Number *</label>
                        <input type="text" class="form-control" id="new_matricule" name="matricule" required>
                        <div class="form-text">This matricule will be available for student registration</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Matricule
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
                <p>Are you sure you want to delete <strong id="deleteStudentName"></strong>?</p>
                <p class="text-muted">This action cannot be undone and will also remove all related records.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="student_id" id="deleteStudentId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Student
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openMatriculeModal() {
    document.getElementById('matriculeForm').reset();
}

// Handle matricule form submission
document.getElementById('matriculeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('add_matricule.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Matricule added successfully!');
            bootstrap.Modal.getInstance(document.getElementById('matriculeModal')).hide();
            location.reload(); // Refresh to show updated data
        } else {
            showErrorMessage(data.message || 'Error adding matricule');
        }
    })
    .catch(error => {
        showErrorMessage('Error: ' + error.message);
    });
});

function showSuccessMessage(message) {
    // Create and show success alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
}

function showErrorMessage(message) {
    // Create and show error alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
}
function openCreateModal() {
    document.getElementById('studentModalLabel').innerHTML = '<i class="fas fa-user-plus me-2"></i>Add Student';
    document.getElementById('formAction').value = 'create';
    document.getElementById('studentId').value = '';
    document.getElementById('studentForm').reset();
    document.getElementById('password').required = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Save Student';
}

function openEditModal(student) {
    document.getElementById('studentModalLabel').innerHTML = '<i class="fas fa-user-edit me-2"></i>Edit Student';
    document.getElementById('formAction').value = 'update';
    document.getElementById('studentId').value = student.id;
    document.getElementById('matricule').value = student.matricule;
    document.getElementById('full_name').value = student.full_name;
    document.getElementById('email').value = student.email;
    document.getElementById('phone_number').value = student.phone_number;
    document.getElementById('school_id').value = student.school_id;
    document.getElementById('department_name').value = student.department_name || '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Student';
    
    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
}

function deleteStudent(id, name) {
    document.getElementById('deleteStudentId').value = id;
    document.getElementById('deleteStudentName').textContent = name;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function viewStudent(id) {
    // Implement view functionality or redirect to detail page
    window.location.href = `student_detail.php?id=${id}`;
}

// Form validation
document.getElementById('studentForm').addEventListener('submit', function(e) {
    const matricule = document.getElementById('matricule').value;
    const fullName = document.getElementById('full_name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone_number').value;
    const password = document.getElementById('password').value;
    const isCreate = document.getElementById('formAction').value === 'create';
    
    if (!matricule || !fullName || !email || !phone) {
        e.preventDefault();
        showErrorMessage('Please fill in all required fields.');
        return;
    }
    
    if (isCreate && !password) {
        e.preventDefault();
        showErrorMessage('Password is required for new students.');
        return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        showErrorMessage('Please enter a valid email address.');
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

