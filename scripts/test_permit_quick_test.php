<?php
/**
 * Quick Test for Test Permit PDF
 */

require_once 'config/config.php';

echo "<h2>Test Permit PDF Quick Test</h2>";

// Test the exact same query as view_test_permit.php
$db = getDB();
$studentId = 1; // Assuming student ID 1

$stmt = $db->prepare("
    SELECT s.*, af.*, tp.approved_by, tp.approved_at, a.full_name as admin_name
    FROM students s 
    LEFT JOIN admission_forms af ON s.id = af.student_id 
    LEFT JOIN test_permits tp ON s.id = tp.student_id
    LEFT JOIN admins a ON tp.approved_by = a.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$data = $stmt->fetch();

if ($data) {
    echo "<h3>Query Result:</h3>";
    echo "<p><strong>Student:</strong> {$data['first_name']} {$data['last_name']}</p>";
    echo "<p><strong>Test Permit Status:</strong> {$data['status']}</p>";
    echo "<p><strong>Approved By:</strong> " . ($data['approved_by'] ?: 'NULL') . "</p>";
    echo "<p><strong>Admin Name from JOIN:</strong> " . ($data['admin_name'] ?: 'NULL') . "</p>";
    
    // Test the fallback logic
    $adminName = $data['admin_name'] ?? 'N/A';
    
    if ($adminName === 'N/A' && $data['approved_by']) {
        try {
            $stmt = $db->prepare("SELECT full_name FROM admins WHERE id = ?");
            $stmt->execute([$data['approved_by']]);
            $admin = $stmt->fetch();
            if ($admin && !empty($admin['full_name'])) {
                $adminName = $admin['full_name'];
                echo "<p style='color: green;'><strong>Fallback Admin Name:</strong> $adminName</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Fallback failed: " . $e->getMessage() . "</p>";
        }
    }
    
    if ($adminName === 'N/A') {
        $adminName = 'System Administrator';
        echo "<p style='color: orange;'><strong>Default Admin Name:</strong> $adminName</p>";
    }
    
    echo "<p><strong>Final Admin Name:</strong> <span style='color: green; font-weight: bold;'>$adminName</span></p>";
    
} else {
    echo "<p style='color: red;'>No data found for student ID: $studentId</p>";
}

echo "<h3>Test Links:</h3>";
echo "<p><a href='view_test_permit.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Test Permit PDF</a></p>";
echo "<p><a href='student/documents.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Documents Page</a></p>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
p { margin: 10px 0; }
h3 { color: #333; }
</style>
