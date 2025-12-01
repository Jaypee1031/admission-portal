<?php
/**
 * Simple Admin Lookup Test
 */

require_once 'config/config.php';

echo "<h2>Simple Admin Lookup Test</h2>";

$db = getDB();

// Test the exact same logic as view_test_permit.php
$permitData = [
    'approved_by' => 1,
    'status' => 'Approved'
];

echo "<h3>Testing with approved_by = 1:</h3>";

$adminData = null;
$adminName = 'N/A';

if ($permitData && isset($permitData['approved_by']) && !empty($permitData['approved_by'])) {
    echo "<p>✓ approved_by field exists: {$permitData['approved_by']}</p>";
    
    try {
        $stmt = $db->prepare("SELECT full_name FROM admins WHERE id = ?");
        $stmt->execute([$permitData['approved_by']]);
        $adminData = $stmt->fetch();
        
        echo "<p>✓ Query executed successfully</p>";
        echo "<p>Admin data: " . print_r($adminData, true) . "</p>";
        
        if ($adminData && !empty($adminData['full_name'])) {
            $adminName = $adminData['full_name'];
            echo "<p style='color: green;'>✓ Admin name found: <strong>$adminName</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Admin data is empty or full_name is empty</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ approved_by field is missing or empty</p>";
}

echo "<h3>Final Result:</h3>";
echo "<p><strong>Admin Name: $adminName</strong></p>";

// Test direct query
echo "<h3>Direct Admin Query Test:</h3>";
try {
    $stmt = $db->prepare("SELECT id, username, full_name FROM admins WHERE id = 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Direct query successful:</p>";
        echo "<ul>";
        echo "<li>ID: {$admin['id']}</li>";
        echo "<li>Username: {$admin['username']}</li>";
        echo "<li>Full Name: {$admin['full_name']}</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Direct query returned no results</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Direct query error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='view_test_permit.php'>Test View Test Permit</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
