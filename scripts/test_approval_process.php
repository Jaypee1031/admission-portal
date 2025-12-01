<?php
/**
 * Test the Approval Process
 */

require_once 'config/config.php';

echo "<h2>Test Approval Process</h2>";

$db = getDB();

// Check current test permits
echo "<h3>1. Current Test Permits:</h3>";
$stmt = $db->prepare("SELECT id, student_id, status, approved_by, approved_at FROM test_permits ORDER BY id DESC");
$stmt->execute();
$permits = $stmt->fetchAll();

if (empty($permits)) {
    echo "<p style='color: red;'>No test permits found. Create one first.</p>";
} else {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Status</th><th>Approved By</th><th>Approved At</th></tr>";
    foreach ($permits as $permit) {
        $approvedBy = $permit['approved_by'] ? $permit['approved_by'] : 'NULL';
        $approvedAt = $permit['approved_at'] ? $permit['approved_at'] : 'NULL';
        $statusColor = $permit['status'] === 'Approved' ? 'green' : ($permit['status'] === 'Pending' ? 'orange' : 'red');
        echo "<tr>";
        echo "<td>{$permit['id']}</td>";
        echo "<td>{$permit['student_id']}</td>";
        echo "<td style='color: $statusColor;'>{$permit['status']}</td>";
        echo "<td>{$approvedBy}</td>";
        echo "<td>{$approvedAt}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test admin lookup for each permit
echo "<h3>2. Admin Lookup Test:</h3>";
foreach ($permits as $permit) {
    echo "<h4>Permit ID: {$permit['id']} (Status: {$permit['status']})</h4>";
    
    if ($permit['approved_by']) {
        $stmt = $db->prepare("SELECT full_name FROM admins WHERE id = ?");
        $stmt->execute([$permit['approved_by']]);
        $admin = $stmt->fetch();
        
        if ($admin && !empty($admin['full_name'])) {
            echo "<p style='color: green;'>✓ Admin Name: <strong>{$admin['full_name']}</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Admin not found for ID: {$permit['approved_by']}</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No approved_by field (Status: {$permit['status']})</p>";
        if ($permit['status'] === 'Approved') {
            echo "<p style='color: red;'>❌ PROBLEM: Status is Approved but no approved_by field!</p>";
        }
    }
}

// Check if there are any approved permits without approved_by
echo "<h3>3. Data Integrity Check:</h3>";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM test_permits WHERE status = 'Approved' AND approved_by IS NULL");
$stmt->execute();
$orphanedApprovals = $stmt->fetchColumn();

if ($orphanedApprovals > 0) {
    echo "<p style='color: red;'>❌ Found $orphanedApprovals approved permits without approved_by field!</p>";
    echo "<p>This means the approval process isn't setting the approved_by field correctly.</p>";
} else {
    echo "<p style='color: green;'>✓ All approved permits have approved_by field set.</p>";
}

echo "<h3>4. Manual Fix (if needed):</h3>";
if ($orphanedApprovals > 0) {
    echo "<p>To fix orphaned approvals, run this SQL:</p>";
    echo "<pre>";
    echo "UPDATE test_permits SET approved_by = 1 WHERE status = 'Approved' AND approved_by IS NULL;";
    echo "</pre>";
    echo "<p><a href='fix_orphaned_approvals.php'>Run Fix Script</a></p>";
}

echo "<p><a href='admin/test_permits.php'>Go to Admin Panel</a> | <a href='test_admin_lookup.php'>Test Admin Lookup</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
