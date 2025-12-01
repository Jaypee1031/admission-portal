<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/pdf_favicon_helper.php';

class TestPermitPDF {
    private $pdf;
    private $adminData = null;
    
    public function __construct() {
        $this->pdf = new TCPDF('P', 'mm', array(216, 330), true, 'UTF-8', false);
        $this->setupPDF();
    }
    
    private function setupPDF() {
        // Set document information
        $this->pdf->SetCreator('Quirino State University');
        $this->pdf->SetAuthor('OGCA');
        $this->pdf->SetTitle('Test Permit (F4)');
        $this->pdf->SetSubject('Entrance Examination Permit');
        $this->pdf->SetKeywords('QSU, Test Permit, Admission, Entrance Exam');
        
        // Set PDF metadata with favicon information
        PDFFaviconHelper::setPDFMetadata($this->pdf, 'Test Permit (F4)', 'Entrance Examination Permit', 'QSU, Test Permit, Admission, Entrance Exam');
        
        // Remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Set margins - smaller for more content
        $this->pdf->SetMargins(8, 8, 8);
        $this->pdf->SetAutoPageBreak(false, 8);
        
        // Add a page
        $this->pdf->AddPage();
        
        // Add broken line in the center of the page (half of long bond paper)
        $this->addCenterCutLine();
        
        // Set font - Bookman Old Style for formal appearance
        $this->pdf->SetFont('times', '', 11);
    }
    
    public function generateTestPermit($permitData, $studentData, $admissionFormData = null, $idPictureData = null, $adminData = null) {
        // Store admin data for later use when rendering sections
        $this->adminData = $adminData;
        // First copy (top half)
        $this->addHeader($idPictureData);
        $this->addStudentInfo($studentData, $permitData);
        $this->addExamDetails($permitData, $studentData);
        
        // Second copy (bottom half) - duplicate below cut line
        $this->addHeader($idPictureData, 175); // Offset by 170mm (below cut line)
        $this->addStudentInfo($studentData, $permitData, 175);
        $this->addExamDetails($permitData, $studentData, 175, true); // true = Counselor's Copy
        
        // Footer
        $this->addFooter();
        
        return $this->pdf;
    }
    
    private function addHeader($idPictureData = null, $yOffset = 0) {
        // Logo (if available and valid) - F4 format positioning
        $logoPath = __DIR__ . '/../assets/images/qsulogo.png';
        if (file_exists($logoPath)) {
            try {
                $this->pdf->Image($logoPath, 5, 5 + $yOffset, 20, 20, 'PNG');
            } catch (Exception $e) {
                try {
                    $this->pdf->Image($logoPath, 5, 5 + $yOffset, 20, 20);
                } catch (Exception $e2) {
                    try {
                        $this->pdf->Image($logoPath, 5, 5 + $yOffset, 20, 20, '', '', '', true, 300, '', false, false, 0, false, false, false);
                    } catch (Exception $e3) {
                        // Fallback: Show placeholder
                        $this->pdf->Rect(5, 5 + $yOffset, 20, 20, 'D');
                        $this->pdf->SetXY(5, 12 + $yOffset);
                        $this->pdf->SetFont('times', 'B', 7);
                        $this->pdf->Cell(20, 5, 'QSU LOGO', 0, 1, 'C');
                    }
                }
            }
        } else {
            // File doesn't exist, show placeholder
            $this->pdf->Rect(5, 5 + $yOffset, 20, 20, 'D');
            $this->pdf->SetXY(5, 12 + $yOffset);
            $this->pdf->SetFont('times', 'B', 7);
            $this->pdf->Cell(20, 5, 'QSU LOGO', 0, 1, 'C');
        }
        
        // Add favicon next to logo
        PDFFaviconHelper::addFaviconToPDF($this->pdf, 'assets/images/favicon_io/favicon-32x32.png', 27, 5 + $yOffset, 6);
        
        // University information - F4 format
        $this->pdf->SetXY(28, 8 + $yOffset);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 5, 'QUIRINO STATE UNIVERSITY', 0, 1, 'L');
        $this->pdf->SetXY(28, 13 + $yOffset);
        $this->pdf->SetFont('times', '', 12);
        $this->pdf->Cell(0, 4, 'SAS - Office of Guidance, Counseling & Admission', 0, 1, 'L');
        
        // Add horizontal line below university information
        $linePath = __DIR__ . '/../assets/images/line.png';
        if (file_exists($linePath)) {
            $this->pdf->Image($linePath, 28, 18 + $yOffset, 120, 2);
        } else {
            // Fallback: simple line if image doesn't exist
            $this->pdf->SetX(28);
            $this->pdf->Cell(120, 0, '', 'B', 1, 'L');
        }
        
        // Form title centered - F4 format
        $this->pdf->SetXY(8, 22 + $yOffset);
        $this->pdf->SetFont('times', 'BU', 14);
        $this->pdf->Cell(0, 6, 'TEST PERMIT', 0, 1, 'C');
        
        // 2x2 ID Picture box on the right top - F4 format with dark blue border
        $this->pdf->SetXY(150, 10 + $yOffset);
        $this->pdf->SetDrawColor(0, 0, 139); // Dark blue border
        $this->pdf->SetLineWidth(1);
        $this->pdf->Rect(150, 10 + $yOffset, 45, 35, 'D');
        
        // Check if student has uploaded a profile photo (same as view_pdf.php)
        $profilePhoto = $idPictureData['profile_photo'] ?? '';
        if (!empty($profilePhoto) && file_exists($profilePhoto)) {
            try {
                // Display the actual profile photo (same as view_pdf.php)
                $this->pdf->Image($profilePhoto, 150, 10 + $yOffset, 45, 35, '', '', '', true, 300, '', false, false, 0, false, false, false);
            } catch (Exception $e) {
                // If image fails to load, show placeholder text
                $this->pdf->SetFont('times', '', 9);
                $this->pdf->SetXY(150, 25 + $yOffset);
                $this->pdf->Cell(45, 5, '2x2 ID Picture', 0, 1, 'C');
            }
        } else {
            // No profile photo uploaded, show placeholder text
            $this->pdf->SetFont('times', '', 9);
            $this->pdf->SetXY(150, 25 + $yOffset);
            $this->pdf->Cell(45, 5, '2x2 ID Picture', 0, 1, 'C');
        }
        
        $this->pdf->Ln(5);
    }
    
    private function addStudentInfo($studentData, $permitData, $yOffset = 0) {
        // Get data from test_permits table and students table
        $examineeNo = $permitData['permit_number'] ?? '';
        $studentName = ($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? '');
        $requestDate = date('M d, Y', strtotime($permitData['issued_at'] ?? 'now'));
        
        // Examinee No. and Date (top section) - with actual data and underlines
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->SetXY(15, 35 + $yOffset);
        $this->pdf->Cell(35, 5, 'Examinee No:', 0, 0, 'L');
        $this->pdf->SetFont('times', 'BU', 11); // Bold and Underline
        $this->pdf->Cell(50, 5, $examineeNo, 0, 0, 'L');
        
        $this->pdf->SetXY(100, 35 + $yOffset);
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Cell(15, 5, 'Date:', 0, 0, 'L');
        $this->pdf->SetFont('times', 'BU', 11); // Bold and Underline
        $this->pdf->Cell(30, 5, $requestDate, 0, 1, 'L');
        
        // Name - with actual student name and underline
        $this->pdf->SetXY(15, 42 + $yOffset);
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Cell(15, 5, 'Name:', 0, 0, 'L');
        $this->pdf->SetFont('times', 'BU', 11); // Bold and Underline
        $this->pdf->Cell(125, 5, $studentName, 0, 1, 'L');
        
        $this->pdf->Ln(3);
    }
    
    private function addExamDetails($permitData, $studentData, $yOffset = 0, $isCounselorCopy = false) {
        // Main content box with thick border - F4 format
        $this->pdf->SetXY(15, 45 + $yOffset);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(1.5);
        $this->pdf->Rect(15, 48 + $yOffset, 170, 75);
        
        // Salutation - F4 format with student name and underline
        $this->pdf->SetXY(20, 50 + $yOffset);
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->Cell(0, 5, 'Dear Mr./Ms. ', 0, 0, 'L');
        
        // Get current X position after "Dear Mr./Ms. "
        $nameStartX = $this->pdf->GetX();
        $studentName = $studentData['first_name'] . ' ' . $studentData['last_name'];
        
        // Display student name on the same line with bold underline
        $this->pdf->SetXY(50, 50 + $yOffset);
        $this->pdf->SetFont('times', 'BU', 10); // Bold and Underline font
        $this->pdf->Cell(0, 5, $studentName, 0, 0, 'L');
        
        // Main message - F4 format with proper line breaks
        $this->pdf->SetXY(20, 57 + $yOffset);
        $this->pdf->SetFont('times', '', 10); // Regular font (no underline)
        $this->pdf->Cell(0, 5, 'We are happy to inform you that your schedule to take the', 0, 1, 'L');
        
        $this->pdf->SetXY(20, 64 + $yOffset);
        $this->pdf->SetFont('times', 'B', 10);
        $this->pdf->Cell(0, 5, 'Quirino State University College Admission Test', 0, 1, 'L');
        
        $this->pdf->SetXY(20, 71 + $yOffset);
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->Cell(35, 5, 'will be on ', 0, 0, 'L');
        
        // Get exam date and time from permit data
        $examDate = date('M d, Y', strtotime($permitData['exam_date'] ?? ''));
        $examTime = $permitData['exam_time'] ?? '';
        
        // Format exam time for display
        $formattedTime = '';
        if ($examTime) {
            $timeObj = DateTime::createFromFormat('H:i:s', $examTime);
            if ($timeObj) {
                $formattedTime = $timeObj->format('g:i A');
            }
        }
        
        // Display exam date with bold underline
        $this->pdf->SetFont('times', 'BU', 10);
        $this->pdf->Cell(50, 5, $examDate, 0, 0, 'L');
        
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->Cell(15, 5, ' at ', 0, 0, 'L');
        
        // Display exam time with bold underline
        $this->pdf->SetFont('times', 'BU', 10);
        $this->pdf->Cell(30, 5, $formattedTime, 0, 1, 'L');
        
        $this->pdf->SetXY(20, 78 + $yOffset);
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->Cell(40, 5, 'to be held at ', 0, 0, 'L');
        
        // Get exam room from permit data
        $examRoom = $permitData['exam_room'] ?? '';
        $roomDisplay = '';
        if ($examRoom === 'qsu_student_center') {
            $roomDisplay = 'QSU Student Center - Testing room';
        } else {
            $roomDisplay = $examRoom;
        }
        
        // Display exam room with bold underline
        $this->pdf->SetFont('times', 'BU', 10);
        $this->pdf->Cell(100, 5, $roomDisplay, 0, 0, 'L');
        
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->Cell(0, 5, '.', 0, 1, 'L');
        
        // Required items section - F4 format (compact with text wrapping)
        $this->pdf->SetXY(20, 85 + $yOffset);
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->MultiCell(150, 3, 'Please bring this TEST PERMIT and the following on the day of your examination:', 0, 'L');
        
        // Checklist items - F4 format (compact)
        $this->pdf->SetXY(25, 92 + $yOffset);
        $this->pdf->SetTextColor(0, 128, 0); // Green color
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(0, 3, '✓ Black pen, pencil, Eraser', 0, 1, 'L');
        
        $this->pdf->SetXY(25, 96 + $yOffset);
        $this->pdf->Cell(0, 3, '✓ Wear your School ID/Valid ID', 0, 1, 'L');
        
        $this->pdf->SetXY(30, 100 + $yOffset);
        $this->pdf->SetFont('times', '', 7);
        $this->pdf->SetTextColor(255, 0, 0); // Red color
        $this->pdf->Cell(0, 2, '(Tin Id, Driver\'s License, PhilHealth, Postal, National ID)', 0, 1, 'L');
        
        // Admission Staff signature - F4 format (compact)
        $this->pdf->SetXY(20, 110 + $yOffset);
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        $this->pdf->SetFont('times', 'B', 9); // Bold font for underlines
        
        // Get admin name from the stored admin data
        $adminName = $this->adminData['full_name'] ?? 'N/A';
        $this->pdf->Cell(0, 3, 'Admission Staff: ' . $adminName, 0, 1, 'L');
        
        // Important Note - F4 format (right side, aligned with Admission Staff) - smaller
        $this->pdf->SetXY(100, 110 + $yOffset);
        
        // Create note box with light blue background - smaller
        $this->pdf->SetFillColor(173, 216, 230); // Light blue background
        $this->pdf->SetDrawColor(200, 200, 200); // Light gray border
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Rect(100, 110 + $yOffset, 65, 12); // Smaller box (65x12 instead of 75x15)
        
        // Note text with proper formatting - smaller
        $this->pdf->SetXY(102, 111.5 + $yOffset);
        $this->pdf->SetFont('times', 'B', 6); // Smaller font (6pt instead of 7pt)
        $this->pdf->SetTextColor(0, 0, 139); // Dark blue text
        $this->pdf->Cell(0, 2, 'Note:', 0, 1, 'L');
        
        $this->pdf->SetXY(102, 113.5 + $yOffset);
        $this->pdf->SetFont('times', 'I', 4); // Even smaller italic text (4pt instead of 5pt)
        $this->pdf->SetTextColor(0, 0, 139); // Dark blue text
        $noteText = "Changing the attached picture or altering any information found on this permit disqualifies you to take the examination. Read carefully and follow what is written.";
        $this->pdf->MultiCell(61, 1.5, $noteText, 0, 'L', false, 1); // Smaller MultiCell (61mm width, 1.5mm line height)
        
        // Document code and revision (below main box, black color) - adjusted for top half
        $this->pdf->SetXY(15, 125 + $yOffset);
        $this->pdf->SetTextColor(0, 0, 0); // Black color
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(0, 4, 'QSU-OGCA-F004', 0, 1, 'L');
        $this->pdf->SetXY(15, $this->pdf->GetY());
        $this->pdf->Cell(0, 4, 'Rev. 02 (Feb. 03, 2025)', 0, 1, 'L');
        
        // Student's Copy box (smaller, just below main box) - adjusted for top half
        $this->pdf->SetXY(100, 125 + $yOffset);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(1);
        $this->pdf->Rect(100, 125 + $yOffset, 60, 8);
        
        $this->pdf->SetXY(100, 127 + $yOffset);
        $this->pdf->SetFont('times', 'B', 8);
        $copyText = $isCounselorCopy ? 'Counselor\'s Copy' : 'Student\'s Copy';
        $this->pdf->Cell(60, 4, $copyText, 0, 1, 'C');
        
        $this->pdf->Ln(3);
    }
    
    private function addCenterCutLine() {
        // Calculate center of the page (half of long bond paper height)
        $centerY = 165; // Half of 330mm
        
        // Set line style for broken/dashed line
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.5);
        
        // Create dashed line across the page
        $dashLength = 3; // Length of each dash
        $gapLength = 2;  // Length of each gap
        $x = 8; // Start from left margin
        $endX = 208; // End at right margin (216 - 8)
        
        while ($x < $endX) {
            $this->pdf->Line($x, $centerY, $x + $dashLength, $centerY);
            $x += $dashLength + $gapLength;
        }
        
        // Add "CUT HERE" text in the center
        $this->pdf->SetXY(0, $centerY - 2);
        $this->pdf->SetFont('times', 'B', 8);
        $this->pdf->Cell(0, 4, 'CUT HERE', 0, 1, 'C');
    }
  
    
    private function addFooter() {
        // Footer content moved to addExamDetails method
        // This method is kept for compatibility but is now empty
    }
    
    private function isValidImage($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }
        
        $allowedTypes = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF];
        return in_array($imageInfo[2], $allowedTypes);
    }
}
?>
