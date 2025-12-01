<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_permit_pdf.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get filters from URL parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'course_choice' => $_GET['course_choice'] ?? '',
    'type' => $_GET['type'] ?? '',
    'status' => $_GET['status'] ?? ''
];

try {
    $db = getDB();
    
    // Build WHERE clause based on filters
    $whereConditions = [];
    $params = [];
    
    if (!empty($filters['search'])) {
        $whereConditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR tp.permit_number LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($filters['course_choice'])) {
        $whereConditions[] = "af.course_first = ?";
        $params[] = $filters['course_choice'];
    }
    
    if (!empty($filters['type'])) {
        $whereConditions[] = "s.type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['status'])) {
        $whereConditions[] = "tp.status = ?";
        $params[] = $filters['status'];
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get all test permits with complete data
    $stmt = $db->prepare("
        SELECT tp.*, s.first_name, s.last_name, s.middle_name, s.email, s.type,
               af.course_first, af.home_address, af.mobile_number, af.last_school,
               af.father_name, af.mother_name, af.birth_date, af.sex
        FROM test_permits tp
        JOIN students s ON tp.student_id = s.id
        LEFT JOIN admission_forms af ON s.id = af.student_id
        $whereClause
        ORDER BY tp.issued_at DESC, s.last_name ASC, s.first_name ASC
    ");
    $stmt->execute($params);
    $testPermits = $stmt->fetchAll();
    
    if (empty($testPermits)) {
        die('No test permits found to download.');
    }
    
    // Generate individual PDFs and save them temporarily
    $tempDir = __DIR__ . '/../temp/test_permits_' . date('Y-m-d_H-i-s');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $generatedFiles = [];
    
    foreach ($testPermits as $permit) {
        try {
            // Create individual test permit PDF
            $pdf = new TestPermitPDF();
            
            // Prepare data arrays for the PDF generation
            $permitData = [
                'permit_number' => $permit['permit_number'],
                'exam_date' => $permit['exam_date'],
                'exam_time' => $permit['exam_time'],
                'exam_room' => $permit['exam_room'],
                'status' => $permit['status']
            ];
            
            $studentData = [
                'first_name' => $permit['first_name'],
                'last_name' => $permit['last_name'],
                'middle_name' => $permit['middle_name'],
                'email' => $permit['email'],
                'type' => $permit['type']
            ];
            
            $admissionFormData = [
                'course_first' => $permit['course_first'],
                'home_address' => $permit['home_address'],
                'mobile_number' => $permit['mobile_number'],
                'last_school' => $permit['last_school'],
                'father_name' => $permit['father_name'],
                'mother_name' => $permit['mother_name'],
                'birth_date' => $permit['birth_date'],
                'sex' => $permit['sex']
            ];
            
            $pdfInstance = $pdf->generateTestPermit($permitData, $studentData, $admissionFormData);
            
            // Generate filename
            $studentName = trim($permit['last_name'] . ', ' . $permit['first_name'] . ' ' . ($permit['middle_name'] ?? ''));
            $filename = 'Test_Permit_' . $permit['permit_number'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentName) . '.pdf';
            $filepath = $tempDir . '/' . $filename;
            
            // Get PDF content as string and save to file
            $pdfContent = $pdfInstance->Output('', 'S');
            file_put_contents($filepath, $pdfContent);
            $generatedFiles[] = $filepath;
            
        } catch (Exception $e) {
            error_log('Error generating test permit PDF for permit ' . $permit['permit_number'] . ': ' . $e->getMessage());
            continue;
        }
    }
    
    if (empty($generatedFiles)) {
        // Clean up temp directory
        rmdir($tempDir);
        die('No test permits could be generated.');
    }
    
    // Create a simple HTML page with download links for all PDFs
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download All Test Permits</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-download me-2"></i>Download All Test Permits
                        </h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Click on any test permit below to download it individually:</p>
                        <div class="list-group">';
    
    foreach ($generatedFiles as $filepath) {
        $filename = basename($filepath);
        $permitNumber = preg_replace('/Test_Permit_([^_]+)_.*/', '$1', $filename);
        $studentName = preg_replace('/Test_Permit_[^_]+_(.*)\.pdf/', '$1', $filename);
        $studentName = str_replace('_', ' ', $studentName);
        
        $relativePath = 'temp/' . basename($tempDir) . '/' . $filename;
        $html .= '<a href="../' . $relativePath . '" class="list-group-item list-group-item-action" download>
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">Test Permit #' . $permitNumber . '</h6>
                <small class="text-muted">Click to download</small>
            </div>
            <p class="mb-1">Student: ' . $studentName . '</p>
        </a>';
    }
    
    $html .= '</div>
                        <div class="mt-3">
                            <button class="btn btn-success" onclick="downloadAll()">
                                <i class="fas fa-download me-2"></i>Download All Files
                            </button>
                            <a href="test_permits.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Test Permits
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function downloadAll() {
            const links = document.querySelectorAll(\'a[download]\');
            links.forEach((link, index) => {
                setTimeout(() => {
                    link.click();
                }, index * 500); // Delay each download by 500ms
            });
        }
    </script>
</body>
</html>';
    
    // Output the HTML page
    echo $html;
    
    exit;
    
} catch (Exception $e) {
    error_log('Error in download_all_test_permits.php: ' . $e->getMessage());
    die('An error occurred while generating the download. Please try again.');
}
?>
