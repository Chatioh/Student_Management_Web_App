<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['course_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$course_id = $_GET['course_id'];
$date = $_GET['date'];

try {
    // Get students enrolled in the course with their current attendance status for the date
    $stmt = $pdo->prepare("
        SELECT 
            e.id as enrollment_id,
            s.id as student_id,
            s.full_name,
            s.matricule,
            COALESCE(a.status, 'Absent') as current_status
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        LEFT JOIN attendance a ON e.id = a.enrollment_id AND a.date = ?
        WHERE e.course_id = ?
        ORDER BY s.full_name
    ");
    
    $stmt->execute([$date, $course_id]);
    $students = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

