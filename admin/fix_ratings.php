<?php
require_once '../config/config.php';
require_once '../config/grading_config.php';
require_once '../includes/auth.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$db = getDB();

try {
    // Get all test results
    $stmt = $db->query("SELECT * FROM test_results");
    $results = $stmt->fetchAll();
    
    $updated = 0;
    $errors = 0;
    
    echo "<h1>Fixing Overall Ratings (Based on Exam Rating)</h1>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Permit No</th><th>Exam Rating</th><th>Exam %</th><th>Old Rating</th><th>New Rating</th><th>Status</th></tr>";
    
    foreach ($results as $result) {
        $examRating = $result['exam_rating'] ?? 0;
        $examPercentage = $result['exam_percentage'] ?? 0;
        $oldRating = $result['overall_rating'] ?? 'N/A';
        
        // Calculate new rating using exam rating (not percentage)
        $newRating = getOverallRating($examRating);
        
        // Update if different
        if ($oldRating !== $newRating) {
            $updateStmt = $db->prepare("UPDATE test_results SET overall_rating = ?, updated_at = NOW() WHERE id = ?");
            if ($updateStmt->execute([$newRating, $result['id']])) {
                $status = "Updated";
                $updated++;
            } else {
                $status = "Error";
                $errors++;
            }
        } else {
            $status = "No change";
        }
        
        echo "<tr>";
        echo "<td>{$result['id']}</td>";
        echo "<td>{$result['permit_number']}</td>";
        echo "<td>" . number_format($examRating, 1) . "</td>";
        echo "<td>" . number_format($examPercentage, 1) . "%</td>";
        echo "<td>{$oldRating}</td>";
        echo "<td>{$newRating}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<h2>Summary:</h2>";
    echo "<p>Updated: {$updated} records</p>";
    echo "<p>Errors: {$errors} records</p>";
    echo "<p>Total processed: " . count($results) . " records</p>";
    
    if ($updated > 0) {
        echo "<p style='color: green;'>Successfully updated {$updated} records!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
