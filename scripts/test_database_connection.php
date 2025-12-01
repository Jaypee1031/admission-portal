<?php
require_once 'config/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = getDB();
    echo "<div style='color: green; background: #d4edda; padding: 10px; border-radius: 5px;'>";
    echo "<strong>✓ Database connection successful!</strong>";
    echo "</div>";
    
    // Check if admission_forms table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admission_forms'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
        echo "<strong>✓ admission_forms table exists!</strong>";
        echo "</div>";
        
        // Show table structure
        $stmt = $db->query("DESCRIBE admission_forms");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if there are any existing records
        $stmt = $db->query("SELECT COUNT(*) as count FROM admission_forms");
        $count = $stmt->fetch()['count'];
        echo "<p><strong>Total records in admission_forms:</strong> $count</p>";
        
    } else {
        echo "<div style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
        echo "<strong>✗ admission_forms table does not exist!</strong>";
        echo "</div>";
    }
    
    // Check students table
    $stmt = $db->query("SELECT COUNT(*) as count FROM students");
    $count = $stmt->fetch()['count'];
    echo "<p><strong>Total students:</strong> $count</p>";
    
    if ($count > 0) {
        echo "<h3>Sample Students:</h3>";
        $stmt = $db->query("SELECT id, first_name, last_name, email FROM students LIMIT 5");
        $students = $stmt->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>✗ Database Error</strong><br>";
    echo "Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='student/dashboard.php'>Go to Student Dashboard</a></p>";
echo "<p><a href='student/admission_form.php'>Go to Admission Form</a></p>";
?>
