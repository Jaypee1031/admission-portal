<?php
/**
 * Manually Approve Test Permit
 */

require_once 'config/config.php';

echo "<h2>Manually Approve Test Permit</h2>";

$db = getDB();

// Get all pending test permits
$stmt = $db->prepare("SELECT id, student_id, status, approved_by FROM test_permits WHERE status = 'Pending' OR approved_by IS NULL");
$stmt->execute();
$permits = $stmt->fetchAll();

if (empty($permits)) {
    echo "<p style='color: green;'>No permits need approval.</p>";
} else {
    echo "<h3>Permits that need approval:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Status</th><th>Approved By</th><th>Action</th></tr>";
    
    foreach ($permits as $permit) {
        $approvedBy = $permit['approved_by'] ? $permit['approved_by'] : 'NULL';
        echo "<tr>";
        echo "<td>{$permit['id']}</td>";
        echo "<td>{$permit['student_id']}</td>";
        echo "<td>{$permit['status']}</td>";
        echo "<td>{$approvedBy}</td>";
        echo "<td><a href='?approve={$permit['id']}' style='color: green;'>Approve</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Handle approval
if (isset($_GET['approve'])) {
    $permitId = (int)$_GET['approve'];
    
    try {
        // Get admin ID (use the first admin)
        $stmt = $db->prepare("SELECT id FROM admins LIMIT 1");
        $stmt->execute();
        $adminId = $stmt->fetchColumn();
        
        if ($adminId) {
            // Update the test permit
            $stmt = $db->prepare("UPDATE test_permits SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$adminId, $permitId]);
            
            if ($result) {
                echo "<p style='color: green;'>✓ Test permit ID $permitId approved successfully!</p>";
                echo "<p>Approved by admin ID: $adminId</p>";
                
                // Also update requirements for this student
                $stmt = $db->prepare("SELECT student_id FROM test_permits WHERE id = ?");
                $stmt->execute([$permitId]);
                $permitData = $stmt->fetch();
                
                if ($permitData) {
                    $studentId = $permitData['student_id'];
                    $stmt = $db->prepare("UPDATE requirements SET status = 'Approved', reviewed_at = NOW() WHERE student_id = ? AND status = 'Pending'");
                    $stmt->execute([$studentId]);
                    echo "<p>✓ Also updated requirements for student ID: $studentId</p>";
                }
                
                echo "<p><a href='view_test_permit.php'>View Test Permit PDF</a></p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to approve test permit.</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ No admin found to assign approval to.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<p><a href='check_permit_status.php'>Check Permit Status</a> | <a href='admin/test_permits.php'>Go to Admin Panel</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
