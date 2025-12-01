<?php
/**
 * Test Admin Lookup for Test Permit
 */

require_once 'config/config.php';

echo "<h2>Test Admin Lookup</h2>";

$db = getDB();

// Test 1: Check if admin exists
echo "<h3>1. Admin Data Check:</h3>";
$stmt = $db->prepare("SELECT id, username, full_name FROM admins WHERE id = 1");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "<p style='color: green;'>✓ Admin found:</p>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$admin['id']}</li>";
    echo "<li><strong>Username:</strong> {$admin['username']}</li>";
    echo "<li><strong>Full Name:</strong> {$admin['full_name']}</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Admin not found</p>";
}

// Test 2: Check test permits
echo "<h3>2. Test Permits Check:</h3>";
$stmt = $db->prepare("SELECT id, student_id, status, approved_by, approved_at FROM test_permits ORDER BY id DESC LIMIT 5");
$stmt->execute();
$permits = $stmt->fetchAll();

if (empty($permits)) {
    echo "<p style='color: orange;'>⚠ No test permits found</p>";
} else {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Permit ID</th><th>Student ID</th><th>Status</th><th>Approved By</th><th>Approved At</th></tr>";
    foreach ($permits as $permit) {
        $approvedBy = $permit['approved_by'] ? $permit['approved_by'] : 'NULL';
        $approvedAt = $permit['approved_at'] ? $permit['approved_at'] : 'NULL';
        echo "<tr>";
        echo "<td>{$permit['id']}</td>";
        echo "<td>{$permit['student_id']}</td>";
        echo "<td>{$permit['status']}</td>";
        echo "<td>{$approvedBy}</td>";
        echo "<td>{$approvedAt}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Simulate the admin lookup logic
echo "<h3>3. Admin Lookup Simulation:</h3>";
if (!empty($permits)) {
    foreach ($permits as $permit) {
        echo "<h4>Permit ID: {$permit['id']} (Status: {$permit['status']})</h4>";
        
        if ($permit['approved_by']) {
            $stmt = $db->prepare("SELECT full_name FROM admins WHERE id = ?");
            $stmt->execute([$permit['approved_by']]);
            $adminData = $stmt->fetch();
            
            if ($adminData && !empty($adminData['full_name'])) {
                echo "<p style='color: green;'>✓ Admin Name: {$adminData['full_name']}</p>";
            } else {
                echo "<p style='color: red;'>✗ Admin data not found for ID: {$permit['approved_by']}</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ No approved_by field (Status: {$permit['status']})</p>";
        }
    }
} else {
    echo "<p>No test permits to test with.</p>";
}

echo "<h3>4. Next Steps:</h3>";
if (empty($permits)) {
    echo "<p style='color: blue;'><strong>Action needed:</strong> Create a test permit first.</p>";
} else {
    $approvedPermits = array_filter($permits, function($p) { return $p['status'] === 'Approved'; });
    if (empty($approvedPermits)) {
        echo "<p style='color: blue;'><strong>Action needed:</strong> Approve a test permit through the admin panel.</p>";
        echo "<p><a href='admin/test_permits.php' target='_blank'>Go to Admin Test Permits</a></p>";
    } else {
        echo "<p style='color: green;'>✓ Approved test permits found. The admin name should display correctly.</p>";
    }
}

echo "<p><a href='debug_admin_data.php'>Run Full Debug</a> | <a href='student/documents.php'>Go to Documents</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
