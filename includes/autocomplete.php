<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Handle autocomplete requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'autocomplete') {
    $query = trim($_POST['query'] ?? '');
    $context = $_POST['context'] ?? 'general'; // general, students, applicants, etc.
    
    if (empty($query) || strlen($query) < 1) {
        echo json_encode(['success' => false, 'message' => 'Query too short']);
        exit;
    }
    
    try {
        $db = getDB();
        $suggestions = [];
        
        switch ($context) {
            case 'students':
                $suggestions = getStudentSuggestions($db, $query);
                break;
            case 'applicants':
                $suggestions = getApplicantSuggestions($db, $query);
                break;
            case 'test_permits':
                $suggestions = getTestPermitSuggestions($db, $query);
                break;
            case 'admission_forms':
                $suggestions = getAdmissionFormSuggestions($db, $query);
                break;
            case 'f2_forms':
                $suggestions = getF2FormSuggestions($db, $query);
                break;
            default:
                $suggestions = getGeneralSuggestions($db, $query);
                break;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $suggestions
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

function getStudentSuggestions($db, $query) {
    // Clean the query - remove email addresses and extra formatting
    $cleanQuery = preg_replace('/\s*\([^)]*\)/', '', $query);
    $cleanQuery = trim($cleanQuery);
    
    $stmt = $db->prepare("
        SELECT DISTINCT 
            s.id,
            CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) as name,
            s.first_name,
            s.last_name,
            s.email,
            af.last_school,
            af.home_address,
            tp.permit_number
        FROM students s
        LEFT JOIN admission_forms af ON s.id = af.student_id
        LEFT JOIN test_permits tp ON s.id = tp.student_id
        WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.middle_name LIKE ? OR s.email LIKE ? OR
              CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) LIKE ? OR
              CONCAT(s.first_name, ' ', s.last_name) LIKE ?
        ORDER BY 
            CASE 
                WHEN s.first_name LIKE ? THEN 1
                WHEN s.last_name LIKE ? THEN 2
                WHEN s.email LIKE ? THEN 3
                ELSE 4
            END,
            s.first_name, s.last_name
        LIMIT 10
    ");
    
    $searchPattern = '%' . $query . '%';
    $cleanPattern = '%' . $cleanQuery . '%';
    $stmt->execute([
        $searchPattern, $searchPattern, $searchPattern, $searchPattern,
        $cleanPattern, $cleanPattern,
        $searchPattern, $searchPattern, $searchPattern
    ]);
    
    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = [
            'id' => $row['id'],
            'name' => trim($row['name']),
            'email' => $row['email'],
            'permit_number' => $row['permit_number'] ?? '',
            'display' => trim($row['name']) . ' (' . $row['email'] . ')'
        ];
    }
    
    return $suggestions;
}

function getApplicantSuggestions($db, $query) {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            s.id,
            CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) as name,
            s.first_name,
            s.last_name,
            s.email,
            s.status
        FROM students s
        WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.middle_name LIKE ? OR s.email LIKE ?)
        ORDER BY s.first_name, s.last_name
        LIMIT 10
    ");
    
    $searchPattern = '%' . $query . '%';
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    
    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = [
            'id' => $row['id'],
            'name' => trim($row['name']),
            'email' => $row['email'],
            'status' => $row['status'],
            'display' => trim($row['name']) . ' (' . $row['email'] . ') - ' . $row['status']
        ];
    }
    
    return $suggestions;
}

function getTestPermitSuggestions($db, $query) {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            tp.id,
            tp.permit_number,
            CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) as student_name,
            s.email,
            tp.status
        FROM test_permits tp
        JOIN students s ON tp.student_id = s.id
        WHERE tp.permit_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?
        ORDER BY tp.permit_number, s.first_name
        LIMIT 10
    ");
    
    $searchPattern = '%' . $query . '%';
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    
    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = [
            'id' => $row['id'],
            'permit_number' => $row['permit_number'],
            'student_name' => trim($row['student_name']),
            'email' => $row['email'],
            'status' => $row['status'],
            'display' => $row['permit_number'] . ' - ' . trim($row['student_name']) . ' (' . $row['email'] . ')'
        ];
    }
    
    return $suggestions;
}

function getAdmissionFormSuggestions($db, $query) {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            af.id,
            CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) as name,
            s.first_name,
            s.last_name,
            s.email,
            af.course_first
        FROM admission_forms af
        JOIN students s ON af.student_id = s.id
        WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.middle_name LIKE ? OR s.email LIKE ? OR af.course_first LIKE ?
        ORDER BY s.first_name, s.last_name
        LIMIT 10
    ");
    
    $searchPattern = '%' . $query . '%';
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    
    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = [
            'id' => $row['id'],
            'name' => trim($row['name']),
            'email' => $row['email'],
            'course' => $row['course_first'],
            'display' => trim($row['name']) . ' (' . $row['email'] . ') - ' . $row['course_first']
        ];
    }
    
    return $suggestions;
}

function getF2FormSuggestions($db, $query) {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            f2.id,
            CONCAT(f2.first_name, ' ', COALESCE(f2.middle_name, ''), ' ', f2.last_name) as name,
            f2.first_name,
            f2.last_name,
            s.email
        FROM f2_personal_data_forms f2
        JOIN students s ON f2.student_id = s.id
        WHERE f2.first_name LIKE ? OR f2.last_name LIKE ? OR f2.middle_name LIKE ? OR s.email LIKE ?
        ORDER BY f2.first_name, f2.last_name
        LIMIT 10
    ");
    
    $searchPattern = '%' . $query . '%';
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    
    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = [
            'id' => $row['id'],
            'name' => trim($row['name']),
            'email' => $row['email'],
            'display' => trim($row['name']) . ' (' . $row['email'] . ')'
        ];
    }
    
    return $suggestions;
}

function getGeneralSuggestions($db, $query) {
    // Combine suggestions from multiple tables
    $suggestions = [];
    
    // Students
    $studentSuggestions = getStudentSuggestions($db, $query);
    foreach ($studentSuggestions as $suggestion) {
        $suggestions[] = [
            'id' => $suggestion['id'],
            'name' => $suggestion['name'],
            'email' => $suggestion['email'],
            'type' => 'Student',
            'display' => $suggestion['name'] . ' (' . $suggestion['email'] . ') - Student'
        ];
    }
    
    return array_slice($suggestions, 0, 10);
}
?>
