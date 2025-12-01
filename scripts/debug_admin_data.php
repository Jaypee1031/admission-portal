<?php
/**
 * Debug Admin Data for Test Permit
 */

require_once 'config/config.php';

echo "<h2>Debug Admin Data for Test Permit</h2>";

$db = getDB();

// Check if there are any test permits with approved_by
echo "<h3>1. Test Permits with approved_by:</h3>";
$stmt = $db->prepare("SELECT id, student_id, approved_by, status FROM test_permits WHERE approved_by IS NOT NULL");
$stmt->execute();
$permits = $stmt->fetchAll();

if (empty($permits)) {
    echo "<p style='color: red;'>No test permits found with approved_by field set.</p>";
} else {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Permit ID</th><th>Student ID</th><th>Approved By</th><th>Status</th></tr>";
    foreach ($permits as $permit) {
        echo "<tr>";
        echo "<td>{$permit['id']}</td>";
        echo "<td>{$permit['student_id']}</td>";
        echo "<td>{$permit['approved_by']}</td>";
        echo "<td>{$permit['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check admins table
echo "<h3>2. Available Admins:</h3>";
$stmt = $db->prepare("SELECT id, username, full_name FROM admins");
$stmt->execute();
$admins = $stmt->fetchAll();

if (empty($admins)) {
    echo "<p style='color: red;'>No admins found in database.</p>";
} else {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th></tr>";
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td>{$admin['full_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test the admin lookup for each permit
echo "<h3>3. Admin Lookup Test:</h3>";
foreach ($permits as $permit) {
    echo "<h4>Permit ID: {$permit['id']}</h4>";
    
    if ($permit['approved_by']) {
        $stmt = $db->prepare("SELECT full_name FROM admins WHERE id = ?");
        $stmt->execute([$permit['approved_by']]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p style='color: green;'>✓ Admin found: {$admin['full_name']}</p>";
        } else {
            echo "<p style='color: red;'>✗ Admin not found for ID: {$permit['approved_by']}</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No approved_by field set</p>";
    }
}

echo "<h3>4. Recommendations:</h3>";
if (empty($permits)) {
    echo "<p style='color: red;'><strong>Issue:</strong> No test permits have been approved yet.</p>";
    echo "<p><strong>Solution:</strong> Approve a test permit first through the admin panel.</p>";
} elseif (empty($admins)) {
    echo "<p style='color: red;'><strong>Issue:</strong> No admins found in database.</p>";
    echo "<p><strong>Solution:</strong> Check if the admins table has data.</p>";
} else {
    echo "<p style='color: green;'>✓ Database structure looks correct. The issue might be in the PDF generation logic.</p>";
}

echo "<p><a href='student/documents.php'>Go to Documents</a> | <a href='admin/test_permits.php'>Go to Admin Test Permits</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
