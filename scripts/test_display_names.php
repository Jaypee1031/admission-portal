<?php
/**
 * Test Display Names for Requirements
 */

require_once 'config/config.php';
require_once 'includes/requirements.php';

echo "<h2>Requirements Display Names Test</h2>";

// Test the requirements list
$requirements = new Requirements();
$requirementsList = $requirements->getRequirementsList('Freshman');

echo "<h3>Freshman Requirements List:</h3>";
echo "<ul>";
foreach ($requirementsList as $key => $name) {
    echo "<li><strong>$key:</strong> $name</li>";
}
echo "</ul>";

// Test with a sample student (assuming student ID 1 exists)
$studentId = 1;
$uploadedRequirements = $requirements->getStudentRequirements($studentId);

echo "<h3>Sample Student Requirements (ID: $studentId):</h3>";
if (empty($uploadedRequirements)) {
    echo "<p>No requirements found for this student.</p>";
} else {
    echo "<ul>";
    foreach ($uploadedRequirements as $req) {
        $displayName = $requirementsList[$req['document_name']] ?? $req['document_name'];
        echo "<li><strong>Database name:</strong> {$req['document_name']}</li>";
        echo "<li><strong>Display name:</strong> $displayName</li>";
        echo "<li><strong>Status:</strong> {$req['status']}</li>";
        echo "<li><strong>Uploaded:</strong> {$req['uploaded_at']}</li>";
        echo "<hr>";
    }
    echo "</ul>";
}

echo "<h3>Expected Display Names:</h3>";
echo "<ol>";
echo "<li>Certified True Copy of Grade 12 report card (1st sem)</li>";
echo "<li>Certificate of Good Moral Character</li>";
echo "<li>NSO/PSA Birth Certificate (2 photocopies)</li>";
echo "<li>Marriage Certificate (if married) (if not blank image) (2 photocopies)</li>";
echo "<li>2x2 ID picture (6pcs) – with name tag and white background</li>";
echo "<li>Long brown folder (1pc)</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
ul, ol { margin: 10px 0; }
li { margin: 5px 0; }
</style>
