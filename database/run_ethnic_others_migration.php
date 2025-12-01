<?php
// Migration script to add ethnic_others_specify field to admission_forms table
// Run this script once to update the database schema

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "Starting migration: Add ethnic_others_specify field...\n";
    
    // Check if the field already exists
    $checkField = $db->query("SHOW COLUMNS FROM admission_forms LIKE 'ethnic_others_specify'");
    if ($checkField->rowCount() > 0) {
        echo "Field 'ethnic_others_specify' already exists. Migration not needed.\n";
        exit;
    }
    
    // Add the new field
    $sql = "ALTER TABLE `admission_forms` 
            ADD COLUMN `ethnic_others_specify` varchar(255) DEFAULT NULL 
            AFTER `ethnic_affiliation`";
    
    $db->exec($sql);
    echo "✓ Added 'ethnic_others_specify' field to admission_forms table\n";
    
    // Update the ethnic_affiliation field to use proper enum values
    $sql = "ALTER TABLE `admission_forms` 
            MODIFY COLUMN `ethnic_affiliation` enum('Ilocano','Igorot','Ifugao','Bisaya','Others') DEFAULT NULL";
    
    $db->exec($sql);
    echo "✓ Updated 'ethnic_affiliation' field to use enum values\n";
    
    // Show the updated table structure
    echo "\nUpdated table structure:\n";
    $result = $db->query("DESCRIBE admission_forms");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['Field'], ['ethnic_affiliation', 'ethnic_others_specify'])) {
            echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Default']}\n";
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "The admission_forms table now supports:\n";
    echo "- ethnic_affiliation: enum('Ilocano','Igorot','Ifugao','Bisaya','Others')\n";
    echo "- ethnic_others_specify: varchar(255) for custom ethnic affiliation\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
