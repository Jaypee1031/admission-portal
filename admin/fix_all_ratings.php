<?php
/**
 * Fix All Ratings Script
 * Updates all test results with correct overall ratings based on exam rating
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/grading_config.php';

try {
    $db = getDB();
    
    echo "<h2>Fix All Ratings</h2>";
    echo "<p>Updating all test results with correct overall ratings based on exam rating...</p>";
    
    // Get all test results
    $stmt = $db->query("SELECT id, permit_number, exam_rating, overall_rating FROM test_results ORDER BY permit_number");
    $results = $stmt->fetchAll();
    
    $updated = 0;
    $changes = [];
    
    echo "<table class='table table-striped'>";
    echo "<thead>";
    echo "<tr><th>Permit Number</th><th>Exam Rating</th><th>Old Rating</th><th>New Rating</th><th>Status</th></tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($results as $result) {
        $examRating = $result['exam_rating'];
        $oldRating = $result['overall_rating'];
        
        // Calculate correct rating
        $newRating = getOverallRating($examRating);
        
        // Update if different
        if ($oldRating !== $newRating) {
            $updateStmt = $db->prepare("UPDATE test_results SET overall_rating = ? WHERE id = ?");
            $updateStmt->execute([$newRating, $result['id']]);
            
            $changes[] = [
                'permit_number' => $result['permit_number'],
                'exam_rating' => $examRating,
                'old_rating' => $oldRating,
                'new_rating' => $newRating
            ];
            
            echo "<tr class='table-warning'>";
            echo "<td>{$result['permit_number']}</td>";
            echo "<td>" . number_format($examRating, 2) . "</td>";
            echo "<td><span class='badge bg-danger'>$oldRating</span></td>";
            echo "<td><span class='badge bg-success'>$newRating</span></td>";
            echo "<td><span class='badge bg-info'>Updated</span></td>";
            echo "</tr>";
            
            $updated++;
        } else {
            echo "<tr>";
            echo "<td>{$result['permit_number']}</td>";
            echo "<td>" . number_format($examRating, 2) . "</td>";
            echo "<td><span class='badge bg-secondary'>$oldRating</span></td>";
            echo "<td><span class='badge bg-secondary'>$newRating</span></td>";
            echo "<td><span class='badge bg-light text-dark'>No Change</span></td>";
            echo "</tr>";
        }
    }
    
    echo "</tbody>";
    echo "</table>";
    
    echo "<div class='alert alert-info'>";
    echo "<strong>Summary:</strong><br>";
    echo "Total Students: " . count($results) . "<br>";
    echo "Records Updated: $updated<br>";
    echo "Records Unchanged: " . (count($results) - $updated) . "<br>";
    echo "</div>";
    
    // Show statistics
    $statsStmt = $db->query("
        SELECT 
            overall_rating,
            COUNT(*) as count,
            MIN(exam_rating) as min_rating,
            MAX(exam_rating) as max_rating,
            AVG(exam_rating) as avg_rating
        FROM test_results 
        GROUP BY overall_rating 
        ORDER BY 
            CASE overall_rating 
                WHEN 'Excellent' THEN 1 
                WHEN 'Very Good' THEN 2 
                WHEN 'Passed' THEN 3 
                WHEN 'Conditional' THEN 4 
                WHEN 'Failed' THEN 5 
            END
    ");
    $stats = $statsStmt->fetchAll();
    
    echo "<h3>Rating Statistics</h3>";
    echo "<table class='table table-bordered'>";
    echo "<thead>";
    echo "<tr><th>Rating</th><th>Count</th><th>Min Exam Rating</th><th>Max Exam Rating</th><th>Avg Exam Rating</th></tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td><span class='badge bg-" . getRatingColor($stat['overall_rating']) . "'>{$stat['overall_rating']}</span></td>";
        echo "<td>{$stat['count']}</td>";
        echo "<td>" . number_format($stat['min_rating'], 2) . "</td>";
        echo "<td>" . number_format($stat['max_rating'], 2) . "</td>";
        echo "<td>" . number_format($stat['avg_rating'], 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
    echo "<div class='alert alert-success'>";
    echo "<strong>✓ All ratings have been fixed!</strong><br>";
    echo "The overall rating is now correctly calculated based on exam rating (not exam percentage).<br>";
    echo "Passing threshold: " . PASSING_THRESHOLD . "<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { padding: 20px; }
.table { margin-bottom: 20px; }
.badge { font-size: 12px; }
</style>

<script>
// Auto-refresh after 5 seconds if there were updates
setTimeout(function() {
    const updatedRows = document.querySelectorAll('.table-warning').length;
    if (updatedRows > 0) {
        console.log('Ratings updated successfully!');
    }
}, 5000);
</script>
