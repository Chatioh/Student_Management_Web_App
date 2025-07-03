<?php
require_once 'config.php';

$page_title = 'Transcripts Management';

// GPA Calculation Function
function calculateGPA($totalScore) {
    if ($totalScore >= 80) return 4.0;
    if ($totalScore >= 70) return 3.0;
    if ($totalScore >= 60) return 2.0;
    if ($totalScore >= 50) return 1.0;
    return 0.0;
}

function getGradeFromScore($totalScore) {
    if ($totalScore >= 80) return 'A';
    if ($totalScore >= 70) return 'B';
    if ($totalScore >= 60) return 'C';
    if ($totalScore >= 50) return 'D';
    return 'F';
}

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'generate_transcript':
                    $student_id = $_POST['student_id'];
                    $semester = $_POST['semester'];
                    
                    // Get all exam results for the student in the specified semester
                    $results_stmt = $pdo->prepare("
                        SELECT er.*, c.credit_hours, c.name as course_name, c.code as course_code
                        FROM exam_results er
                        JOIN enrollments e ON er.enrollment_id = e.id
                        JOIN courses c ON e.course_id = c.id
                        WHERE e.student_id = ? AND c.semester = ?
                    ");
                    $results_stmt->execute([$student_id, $semester]);
                    $results = $results_stmt->fetchAll();
                    
                    if (!empty($results)) {
                        $total_points = 0;
                        $total_credits = 0;
                        
                        foreach ($results as $result) {
                            $gpa_points = calculateGPA($result['total_score']);
                            $credit_hours = $result['credit_hours'] ?: 3; // Default to 3 if not set
                            
                            $total_points += $gpa_points * $credit_hours;
                            $total_credits += $credit_hours;
                        }
                        
                        $semester_gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0.0;
                        
                        // Check if transcript already exists
                        $check_stmt = $pdo->prepare("
                            SELECT id FROM transcripts 
                            WHERE student_id = ? AND semester = ?
                        ");
                        $check_stmt->execute([$student_id, $semester]);
                        
                        if ($check_stmt->fetch()) {
                            // Update existing transcript
                            $update_stmt = $pdo->prepare("
                                UPDATE transcripts 
                                SET gpa = ?, remarks = ?
                                WHERE student_id = ? AND semester = ?
                            ");
                            $remarks = $semester_gpa >= 3.0 ? 'Good Standing' : ($semester_gpa >= 2.0 ? 'Satisfactory' : 'Needs Improvement');
                            $update_stmt->execute([$semester_gpa, $remarks, $student_id, $semester]);
                        } else {
                            // Create new transcript
                            $insert_stmt = $pdo->prepare("
                                INSERT INTO transcripts (student_id, semester, gpa, remarks) 
                                VALUES (?, ?, ?, ?)
                            ");
                            $remarks = $semester_gpa >= 3.0 ? 'Good Standing' : ($semester_gpa >= 2.0 ? 'Satisfactory' : 'Needs Improvement');
                            $insert_stmt->execute([$student_id, $semester, $semester_gpa, $remarks]);
                        }
                        
                        $message = 'Transcript generated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'No exam results found for the selected student and semester.';
                        $message_type = 'error';
                    }
                    break;
                    
                case 'update_transcript':
                    $stmt = $pdo->prepare("
                        UPDATE transcripts 
                        SET semester = ?, gpa = ?, remarks = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['semester'],
                        $_POST['gpa'],
                        $_POST['remarks'],
                        $_POST['transcript_id']
                    ]);
                    $message = 'Transcript updated successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete_transcript':
                    $stmt = $pdo->prepare("DELETE FROM transcripts WHERE id = ?");
                    $stmt->execute([$_POST['transcript_id']]);
                    $message = 'Transcript deleted successfully!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch transcripts with student information
$search = isset($_GET['search']) ? $_GET['search'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$gpa_filter = isset($_GET['gpa_range']) ? $_GET['gpa_range'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(s.full_name LIKE ? OR s.matricule LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($semester_filter) {
    $where_conditions[] = "t.semester = ?";
    $params[] = $semester_filter;
}

if ($gpa_filter) {
    switch ($gpa_filter) {
        case 'excellent':
            $where_conditions[] = "t.gpa >= 3.5";
            break;
        case 'good':
            $where_conditions[] = "t.gpa >= 3.0 AND t.gpa < 3.5";
            break;
        case 'satisfactory':
            $where_conditions[] = "t.gpa >= 2.0 AND t.gpa < 3.0";
            break;
        case 'poor':
            $where_conditions[] = "t.gpa < 2.0";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM transcripts t
    JOIN students s ON t.student_id = s.id
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_transcripts = $count_stmt->fetch()['total'];
$total_pages = ceil($total_transcripts / $limit);

// Fetch transcripts
$sql = "
    SELECT 
        t.*,
        s.full_name as student_name,
        s.matricule,
        d.name as department_name
    FROM transcripts t
    JOIN students s ON t.student_id = s.id
    LEFT JOIN departments d ON s.department_name = d.id
    $where_clause
    ORDER BY t.gpa DESC, s.full_name
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transcripts = $stmt->fetchAll();

// Fetch students for dropdown
$students_stmt = $pdo->query("
    SELECT s.id, s.full_name, s.matricule, d.name as department_name
    FROM students s
    LEFT JOIN departments d ON s.department_name = d.id
    ORDER BY s.full_name
");
$students = $students_stmt->fetchAll();

// Get unique semesters
$semesters_stmt = $pdo->query("
    SELECT DISTINCT semester FROM courses 
    WHERE semester IS NOT NULL 
    UNION 
    SELECT DISTINCT semester FROM transcripts 
    WHERE semester IS NOT NULL 
    ORDER BY semester
");
$semesters = $semesters_stmt->fetchAll();

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
        <h2 class="mb-1">Transcripts Management</h2>
        <p class="text-muted mb-0">Generate and manage student academic transcripts with GPA calculations</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
        <i class="fas fa-plus me-2"></i>Generate Transcript
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h4 class="text-primary">
                    <?php
                    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM transcripts");
                    echo $total_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Total Transcripts</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
                    <i class="fas fa-star"></i>
                </div>
                <h4 class="text-success">
                    <?php
                    $excellent_stmt = $pdo->query("SELECT COUNT(*) as total FROM transcripts WHERE gpa >= 3.5");
                    echo $excellent_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Excellent (3.5+)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(253, 203, 110, 0.1); color: var(--warning-color);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4 class="text-warning">
                    <?php
                    $avg_stmt = $pdo->query("SELECT AVG(gpa) as average FROM transcripts");
                    $average_gpa = $avg_stmt->fetch()['average'];
                    echo $average_gpa ? number_format($average_gpa, 2) : '0.00';
                    ?>
                </h4>
                <p class="text-muted mb-0">Average GPA</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(116, 185, 255, 0.1); color: var(--info-color);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h4 class="text-info">
                    <?php
                    $honor_stmt = $pdo->query("SELECT COUNT(*) as total FROM transcripts WHERE gpa >= 3.0");
                    echo $honor_stmt->fetch()['total'];
                    ?>
                </h4>
                <p class="text-muted mb-0">Honor Roll (3.0+)</p>
            </div>
        </div>
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
                           placeholder="Search by name or matricule..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label for="semester" class="form-label">Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem['semester']; ?>" 
                            <?php echo $semester_filter === $sem['semester'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sem['semester']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="gpa_range" class="form-label">GPA Range</label>
                <select class="form-select" id="gpa_range" name="gpa_range">
                    <option value="">All GPAs</option>
                    <option value="excellent" <?php echo $gpa_filter === 'excellent' ? 'selected' : ''; ?>>Excellent (3.5+)</option>
                    <option value="good" <?php echo $gpa_filter === 'good' ? 'selected' : ''; ?>>Good (3.0-3.4)</option>
                    <option value="satisfactory" <?php echo $gpa_filter === 'satisfactory' ? 'selected' : ''; ?>>Satisfactory (2.0-2.9)</option>
                    <option value="poor" <?php echo $gpa_filter === 'poor' ? 'selected' : ''; ?>>Poor (<2.0)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="transcripts.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transcripts Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Student Transcripts
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($transcripts)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>GPA</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transcripts as $transcript): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($transcript['student_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($transcript['student_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($transcript['matricule']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($transcript['department_name']): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($transcript['department_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($transcript['semester']); ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 60px; height: 8px;">
                                    <div class="progress-bar bg-<?php echo $transcript['gpa'] >= 3.0 ? 'success' : ($transcript['gpa'] >= 2.0 ? 'warning' : 'danger'); ?>" 
                                         style="width: <?php echo ($transcript['gpa'] / 4.0) * 100; ?>%"></div>
                                </div>
                                <span class="fw-bold"><?php echo number_format($transcript['gpa'], 2); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php
                            $gpa_class = '';
                            if ($transcript['gpa'] >= 3.5) $gpa_class = 'success';
                            elseif ($transcript['gpa'] >= 3.0) $gpa_class = 'primary';
                            elseif ($transcript['gpa'] >= 2.0) $gpa_class = 'warning';
                            else $gpa_class = 'danger';
                            
                            $grade_letter = '';
                            if ($transcript['gpa'] >= 3.5) $grade_letter = 'A';
                            elseif ($transcript['gpa'] >= 3.0) $grade_letter = 'B';
                            elseif ($transcript['gpa'] >= 2.0) $grade_letter = 'C';
                            elseif ($transcript['gpa'] >= 1.0) $grade_letter = 'D';
                            else $grade_letter = 'F';
                            ?>
                            <span class="badge badge-<?php echo $gpa_class; ?>"><?php echo $grade_letter; ?></span>
                        </td>
                        <td>
                            <small class="text-muted"><?php echo htmlspecialchars($transcript['remarks']); ?></small>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="viewTranscript(<?php echo $transcript['id']; ?>)"
                                        data-bs-toggle="tooltip" title="View Transcript">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" 
                                        onclick="printTranscript(<?php echo $transcript['id']; ?>)"
                                        data-bs-toggle="tooltip" title="Print Transcript">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($transcript)); ?>)"
                                        data-bs-toggle="tooltip" title="Edit Transcript">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteTranscript(<?php echo $transcript['id']; ?>, '<?php echo htmlspecialchars($transcript['student_name']); ?>', '<?php echo htmlspecialchars($transcript['semester']); ?>')"
                                        data-bs-toggle="tooltip" title="Delete Transcript">
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
            <nav aria-label="Transcripts pagination">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>&gpa_range=<?php echo urlencode($gpa_filter); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>&gpa_range=<?php echo urlencode($gpa_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>&gpa_range=<?php echo urlencode($gpa_filter); ?>">
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
            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No transcripts found</h5>
            <p class="text-muted">Try adjusting your filters or generate a new transcript.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
                <i class="fas fa-plus me-2"></i>Generate First Transcript
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Transcript Modal -->
<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateModalLabel">
                    <i class="fas fa-plus me-2"></i>Generate Transcript
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="generate_transcript">
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student *</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['matricule'] . ')' . ($student['department_name'] ? ' - ' . $student['department_name'] : '')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="semester_generate" class="form-label">Semester *</label>
                        <select class="form-select" id="semester_generate" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem['semester']; ?>">
                                <?php echo htmlspecialchars($sem['semester']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> The transcript will be automatically generated based on the student's exam results for the selected semester. GPA will be calculated using the standard 4.0 scale.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>Generate Transcript
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transcript Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Transcript
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_transcript">
                    <input type="hidden" name="transcript_id" id="editTranscriptId">
                    
                    <div class="mb-3">
                        <label for="edit_semester" class="form-label">Semester *</label>
                        <input type="text" class="form-control" id="edit_semester" name="semester" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_gpa" class="form-label">GPA (0.0 - 4.0) *</label>
                        <input type="number" class="form-control" id="edit_gpa" name="gpa" 
                               min="0" max="4" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Transcript
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
                <p>Are you sure you want to delete the transcript for:</p>
                <p><strong id="deleteStudentName"></strong> - <strong id="deleteSemester"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_transcript">
                    <input type="hidden" name="transcript_id" id="deleteTranscriptId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Transcript
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal(transcript) {
    document.getElementById('editTranscriptId').value = transcript.id;
    document.getElementById('edit_semester').value = transcript.semester;
    document.getElementById('edit_gpa').value = transcript.gpa;
    document.getElementById('edit_remarks').value = transcript.remarks || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function deleteTranscript(id, studentName, semester) {
    document.getElementById('deleteTranscriptId').value = id;
    document.getElementById('deleteStudentName').textContent = studentName;
    document.getElementById('deleteSemester').textContent = semester;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function viewTranscript(transcriptId) {
    // Open transcript details in a new window or modal
    window.open(`transcript_detail.php?id=${transcriptId}`, '_blank', 'width=800,height=600');
}

function printTranscript(transcriptId) {
    // Open printable version of transcript
    window.open(`transcript_print.php?id=${transcriptId}`, '_blank', 'width=800,height=600');
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

