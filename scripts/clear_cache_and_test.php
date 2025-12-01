<?php
/**
 * Clear Cache and Test Display Names
 */

// Clear any PHP opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p>✓ PHP opcache cleared</p>";
}

// Test the requirements display
require_once 'config/config.php';
require_once 'includes/requirements.php';

echo "<h2>Requirements Display Test</h2>";

$requirements = new Requirements();
$requirementsList = $requirements->getRequirementsList('Freshman');

echo "<h3>Current Requirements List:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Database Key</th><th>Display Name</th></tr>";
foreach ($requirementsList as $key => $name) {
    echo "<tr><td>$key</td><td>$name</td></tr>";
}
echo "</table>";

echo "<h3>Expected Order:</h3>";
echo "<ol>";
echo "<li>Certified True Copy of Grade 12 report card (1st sem)</li>";
echo "<li>Certificate of Good Moral Character</li>";
echo "<li>NSO/PSA Birth Certificate (2 photocopies)</li>";
echo "<li>Marriage Certificate (if married) (if not blank image) (2 photocopies)</li>";
echo "<li>2x2 ID picture (6pcs) – with name tag and white background</li>";
echo "<li>Long brown folder (1pc)</li>";
echo "</ol>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)</li>";
echo "<li>Visit the documents page: <a href='student/documents.php'>student/documents.php</a></li>";
echo "<li>Check if the display names are now showing correctly</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> If you still see the old names, try:</p>";
echo "<ul>";
echo "<li>Hard refresh the browser (Ctrl+F5)</li>";
echo "<li>Clear browser cache completely</li>";
echo "<li>Check if there are any server-side caching issues</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
