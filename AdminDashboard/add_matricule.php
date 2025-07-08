<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $matricule = trim($_POST['matricule']);
        
        if (empty($matricule)) {
            echo json_encode(['success' => false, 'message' => 'Matricule is required']);
            exit;
        }
        
        // Check if matricule already exists
        $check_stmt = $pdo->prepare("SELECT id FROM students WHERE matricule = ?");
        $check_stmt->execute([$matricule]);
        
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Matricule already exists']);
            exit;
        }
        
        // Insert matricule-only record
        $stmt = $pdo->prepare("
            INSERT INTO students (matricule, full_name, email, phone_number, password, school_id, department_name) 
            VALUES (?, '', '', '', '', '', NULL)
        ");
        $stmt->execute([$matricule]);
        
        echo json_encode(['success' => true, 'message' => 'Matricule added successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>