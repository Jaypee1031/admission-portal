<?php
/**
 * Database Setup Script for University Admission Portal
 * This script creates the database and imports the schema
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database_name = 'university_portal';

echo "<h2>University Admission Portal - Database Setup</h2>";
echo "<p>Setting up database...</p>";

try {
    // First, connect without specifying a database to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "<p>✓ Database '$database_name' created/verified</p>";
    
    // Select the database
    $pdo->exec("USE `$database_name`");
    echo "<p>✓ Selected database '$database_name'</p>";
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/database/university_portal.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    echo "<p>✓ Reading SQL file...</p>";
    
    $sql_content = file_get_contents($sql_file);
    
    // Split the SQL content into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^\/\*/', $stmt);
        }
    );
    
    echo "<p>✓ Found " . count($statements) . " SQL statements to execute</p>";
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Skip errors for statements that might already exist
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p>✓ Executed $executed SQL statements</p>";
    
    // Verify tables were created
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✓ Database tables created: " . implode(', ', $tables) . "</p>";
    
    // Check if admin user exists
    $admin_check = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    echo "<p>✓ Admin users in database: $admin_check</p>";
    
    // Check if students exist
    $student_check = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    echo "<p>✓ Students in database: $student_check</p>";
    
    echo "<h3 style='color: green;'>✅ Database setup completed successfully!</h3>";
    echo "<p><strong>Default Admin Login:</strong></p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: password</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Go to Application</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please check your MySQL server is running and credentials are correct.</p>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
p { margin: 10px 0; }
</style>
