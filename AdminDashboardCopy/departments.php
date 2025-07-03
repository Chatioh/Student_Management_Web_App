<?php
require_once 'config.php';

$page_title = 'Departments Management';

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("
                        INSERT INTO departments (level_id, name, code, description) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['level_id'],
                        $_POST['name'],
                        $_POST['code'],
                        $_POST['description']
                    ]);
                    $message = 'Department created successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE departments 
                        SET level_id = ?, name = ?, code = ?, description = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['level_id'],
                        $_POST['name'],
                        $_POST['code'],
                        $_POST['description'],
                        $_POST['department_id']
                    ]);
                    $message = 'Department updated successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
                    $stmt->execute([$_POST['department_id']]);
                    $message = 'Department deleted successfully!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch departments with level and school information
$search = isset($_GET['search']) ? $_GET['search'] : '';
$level_filter = isset($_GET['level']) ? $_GET['level'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(d.name LIKE ? OR d.code LIKE ? OR d.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($level_filter) {
    $where_conditions[] = "d.level_id = ?";
    $params[] = $level_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM departments d 
    LEFT JOIN levels l ON d.level_id = l.id 
    LEFT JOIN schools s ON l.school_id = s.id 
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_departments = $count_stmt->fetch()['total'];
$total_pages = ceil($total_departments / $limit);

// Fetch departments
$sql = "
    SELECT d.*, l.name as level_name, s.name as school_name,
           (SELECT COUNT(*) FROM students WHERE department_name = d.id) as student_count,
           (SELECT COUNT(*) FROM courses WHERE department_id = d.id) as course_count
    FROM departments d 
    LEFT JOIN levels l ON d.level_id = l.id 
    LEFT JOIN schools s ON l.school_id = s.id 
    $where_clause
    ORDER BY d.name 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$departments = $stmt->fetchAll();

// Fetch levels for dropdown
$levels_stmt = $pdo->query("
    SELECT l.id, l.name, s.name as school_name 
    FROM levels l 
    LEFT JOIN schools s ON l.school_id = s.id 
    ORDER BY s.name, l.name
");
$levels = $levels_stmt->fetchAll();

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
        <h2 class="mb-1">Departments Management</h2>
        <p class="text-muted mb-0">Manage academic departments, levels, and organizational structure</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="openCreateModal()">
        <i class="fas fa-plus me-2"></i>Add Department
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                    <i class="fas fa-building"></i>
                </div>
                <h4 class="text-primary">
                    <?php
                    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
                    echo $total_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Total Departments</p>
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
                    $students_stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE department_name IS NOT NULL");
                    echo $students_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Students Assigned</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(253, 203, 110, 0.1); color: var(--warning-color);">
                    <i class="fas fa-book"></i>
                </div>
                <h4 class="text-warning">
                    <?php
                    $courses_stmt = $pdo->query("SELECT COUNT(*) as total FROM courses WHERE department_id IS NOT NULL");
                    echo $courses_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Courses Offered</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(116, 185, 255, 0.1); color: var(--info-color);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h4 class="text-info">
                    <?php
                    $levels_count_stmt = $pdo->query("SELECT COUNT(*) as total FROM levels");
                    echo $levels_count_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Academic Levels</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Search Departments</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name, code, or description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label for="level" class="form-label">Academic Level</label>
                <select class="form-select" id="level" name="level">
                    <option value="">All Levels</option>
                    <?php foreach ($levels as $level): ?>
                    <option value="<?php echo $level['id']; ?>" 
                            <?php echo $level_filter == $level['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($level['name'] . ($level['school_name'] ? ' - ' . $level['school_name'] : '')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="departments.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Departments Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Departments List
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($departments)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Department</th>
                        <th>Code</th>
                        <th>Level/School</th>
                        <th>Students</th>
                        <th>Courses</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($dept['name']); ?></div>
                                <?php if ($dept['description']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($dept['description'], 0, 60)) . (strlen($dept['description']) > 60 ? '...' : ''); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($dept['code']); ?></span>
                        </td>
                        <td>
                            <div>
                                <?php if ($dept['level_name']): ?>
                                    <div class="fw-bold"><?php echo htmlspecialchars($dept['level_name']); ?></div>
                                    <?php if ($dept['school_name']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($dept['school_name']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-warning">Not Assigned</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users me-2 text-muted"></i>
                                <span class="fw-bold"><?php echo $dept['student_count']; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-book me-2 text-muted"></i>
                                <span class="fw-bold"><?php echo $dept['course_count']; ?></span>
                            </div>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('M d, Y', strtotime($dept['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($dept)); ?>)"
                                        data-bs-toggle="tooltip" title="Edit Department">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="viewDetails(<?php echo $dept['id']; ?>)"
                                        data-bs-toggle="tooltip" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name']); ?>')"
                                        data-bs-toggle="tooltip" title="Delete Department">
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
            <nav aria-label="Departments pagination">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&level=<?php echo urlencode($level_filter); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&level=<?php echo urlencode($level_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&level=<?php echo urlencode($level_filter); ?>">
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
            <i class="fas fa-building fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No departments found</h5>
            <p class="text-muted">Try adjusting your search criteria or add a new department.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="openCreateModal()">
                <i class="fas fa-plus me-2"></i>Add First Department
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalLabel">
                    <i class="fas fa-building me-2"></i>Add Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="departmentForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="department_id" id="departmentId">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Department Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label">Department Code *</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        <div class="col-12">
                            <label for="level_id" class="form-label">Academic Level *</label>
                            <select class="form-select" id="level_id" name="level_id" required>
                                <option value="">Select Academic Level</option>
                                <?php foreach ($levels as $level): ?>
                                <option value="<?php echo $level['id']; ?>">
                                    <?php echo htmlspecialchars($level['name'] . ($level['school_name'] ? ' - ' . $level['school_name'] : '')); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Brief description of the department..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Save Department
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
                <p>Are you sure you want to delete <strong id="deleteDepartmentName"></strong>?</p>
                <p class="text-muted">This action cannot be undone and will also remove all related courses and student assignments.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="department_id" id="deleteDepartmentId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Department
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('departmentModalLabel').innerHTML = '<i class="fas fa-building me-2"></i>Add Department';
    document.getElementById('formAction').value = 'create';
    document.getElementById('departmentId').value = '';
    document.getElementById('departmentForm').reset();
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Save Department';
}

function openEditModal(department) {
    document.getElementById('departmentModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Department';
    document.getElementById('formAction').value = 'update';
    document.getElementById('departmentId').value = department.id;
    document.getElementById('name').value = department.name;
    document.getElementById('code').value = department.code;
    document.getElementById('level_id').value = department.level_id || '';
    document.getElementById('description').value = department.description || '';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Department';
    
    const modal = new bootstrap.Modal(document.getElementById('departmentModal'));
    modal.show();
}

function deleteDepartment(id, name) {
    document.getElementById('deleteDepartmentId').value = id;
    document.getElementById('deleteDepartmentName').textContent = name;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function viewDetails(departmentId) {
    // Redirect to department details page or show details modal
    window.location.href = `department_details.php?id=${departmentId}`;
}

// Form validation
document.getElementById('departmentForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value;
    const code = document.getElementById('code').value;
    const levelId = document.getElementById('level_id').value;
    
    if (!name || !code || !levelId) {
        e.preventDefault();
        showErrorMessage('Please fill in all required fields.');
        return;
    }
    
    // Code validation (alphanumeric)
    const codeRegex = /^[A-Za-z0-9]+$/;
    if (!codeRegex.test(code)) {
        e.preventDefault();
        showErrorMessage('Department code should contain only letters and numbers.');
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

