<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PDF Viewer for Admission Forms - Generate PDF directly with database data
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/test_permit.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

// Get current user
$user = $auth->getCurrentUser();

// Determine which student ID to use:
// - If admin passes ?student_id=, allow viewing that student's form
// - Else if logged in student, use their own ID
// - Otherwise redirect
$studentId = null;
if (isAdmin() && isset($_GET['student_id'])) {
    $studentId = (int)$_GET['student_id'];
} elseif (isStudent()) {
    $studentId = $user['id'];
} else {
    redirect('index.php');
}

// Check if student has completed test permit (only for students, not admins)
$testPermit = new TestPermit();
if (isStudent() && !$testPermit->hasTestPermit($studentId)) {
    // Show error message and redirect
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - ' . SITE_NAME . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Access Denied
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                            <h4>Test Permit Required</h4>
                            <p class="text-muted">You need to request your test permit before you can access this PDF.</p>
                            <div class="mt-4">
                                <a href="student/test_permit.php" class="btn btn-warning me-2">
                                    <i class="fas fa-file-alt me-2"></i>Request Test Permit
                                </a>
                                <a href="student/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Get student and form data from database
$db = getDB();
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

if (!$data) {
    die('Student or form data not found');
}

// Include TCPDF autoloader
require_once 'vendor/autoload.php';

// Create new PDF document - Long Bond Paper (8.5" x 13")
$pdf = new TCPDF('P', 'mm', array(216, 330), true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Quirino State University');
$pdf->SetAuthor('OGCA');
$pdf->SetTitle('Application for Pre-Admission Form');
$pdf->SetSubject('Admission Form');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins - smaller for more content
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(false, 8);

// Add a page
$pdf->AddPage();

// Set font - Bookman Old Style for formal appearance
$pdf->SetFont('times', '', 11);

// Define colors
$lightGreen = array(179, 228, 160);

// HEADER SECTION - Logo and University Info on Top Left
// Add logo - try multiple approaches
$logoPath = 'assets/images/qsulogo.png';
if (file_exists($logoPath)) {
    // Try to add the logo with different methods
    try {
        // Method 1: Try with explicit PNG format
        $pdf->Image($logoPath, 5, 5, 20, 20, 'PNG');
    } catch (Exception $e) {
        try {
            // Method 2: Try without format specification
            $pdf->Image($logoPath, 5, 5, 20, 20);
        } catch (Exception $e2) {
            try {
                // Method 3: Try with different parameters
                $pdf->Image($logoPath, 5, 5, 20, 20, '', '', '', true, 300, '', false, false, 0, false, false, false);
            } catch (Exception $e3) {
                // Fallback: Show placeholder
                $pdf->Rect(5, 5, 20, 20, 'D');
                $pdf->SetXY(5, 12);
                $pdf->SetFont('times', 'B', 7);
                $pdf->Cell(20, 5, 'QSU LOGO', 0, 1, 'C');
            }
        }
    }
} else {
    // File doesn't exist, show placeholder
    $pdf->Rect(5, 5, 20, 20, 'D');
    $pdf->SetXY(5, 12);
    $pdf->SetFont('times', 'B', 7);
    $pdf->Cell(20, 5, 'QSU LOGO', 0, 1, 'C');
}

// University information on top left (closer to logo)
$pdf->SetXY(28, 8);
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 5, 'QUIRINO STATE UNIVERSITY', 0, 1, 'L');
$pdf->SetXY(28, 13);
$pdf->SetFont('times', '', 12);
$pdf->Cell(0, 4, 'SAS - Office of Guidance, Counseling & Admission', 0, 1, 'L');

// Add horizontal line below university information
$linePath = 'assets/images/line.png';
if (file_exists($linePath)) {
    $pdf->Image($linePath, 28, 18, 139, 2);
} else {
    // Fallback: simple line if image doesn't exist
    $pdf->SetXY(28, 18);
    $pdf->Cell(139, 0, '', 'B', 1, 'L');
}

// Form title centered
$pdf->SetXY(8, 22);
$pdf->SetFont('times', 'BU', 14);
$pdf->Cell(0, 6, 'APPLICATION FOR PRE - ADMISSION FORM', 0, 1, 'C');
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 4, '(For New Students - Undergraduate)', 0, 1, 'C');

// ID Picture - Display actual profile photo from database (bigger size)
$pdf->SetXY(175, 10);
$pdf->Rect(175, 10, 30, 30, 'D');

// Check if student has uploaded a profile photo
$profilePhoto = $data['profile_photo'] ?? '';
if (!empty($profilePhoto) && file_exists($profilePhoto)) {
    try {
        // Display the actual profile photo
        $pdf->Image($profilePhoto, 175, 10, 30, 30, '', '', '', true, 300, '', false, false, 0, false, false, false);
    } catch (Exception $e) {
        // If image fails to load, show placeholder text
        $pdf->SetFont('times', '', 9);
        $pdf->Text(178, 23, '1x1 ID Picture');
        $pdf->Text(178, 27, '(Please Attach)');
    }
} else {
    // No profile photo uploaded, show placeholder text
    $pdf->SetFont('times', '', 9);
    $pdf->Text(178, 23, '1x1 ID Picture');
    $pdf->Text(178, 27, '(Please Attach)');
}
$pdf->SetXY(7, 50);

// Student Type Checkboxes - Better positioned
$pdf->SetFont('times', '', 10);
$studentTypes = array('Incoming Freshmen', 'Transferee', 'Second Courser', 'Foreign Students');
$xPositions = array(15, 55, 85, 125); // More compact spacing for each checkbox

foreach ($studentTypes as $index => $type) {
    $xPos = $xPositions[$index];
    $pdf->SetXY($xPos, 40);
    
    // Determine if this checkbox should be checked based on database
    $isChecked = false;
    switch ($type) {
        case 'Incoming Freshmen':
            $isChecked = ($data['type'] === 'Freshman');
            break;
        case 'Transferee':
            $isChecked = ($data['type'] === 'Transferee');
            break;
        case 'Second Courser':
            $isChecked = (isset($data['second_courser']) && $data['second_courser'] == '1');
            break;
        case 'Foreign Students':
            $isChecked = (isset($data['foreign_students']) && $data['foreign_students'] == '1');
            break;
    }
    
    // Create functional TCPDF checkbox
    $pdf->CheckBox('student_type_' . $index, 3, $isChecked, array(), array(), 'OK');
    $pdf->Text($xPos + 4, 40, $type);
}
$pdf->Ln(7);

// Checkboxes are now functional and handled above

// Add spacing before PERSONAL INFORMATION section
$pdf->Ln(-2);

// PERSONAL INFORMATION Section - Times Typography
$pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2]);
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 6, 'PERSONAL INFORMATION', 1, 1, 'C', true);
$pdf->SetFont('times', '', 9);


// Name Row - Compact (Single Cell)
$fullName = trim(($data['last_name'] ?? '') . ', ' . ($data['first_name'] ?? '') . ' ' . ($data['middle_name'] ?? ''));
$pdf->Cell(115, 5, 'Name (Last, First, Middle): ' . $fullName, 1, 0, 'L');
$pdf->Cell(0, 5, 'Name Extension: ' . ($data['name_extension'] ?? ''), 1, 1, 'L');

// Sex and Gender Row - Individual cells (fitted to table width)
$sexText = 'Sex: ';
$sexText .= ($data['sex'] === 'Male') ? '[x] Male' : '[ ] Male';
$sexText .= '    ';
$sexText .= ($data['sex'] === 'Female') ? '[x] Female' : '[ ] Female';
$pdf->Cell(50, 5, $sexText, 1, 0, 'L');

$genderText = 'Gender: ';
$genderText .= ($data['gender'] === 'Masculine') ? '[x] Masculine' : '[ ] Masculine';
$genderText .= '    ';
$genderText .= ($data['gender'] === 'Feminine') ? '[x] Feminine' : '[ ] Feminine';
$genderText .= '    ';
$genderText .= ($data['gender'] === 'LGBTQ+') ? '[x] LGBTQ+' : '[ ] LGBTQ+';
if ($data['gender'] === 'LGBTQ+' && !empty($data['gender_specify'])) {
    $genderText .= ' Specify: ' . $data['gender_specify'];
}
$pdf->Cell(0, 5, $genderText, 1, 1, 'L');

// Civil Status and Spouse Name Row - Side by Side
$civilStatusText = 'Civil Status: ';
$civilStatusText .= ($data['civil_status'] === 'Single') ? '[x] Single' : '[ ] Single';
$civilStatusText .= '    ';
$civilStatusText .= ($data['civil_status'] === 'Married') ? '[x] Married' : '[ ] Married';
$civilStatusText .= '    ';
$civilStatusText .= ($data['civil_status'] === 'Separated') ? '[x] Separated' : '[ ] Separated';
$civilStatusText .= '    ';
$civilStatusText .= ($data['civil_status'] === 'Solo Parent') ? '[x] Solo Parent' : '[ ] Solo Parent';
$pdf->Cell(100, 5, $civilStatusText, 1, 0, 'L');

$pdf->Cell(0, 5, 'Name of Spouse (if married): ' . ($data['spouse_name'] ?? ''), 1, 1, 'L');

// Age, Date of Birth, Place of Birth, PWD, Disability, Ethnic Affiliation - Image Layout
// Row 1: Age, Date of Birth, Place of Birth, PWD in separate columns
$pdf->Cell(20, 5, 'Age: ' . ($data['age'] ?? ''), 1, 0, 'L');
$birthDate = !empty($data['birth_date']) ? date('m/d/Y', strtotime($data['birth_date'])) : '';
$pdf->Cell(75, 5, 'Date of Birth (mm/dd/yyyy): ' . $birthDate, 1, 0, 'L');
$pdf->Cell(65, 5, 'Place of Birth: ' . ($data['birth_place'] ?? ''), 1, 0, 'L');

$pwdText = 'PWD: ';
$pwdValue = (int)($data['pwd'] ?? 0);
$pwdText .= ($pwdValue === 0) ? '[x] No' : '[ ] No';
$pwdText .= '    ';
$pwdText .= ($pwdValue === 1) ? '[x] Yes' : '[ ] Yes';
$pdf->Cell(0, 5, $pwdText, 1, 1, 'L');

// Row 2: Ethnic Affiliation spanning first 3 columns, Disability in 4th column
$ethnic = $data['ethnic_affiliation'] ?? '';
$ethnicText = 'Ethnic Affiliation: ';
$ethnicText .= ($ethnic === 'Ilocano') ? '[x] Ilocano' : '[ ] Ilocano';
$ethnicText .= '    ';
$ethnicText .= ($ethnic === 'Igorot') ? '[x] Igorot' : '[ ] Igorot';
$ethnicText .= '    ';
$ethnicText .= ($ethnic === 'Ifugao') ? '[x] Ifugao' : '[ ] Ifugao';
$ethnicText .= '    ';
$ethnicText .= ($ethnic === 'Bisaya') ? '[x] Bisaya' : '[ ] Bisaya';
$ethnicText .= '    ';
$ethnicText .= ($ethnic === 'Others') ? '[x] Others' : '[ ] Others';
$ethnicText .= ': ';
if ($ethnic === 'Others') {
    $ethnicText .= ($data['ethnic_others_specify'] ?? '____________________');
} else {
    $ethnicText .= '____________________';
}
$pdf->Cell(160, 5, $ethnicText, 1, 0, 'L');
$pdf->Cell(0, 5, 'Disability: ' . ($data['disability'] ?? ''), 1, 1, 'L');

// Home Address Row - Single Cell
$pdf->Cell(0, 5, 'Home Address: ' . ($data['home_address'] ?? ''), 1, 1, 'L');

// Contact Information Row - 2 cells
$pdf->Cell(100, 5, 'Mobile Number: ' . ($data['mobile_number'] ?? ''), 1, 0, 'L');
$pdf->Cell(0, 5, 'Email Address: ' . ($data['email'] ?? ''), 1, 1, 'L');



// Father's Information Row - 3 Cells (Aligned with Email)
$pdf->Cell(100, 5, 'Father\'s Name: ' . ($data['father_name'] ?? 'N/A'), 1, 0, 'L');
$pdf->Cell(60, 5, 'Occupation: ' . ($data['father_occupation'] ?? 'N/A'), 1, 0, 'L');
$pdf->Cell(0, 5, 'Contact #: ' . ($data['father_contact'] ?? 'N/A'), 1, 1, 'L');

// Mother's Information Row - 3 Cells (Aligned with Email)
$pdf->Cell(100, 5, 'Mother\'s Name: ' . ($data['mother_maiden_name'] ?? 'N/A'), 1, 0, 'L');
$pdf->Cell(60, 5, 'Occupation: ' . ($data['mother_occupation'] ?? 'N/A'), 1, 0, 'L');
$pdf->Cell(0, 5, 'Contact #: ' . ($data['mother_contact'] ?? 'N/A'), 1, 1, 'L');

// Guardian's Information Row - 3 Cells (Aligned with Email)
$pdf->Cell(100, 5, 'Guardian\'s Name: ' . ($data['guardian_name'] ?? 'N/A'), 1, 0, 'L');
$pdf->Cell(60, 5, 'Occupation: ' . ($data['guardian_occupation'] ?? 'N/A'), 1, 0, 'L');
$pdf->Cell(0, 5, 'Contact #: ' . ($data['guardian_contact'] ?? 'N/A'), 1, 1, 'L');

// EDUCATIONAL BACKGROUND Section - Times Typography
$pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2]);
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 6, 'EDUCATIONAL BACKGROUND(Please do not Abbreviate/ Spell-out)', 1, 1, 'C', true);
$pdf->SetFont('times', '', 9);

// School Name Row - Single Cell
$pdf->Cell(0, 5, 'School/ University Last Attended: ' . ($data['last_school'] ?? ''), 1, 1, 'L');

// School Address and Year Row - 2 cells
$pdf->Cell(130, 5, 'School Address: ' . ($data['school_address'] ?? ''), 1, 0, 'L');
$pdf->Cell(0, 5, 'Year Last Attended (for transferee): ' . ($data['year_last_attended'] ?? ''), 1, 1, 'L');

// Year Graduated and Program/Strand Row - 2 cells
$pdf->Cell(130, 5, 'Program/Strand Taken: ' . ($data['strand_taken'] ?? ''), 1, 0, 'L');
$pdf->Cell(0, 5, 'Year Graduated (for Incoming Freshman): ' . ($data['year_graduated'] ?? ''), 1, 1, 'L');


// COURSE INTENDED TO TAKE Section - Compact
$pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2]);
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 6, 'COURSE INTENDED TO TAKE', 1, 1, 'C', true);
$pdf->SetFont('times', '', 9);

// Course Preferences - Connected 3-column row with aligned borders
$pdf->SetFont('times', '', 8);

// Get course preferences
$courseFirst = $data['course_first'] ?? '';
$courseSecond = $data['course_second'] ?? '';
$courseThird = $data['course_third'] ?? '';

// Store starting position
$startX = $pdf->GetX();
$startY = $pdf->GetY();

// Use full width like other sections (0 means full width)
$totalWidth = 0; // This will be calculated by TCPDF to match other sections
$cellWidth = 66; // Width for each column (will be adjusted proportionally)

// Calculate required height for each column
$height1 = $pdf->getStringHeight($cellWidth, '1st Preference: ' . $courseFirst);
$height2 = $pdf->getStringHeight($cellWidth, '2nd Preference: ' . $courseSecond);
$height3 = $pdf->getStringHeight($cellWidth, '3rd Preference: ' . $courseThird);

// Use the maximum height needed
$maxHeight = max($height1, $height2, $height3, 8); // Minimum 8mm

// Draw the course preferences using the same approach as other sections
// First, draw a full-width cell to establish the outer border
$pdf->Cell(0, $maxHeight, '', 1, 1, 'L');

// Reset position to start of the row
$pdf->SetXY($startX, $startY);

// Calculate the actual width used by TCPDF (same as other sections)
$actualWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
$cellWidth = $actualWidth / 3; // Divide into 3 equal columns

// Draw internal vertical lines at exact positions
$pdf->Line($startX + $cellWidth, $startY, $startX + $cellWidth, $startY + $maxHeight);
$pdf->Line($startX + ($cellWidth * 2), $startY, $startX + ($cellWidth * 2), $startY + $maxHeight);

// Now add text content without borders (since we drew them manually)
$pdf->SetXY($startX, $startY);
$pdf->MultiCell($cellWidth, 4, '1st Preference: ' . $courseFirst, 0, 'L', false, 0);

$pdf->SetXY($startX + $cellWidth, $startY);
$pdf->MultiCell($cellWidth, 4, '2nd Preference: ' . $courseSecond, 0, 'L', false, 0);

$pdf->SetXY($startX + ($cellWidth * 2), $startY);
// Add small padding to ensure text doesn't touch the right border
$pdf->MultiCell($cellWidth - 1, 4, '3rd Preference: ' . $courseThird, 0, 'L', false, 0);

// Move to next line after the course preferences
$pdf->SetY($startY + $maxHeight);

// DOCUMENTARY REQUIREMENTS Section - Compact
$pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2]);
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 6, 'DOCUMENTARY REQUIREMENTS', 1, 1, 'C', true);
$pdf->SetFont('times', '', 10);

// Requirements Table - 2 Cells in 1 Row
$pdf->SetFont('times', '', 7);

// Requirements data with headers integrated
$freshmenReqs = "Incoming Freshmen\nDuly accomplished Application for Pre - Admission Form\nCertified True Copy of Grade 12 report card (1st sem)\nCertificate of Good Moral Character\nNSO/PSA Birth Certificate (2 photocopies)\nMarriage Certificate (if married) (2 photocopies)\n2x2 ID picture (6pcs) – with name tag and white background\nLong brown folder (1pc)\n ";

$transfereeReqs = "Transferee Student\nDuly accomplished Application for Pre - Admission Form\nCertificate of Transfer Credential (original and photocopy)\nCertification of Complete Grades (original and photocopy)\nCertificate of Good Moral Character (original and photocopy)\nNSO/PSA Birth Certificate (2 photocopies)\nMarriage Certificate (if married) (2 photocopies)\n2x2 ID picture (6pcs) – with name tag and white background\nLong brown folder (1pc)";

$pdf->MultiCell(100, 5, $freshmenReqs, 1, 'L', false, 0);
$pdf->MultiCell(100, 5, $transfereeReqs, 1, 'L', false, 1);

// ADMISSION OFFICER Section - Compact
$pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2]);
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 6, 'TO BE FILLED OUT BY THE ADMISSION OFFICER', 1, 1, 'C', true);
$pdf->SetFont('times', '', 11);

// Officer Information - Two Cells
// Check if test permit is approved to fill admin information
if ($data['approved_by'] && $data['admin_name']) {
    $adminName = $data['admin_name'];
    $approvalDate = date('F d, Y', strtotime($data['approved_at']));
    
    // Create underlines that match the text length
    $nameUnderline = str_repeat('_', strlen($adminName));
    $dateUnderline = str_repeat('_', strlen($approvalDate));
    
    // Officer Information - Two Cells with filled data
    $officerInfo = "Received by: \n" . $adminName . "\nName and Signature of OGCA Personnel";
    $dateInfo = "Date of Received:\n" . $approvalDate . "\n ";

    $pdf->MultiCell(100, 5, $officerInfo, 1, 'L', false, 0);
    $pdf->MultiCell(100, 5, $dateInfo, 1, 'L', false, 1);
    
    // Add dynamic underlines for admin name and date
    $pdf->Line(10, $pdf->GetY() - 5, 30  + strlen($adminName) * 2, $pdf->GetY() - 5);
    $pdf->Line(110, $pdf->GetY() - 5, 130 + strlen($approvalDate) * 2, $pdf->GetY() - 5);
} else {
    $officerInfo = "Received by: \n___________________________________________\nName and Signature of OGCA Personnel";
    $dateInfo = "Date of Received:\n_______________________________\n ";
    
    $pdf->MultiCell(100, 5, $officerInfo, 1, 'L', false, 0);
    $pdf->MultiCell(100, 5, $dateInfo, 1, 'L', false, 1);
}

// Add more spacing before Data Privacy section
$pdf->Ln(15);

// Footer - Times Typography
$pdf->SetFont('times', '', 8);
$pdf->MultiCell(0, 4, "QSU-OGCA-F001-A\nRev. 03 (Feb. 03, 2025)", 0, 'L', false, 1);

// Data Privacy Statement and Agreement Section
$pdf->Ln(12);
$pdf->SetFont('times', 'B', 10);
$pdf->Cell(0, 5, 'DATA PRIVACY STATEMENT AND AGREEMENT (DATA PRIVACY ACT OF 2012)', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('times', '', 8);
$privacyText = "The Office of the Guidance, Counseling, and Admission (OGCA) is committed to protecting the privacy of its data subjects, and ensuring the safety and securing of personal data under its control and custody. The OGCA collects, stores and processes personal data from its current, past and prospective students starting with the information provided during application for admission.\n\n";
$privacyText .= "Furthermore, the information collected and stored by the Unit shall only be used for the following purposes: Processing of admission application and student selection, Verifying authenticity of student records and documents, Supporting the student's well-being and providing guidance counselling, Documentation of students' data, Management of data records for active population, and as Basis for further development of service protocols and guidelines.";

$pdf->MultiCell(0, 4, $privacyText, 0, 'J', false, 1);

$pdf->Ln(3);
$pdf->SetFont('times', 'B', 9);
$pdf->Cell(0, 5, 'CLIENT CONSENT:', 0, 1, 'L');

$pdf->Ln(1);
$pdf->SetFont('times', '', 8);
$consentText = "I have read the Data Privacy Statement and express my consent for the Office of Guidance, Counseling and Admission (OGCA) and its subsidiary offices and departments to collect, record, organize, update or modify, retrieve, use, consolidate, block, erase, transfer, disclose or dispose of my personal data as part of my information.\n\n";
$consentText .= "Upon signing this form, I hereby allow the OGCA to collect and process all my submitted information. This consent is hereby given on the guarantee that my rights shall be upheld at all times.";

$pdf->MultiCell(0, 3, $consentText, 0, 'J', false, 1);

$pdf->Ln(5);
$pdf->SetFont('times', '', 9);

// Get student name and submission date
$studentName = ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '');
$submissionDate = date('F d, Y', strtotime($data['created_at'] ?? 'now'));

// Signature line with student name - Bold and Underlined
$pdf->SetFont('times', 'BU', 9);
$pdf->Cell(90, 5, $studentName, 0, 0, 'C');
$pdf->Cell(0, 5, $submissionDate, 0, 1, 'C');



$pdf->SetXY(10, $pdf->GetY() + 2);
$pdf->SetFont('times', '', 8);
$pdf->Cell(90, 4, 'Signature', 0, 0, 'C');
$pdf->Cell(0, 4, 'Date', 0, 1, 'C');

// Output PDF
$mode = (isset($_GET['download']) && $_GET['download'] === '1') ? 'D' : 'I';
$pdf->Output('admission_form.pdf', $mode);
?>