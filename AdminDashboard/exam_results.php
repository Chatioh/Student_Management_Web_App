<?php
require_once 'config.php';

$page_title = 'Exam Results Management';

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    // Calculate total score
                    $ca_mark = floatval($_POST['ca_mark']);
                    $exam_mark = floatval($_POST['exam_mark']);
                    $total_score = $ca_mark + $exam_mark;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO exam_results (enrollment_id, exam_type, ca_mark, exam_mark, total_score) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['enrollment_id'],
                        $_POST['exam_type'],
                        $ca_mark,
                        $exam_mark,
                        $total_score
                    ]);
                    $message = 'Exam result added successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'update':
                    // Calculate total score
                    $ca_mark = floatval($_POST['ca_mark']);
                    $exam_mark = floatval($_POST['exam_mark']);
                    $total_score = $ca_mark + $exam_mark;
                    
                    $stmt = $pdo->prepare("
                        UPDATE exam_results 
                        SET enrollment_id = ?, exam_type = ?, ca_mark = ?, exam_mark = ?, total_score = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['enrollment_id'],
                        $_POST['exam_type'],
                        $ca_mark,
                        $exam_mark,
                        $total_score,
                        $_POST['result_id']
                    ]);
                    $message = 'Exam result updated successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM exam_results WHERE id = ?");
                    $stmt->execute([$_POST['result_id']]);
                    $message = 'Exam result deleted successfully!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch exam results with student and course information
$search = isset($_GET['search']) ? $_GET['search'] : '';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';
$exam_type_filter = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(s.full_name LIKE ? OR s.matricule LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($course_filter) {
    $where_conditions[] = "c.id = ?";
    $params[] = $course_filter;
}

if ($exam_type_filter) {
    $where_conditions[] = "er.exam_type = ?";
    $params[] = $exam_type_filter;
}

if ($grade_filter) {
    switch ($grade_filter) {
        case 'A':
            $where_conditions[] = "er.total_score >= 80";
            break;
        case 'B':
            $where_conditions[] = "er.total_score >= 70 AND er.total_score < 80";
            break;
        case 'C':
            $where_conditions[] = "er.total_score >= 60 AND er.total_score < 70";
            break;
        case 'D':
            $where_conditions[] = "er.total_score >= 50 AND er.total_score < 60";
            break;
        case 'F':
            $where_conditions[] = "er.total_score < 50";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM exam_results er
    JOIN enrollments e ON er.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_results = $count_stmt->fetch()['total'];
$total_pages = ceil($total_results / $limit);

// Fetch exam results
$sql = "
    SELECT 
        er.*,
        s.full_name as student_name,
        s.matricule,
        c.name as course_name,
        c.code as course_code,
        CASE 
            WHEN er.total_score >= 80 THEN 'A'
            WHEN er.total_score >= 70 THEN 'B'
            WHEN er.total_score >= 60 THEN 'C'
            WHEN er.total_score >= 50 THEN 'D'
            ELSE 'F'
        END as grade
    FROM exam_results er
    JOIN enrollments e ON er.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    $where_clause
    ORDER BY er.graded_at DESC, c.name, s.full_name
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$exam_results = $stmt->fetchAll();

// Fetch courses for dropdown
$courses_stmt = $pdo->query("SELECT id, name, code FROM courses ORDER BY name");
$courses = $courses_stmt->fetchAll();

// Fetch enrollments for dropdown (when adding new results)
$enrollments_stmt = $pdo->query("
    SELECT e.id, s.full_name, s.matricule, c.name as course_name, c.code as course_code
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY c.name, s.full_name
");
$enrollments = $enrollments_stmt->fetchAll();

// Get unique exam types
$exam_types_stmt = $pdo->query("SELECT DISTINCT exam_type FROM exam_results WHERE exam_type IS NOT NULL ORDER BY exam_type");
$exam_types = $exam_types_stmt->fetchAll();

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
        <h2 class="mb-1">Exam Results Management</h2>
        <p class="text-muted mb-0">Manage student exam scores, grades, and academic performance</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#resultModal" onclick="openCreateModal()">
        <i class="fas fa-plus me-2"></i>Add Result
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4 class="text-primary">
                    <?php
                    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM exam_results");
                    echo $total_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Total Results</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
                    <i class="fas fa-trophy"></i>
                </div>
                <h4 class="text-success">
                    <?php
                    $pass_stmt = $pdo->query("SELECT COUNT(*) as total FROM exam_results WHERE total_score >= 50");
                    echo $pass_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Passed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(231, 76, 60, 0.1); color: var(--danger-color);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h4 class="text-danger">
                    <?php
                    $fail_stmt = $pdo->query("SELECT COUNT(*) as total FROM exam_results WHERE total_score < 50");
                    echo $fail_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Failed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(253, 203, 110, 0.1); color: var(--warning-color);">
                    <i class="fas fa-percentage"></i>
                </div>
                <h4 class="text-warning">
                    <?php
                    $avg_stmt = $pdo->query("SELECT AVG(total_score) as average FROM exam_results");
                    $average = $avg_stmt->fetch()['average'];
                    echo $average ? round($average, 1) . '%' : '0%';
                    ?>
                </h4>
                <p class="text-muted mb-0">Average Score</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search Students</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name or matricule..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label for="course" class="form-label">Course</label>
                <select class="form-select" id="course" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>" 
                            <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['code']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="exam_type" class="form-label">Exam Type</label>
                <select class="form-select" id="exam_type" name="exam_type">
                    <option value="">All Types</option>
                    <?php foreach ($exam_types as $type): ?>
                    <option value="<?php echo $type['exam_type']; ?>" 
                            <?php echo $exam_type_filter === $type['exam_type'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['exam_type']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="grade" class="form-label">Grade</label>
                <select class="form-select" id="grade" name="grade">
                    <option value="">All Grades</option>
                    <option value="A" <?php echo $grade_filter === 'A' ? 'selected' : ''; ?>>A (80-100)</option>
                    <option value="B" <?php echo $grade_filter === 'B' ? 'selected' : ''; ?>>B (70-79)</option>
                    <option value="C" <?php echo $grade_filter === 'C' ? 'selected' : ''; ?>>C (60-69)</option>
                    <option value="D" <?php echo $grade_filter === 'D' ? 'selected' : ''; ?>>D (50-59)</option>
                    <option value="F" <?php echo $grade_filter === 'F' ? 'selected' : ''; ?>>F (0-49)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="exam_results.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Exam Results Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Exam Results
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($exam_results)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Exam Type</th>
                        <th>CA Mark</th>
                        <th>Exam Mark</th>
                        <th>Total Score</th>
                        <th>Grade</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exam_results as $result): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($result['student_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($result['student_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($result['matricule']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($result['course_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($result['course_code']); ?></small>
                            </div>
                        </td>
                        <td>
                            <?php if ($result['exam_type']): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($result['exam_type']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-bold"><?php echo number_format($result['ca_mark'], 1); ?></span>
                        </td>
                        <td>
                            <span class="fw-bold"><?php echo number_format($result['exam_mark'], 1); ?></span>
                        </td>
                        <td>
                            <span class="fw-bold fs-6"><?php echo number_format($result['total_score'], 1); ?></span>
                        </td>
                        <td>
                            <?php
                            $grade_class = '';
                            switch ($result['grade']) {
                                case 'A': $grade_class = 'success'; break;
                                case 'B': $grade_class = 'primary'; break;
                                case 'C': $grade_class = 'warning'; break;
                                case 'D': $grade_class = 'info'; break;
                                case 'F': $grade_class = 'danger'; break;
                            }
                            ?>
                            <span class="badge badge-<?php echo $grade_class; ?> fs-6">
                                <?php echo $result['grade']; ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('M d, Y', strtotime($result['graded_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($result)); ?>)"
                                        data-bs-toggle="tooltip" title="Edit Result">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteResult(<?php echo $result['id']; ?>, '<?php echo htmlspecialchars($result['student_name']); ?>', '<?php echo htmlspecialchars($result['course_name']); ?>')"
                                        data-bs-toggle="tooltip" title="Delete Result">
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
            <nav aria-label="Results pagination">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course_filter); ?>&exam_type=<?php echo urlencode($exam_type_filter); ?>&grade=<?php echo urlencode($grade_filter); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course_filter); ?>&exam_type=<?php echo urlencode($exam_type_filter); ?>&grade=<?php echo urlencode($grade_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course_filter); ?>&exam_type=<?php echo urlencode($exam_type_filter); ?>&grade=<?php echo urlencode($grade_filter); ?>">
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
            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No exam results found</h5>
            <p class="text-muted">Try adjusting your filters or add a new exam result.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#resultModal" onclick="openCreateModal()">
                <i class="fas fa-plus me-2"></i>Add First Result
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalLabel">
                    <i class="fas fa-plus me-2"></i>Add Exam Result
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resultForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="result_id" id="resultId">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="enrollment_id" class="form-label">Student Enrollment *</label>
                            <select class="form-select" id="enrollment_id" name="enrollment_id" required>
                                <option value="">Select Student and Course</option>
                                <?php foreach ($enrollments as $enrollment): ?>
                                <option value="<?php echo $enrollment['id']; ?>">
                                    <?php echo htmlspecialchars($enrollment['full_name'] . ' (' . $enrollment['matricule'] . ') - ' . $enrollment['course_name'] . ' (' . $enrollment['course_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="exam_type" class="form-label">Exam Type</label>
                            <input type="text" class="form-control" id="exam_type" name="exam_type" 
                                   placeholder="e.g., Midterm, Final, Quiz">
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Score Breakdown</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="ca_mark" class="form-label">CA Mark (0-40) *</label>
                            <input type="number" class="form-control" id="ca_mark" name="ca_mark" 
                                   min="0" max="40" step="0.1" required onchange="calculateTotal()">
                        </div>
                        <div class="col-md-6">
                            <label for="exam_mark" class="form-label">Exam Mark (0-60) *</label>
                            <input type="number" class="form-control" id="exam_mark" name="exam_mark" 
                                   min="0" max="60" step="0.1" required onchange="calculateTotal()">
                        </div>
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Total Score</label>
                                            <div class="fs-4 fw-bold text-primary" id="totalScore">0.0</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Grade</label>
                                            <div class="fs-4 fw-bold" id="gradeDisplay">-</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Status</label>
                                            <div class="fs-6 fw-bold" id="statusDisplay">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Save Result
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
                <p>Are you sure you want to delete the exam result for:</p>
                <p><strong id="deleteStudentName"></strong> in <strong id="deleteCourseName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="result_id" id="deleteResultId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Result
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('resultModalLabel').innerHTML = '<i class="fas fa-plus me-2"></i>Add Exam Result';
    document.getElementById('formAction').value = 'create';
    document.getElementById('resultId').value = '';
    document.getElementById('resultForm').reset();
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Save Result';
    calculateTotal();
}

function openEditModal(result) {
    document.getElementById('resultModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Exam Result';
    document.getElementById('formAction').value = 'update';
    document.getElementById('resultId').value = result.id;
    document.getElementById('enrollment_id').value = result.enrollment_id;
    document.getElementById('exam_type').value = result.exam_type || '';
    document.getElementById('ca_mark').value = result.ca_mark;
    document.getElementById('exam_mark').value = result.exam_mark;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Result';
    
    calculateTotal();
    
    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
    modal.show();
}

function deleteResult(id, studentName, courseName) {
    document.getElementById('deleteResultId').value = id;
    document.getElementById('deleteStudentName').textContent = studentName;
    document.getElementById('deleteCourseName').textContent = courseName;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function calculateTotal() {
    const caMarkInput = document.getElementById('ca_mark');
    const examMarkInput = document.getElementById('exam_mark');
    const totalScoreDisplay = document.getElementById('totalScore');
    const gradeDisplay = document.getElementById('gradeDisplay');
    const statusDisplay = document.getElementById('statusDisplay');
    
    const caMark = parseFloat(caMarkInput.value) || 0;
    const examMark = parseFloat(examMarkInput.value) || 0;
    const totalScore = caMark + examMark;
    
    totalScoreDisplay.textContent = totalScore.toFixed(1);
    
    let grade = '';
    let gradeClass = '';
    let status = '';
    let statusClass = '';
    
    if (totalScore >= 80) {
        grade = 'A';
        gradeClass = 'text-success';
        status = 'Excellent';
        statusClass = 'text-success';
    } else if (totalScore >= 70) {
        grade = 'B';
        gradeClass = 'text-primary';
        status = 'Good';
        statusClass = 'text-primary';
    } else if (totalScore >= 60) {
        grade = 'C';
        gradeClass = 'text-warning';
        status = 'Satisfactory';
        statusClass = 'text-warning';
    } else if (totalScore >= 50) {
        grade = 'D';
        gradeClass = 'text-info';
        status = 'Pass';
        statusClass = 'text-info';
    } else {
        grade = 'F';
        gradeClass = 'text-danger';
        status = 'Fail';
        statusClass = 'text-danger';
    }
    
    gradeDisplay.textContent = grade;
    gradeDisplay.className = `fs-4 fw-bold ${gradeClass}`;
    statusDisplay.textContent = status;
    statusDisplay.className = `fs-6 fw-bold ${statusClass}`;
}

// Form validation
document.getElementById('resultForm').addEventListener('submit', function(e) {
    const enrollmentId = document.getElementById('enrollment_id').value;
    const caMark = parseFloat(document.getElementById('ca_mark').value);
    const examMark = parseFloat(document.getElementById('exam_mark').value);
    
    if (!enrollmentId) {
        e.preventDefault();
        showErrorMessage('Please select a student enrollment.');
        return;
    }
    
    if (isNaN(caMark) || caMark < 0 || caMark > 40) {
        e.preventDefault();
        showErrorMessage('CA mark must be between 0 and 40.');
        return;
    }
    
    if (isNaN(examMark) || examMark < 0 || examMark > 60) {
        e.preventDefault();
        showErrorMessage('Exam mark must be between 0 and 60.');
        return;
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize calculation on page load
    calculateTotal();
});
</script>

<?php include 'includes/footer.php'; ?>

