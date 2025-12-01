<?php
/**
 * Fix Orphaned Approvals - Set approved_by for approved permits that don't have it
 */

require_once 'config/config.php';

echo "<h2>Fix Orphaned Approvals</h2>";

$db = getDB();

// Check current state
$stmt = $db->prepare("SELECT COUNT(*) as count FROM test_permits WHERE status = 'Approved' AND approved_by IS NULL");
$stmt->execute();
$orphanedCount = $stmt->fetchColumn();

echo "<p>Found $orphanedCount approved permits without approved_by field.</p>";

if ($orphanedCount > 0) {
    // Get the first admin ID (should be 1 based on your data)
    $stmt = $db->prepare("SELECT id FROM admins LIMIT 1");
    $stmt->execute();
    $adminId = $stmt->fetchColumn();
    
    if ($adminId) {
        // Fix orphaned approvals
        $stmt = $db->prepare("UPDATE test_permits SET approved_by = ?, approved_at = NOW() WHERE status = 'Approved' AND approved_by IS NULL");
        $result = $stmt->execute([$adminId]);
        
        if ($result) {
            $affectedRows = $stmt->rowCount();
            echo "<p style='color: green;'>✓ Fixed $affectedRows orphaned approvals.</p>";
            echo "<p>Set approved_by to admin ID: $adminId</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to fix orphaned approvals.</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ No admin found to assign approvals to.</p>";
    }
} else {
    echo "<p style='color: green;'>✓ No orphaned approvals found.</p>";
}

// Verify the fix
echo "<h3>Verification:</h3>";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM test_permits WHERE status = 'Approved' AND approved_by IS NULL");
$stmt->execute();
$remainingOrphaned = $stmt->fetchColumn();

if ($remainingOrphaned == 0) {
    echo "<p style='color: green;'>✓ All approved permits now have approved_by field set.</p>";
} else {
    echo "<p style='color: red;'>✗ Still $remainingOrphaned orphaned approvals remaining.</p>";
}

echo "<p><a href='test_approval_process.php'>Check Approval Process</a> | <a href='student/documents.php'>Go to Documents</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
