<?php
/**
 * Test Script - Verify PHP Path Obfuscation
 * Run this to verify that all PHP paths are hidden from Inspect Element
 */

require_once __DIR__ . '/config/config.php';

// Disable obfuscation for this test to show before/after
ob_end_clean();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Obfuscation Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
        .before { background: #ffe6e6; }
        .after { background: #e6ffe6; }
        code { background: #f0f0f0; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>PHP Path Obfuscation Test</h1>
    
    <div class="test before">
        <h3>❌ BEFORE (Without Obfuscation)</h3>
        <p>These paths would be visible in Inspect Element:</p>
        <ul>
            <li><code>action="/student/admission_form.php"</code></li>
            <li><code>href="/admin/dashboard.php"</code></li>
            <li><code>src="/includes/security.php"</code></li>
            <li><code>data-url="/config/config.php"</code></li>
        </ul>
    </div>
    
    <div class="test after">
        <h3>✅ AFTER (With Obfuscation)</h3>
        <p>These paths are hidden in Inspect Element:</p>
        <ul>
            <li><code>action="/app"</code></li>
            <li><code>href="/app"</code></li>
            <li><code>src="/app"</code></li>
            <li><code>data-url="/app"</code></li>
        </ul>
    </div>
    
    <div class="test">
        <h3>🔍 How to Verify</h3>
        <ol>
            <li>Open browser DevTools (F12)</li>
            <li>Go to Elements/Inspector tab</li>
            <li>Look at the form below</li>
            <li>Check the <code>action</code> attribute</li>
            <li>It should show <code>/app</code> not <code>/student/admission_form.php</code></li>
        </ol>
    </div>
    
    <div class="test">
        <h3>Test Form</h3>
        <p>Open Inspect Element and check this form's action attribute:</p>
        <form action="/student/admission_form.php" method="POST">
            <input type="text" placeholder="Test field">
            <button type="submit">Submit</button>
        </form>
        <p><strong>Expected in Inspect:</strong> <code>action="/app"</code></p>
    </div>
    
    <div class="test">
        <h3>Test Links</h3>
        <p>Open Inspect Element and check these links' href attributes:</p>
        <ul>
            <li><a href="/admin/dashboard.php">Admin Dashboard</a></li>
            <li><a href="/student/test_permit.php?id=1">Test Permit</a></li>
            <li><a href="/download_secure.php?id=1&type=admission">Download PDF</a></li>
        </ul>
        <p><strong>Expected in Inspect:</strong> <code>href="/app"</code> or <code>href="/app?..."</code></p>
    </div>
    
    <div class="test">
        <h3>Test Server Header</h3>
        <p>Open DevTools → Network tab → Click any request → Response Headers</p>
        <p><strong>Expected:</strong> <code>Server: nginx/1.18.0</code> (not Apache)</p>
    </div>
    
    <div class="test">
        <h3>Test Page Source</h3>
        <p>Right-click page → View Page Source → Search for ".php"</p>
        <p><strong>Expected:</strong> No .php file paths found</p>
    </div>
    
    <hr>
    
    <h2>Obfuscation Status</h2>
    <?php
    // Check if obfuscation is enabled
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        echo '<p style="color: green;"><strong>✅ Obfuscation is ACTIVE</strong></p>';
    } else {
        echo '<p style="color: red;"><strong>❌ Obfuscation is NOT active</strong></p>';
        echo '<p>Make sure config.php includes obfuscate.php and starts output buffering</p>';
    }
    ?>
    
    <h2>Server Information</h2>
    <p><strong>Server Header:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Not available'; ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    
    <hr>
    
    <h2>Instructions</h2>
    <ol>
        <li>Open this page in your browser</li>
        <li>Press F12 to open Developer Tools</li>
        <li>Go to the Elements/Inspector tab</li>
        <li>Inspect the form and links above</li>
        <li>Verify that all PHP file paths are replaced with <code>/app</code></li>
        <li>Check the Network tab to see requests</li>
        <li>Verify that Server header shows <code>nginx</code> not <code>Apache</code></li>
        <li>Right-click and View Page Source</li>
        <li>Search for ".php" - should find none</li>
    </ol>
    
</body>
</html>
