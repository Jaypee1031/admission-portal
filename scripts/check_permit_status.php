<?php
/**
 * Check Test Permit Status
 */

require_once 'config/config.php';

echo "<h2>Test Permit Status Check</h2>";

$db = getDB();

// Get the current user's test permit
$studentId = 1; // Assuming student ID 1 for testing
$stmt = $db->prepare("SELECT * FROM test_permits WHERE student_id = ?");
$stmt->execute([$studentId]);
$permit = $stmt->fetch();

echo "<h3>Current Test Permit Data:</h3>";
if ($permit) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($permit as $key => $value) {
        $displayValue = $value ? $value : 'NULL';
        echo "<tr><td>$key</td><td>$displayValue</td></tr>";
    }
    echo "</table>";
    
    // Check admin data
    if ($permit['approved_by']) {
        $stmt = $db->prepare("SELECT full_name FROM admins WHERE id = ?");
        $stmt->execute([$permit['approved_by']]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p style='color: green;'>✓ Admin found: <strong>{$admin['full_name']}</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Admin not found for ID: {$permit['approved_by']}</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No approved_by field set</p>";
        echo "<p><strong>This is why you see 'N/A' - the test permit hasn't been approved yet!</strong></p>";
    }
} else {
    echo "<p style='color: red;'>No test permit found for student ID: $studentId</p>";
}

// Check all test permits
echo "<h3>All Test Permits:</h3>";
$stmt = $db->prepare("SELECT id, student_id, status, approved_by, approved_at FROM test_permits ORDER BY id DESC");
$stmt->execute();
$permits = $stmt->fetchAll();

if (empty($permits)) {
    echo "<p>No test permits found.</p>";
} else {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Status</th><th>Approved By</th><th>Approved At</th></tr>";
    foreach ($permits as $p) {
        $approvedBy = $p['approved_by'] ? $p['approved_by'] : 'NULL';
        $approvedAt = $p['approved_at'] ? $p['approved_at'] : 'NULL';
        $statusColor = $p['status'] === 'Approved' ? 'green' : ($p['status'] === 'Pending' ? 'orange' : 'red');
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['student_id']}</td>";
        echo "<td style='color: $statusColor;'>{$p['status']}</td>";
        echo "<td>{$approvedBy}</td>";
        echo "<td>{$approvedAt}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Solution:</h3>";
echo "<ol>";
echo "<li><strong>If Status is 'Pending':</strong> Go to <a href='admin/test_permits.php' target='_blank'>Admin Panel</a> and approve the test permit</li>";
echo "<li><strong>If Status is 'Approved' but approved_by is NULL:</strong> <a href='fix_orphaned_approvals.php'>Run Fix Script</a></li>";
echo "<li><strong>If no test permit exists:</strong> Create one first through the student portal</li>";
echo "</ol>";

echo "<p><a href='admin/test_permits.php' target='_blank'>Go to Admin Panel</a> | <a href='student/documents.php'>Go to Student Documents</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
