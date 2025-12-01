<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/pdf_favicon_helper.php';

class CATResultPDF {
    private $pdf;
    
    public function __construct() {
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->setupPDF();
    }
    
    private function setupPDF() {
        // Set document information
        $this->pdf->SetCreator('Quirino State University');
        $this->pdf->SetAuthor('Office of Guidance, Counseling and Admission');
        $this->pdf->SetTitle('College Admission Test Result');
        $this->pdf->SetSubject('CAT Result Report 2026');
        $this->pdf->SetKeywords('QSU, CAT, Test Result, Admission, Entrance Exam');
        
        // Set PDF metadata with favicon information
        PDFFaviconHelper::setPDFMetadata($this->pdf, 'College Admission Test Result', 'CAT Result Report 2026', 'QSU, CAT, Test Result, Admission, Entrance Exam');
        
        // Remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Set margins
        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetAutoPageBreak(TRUE, 10);
        
        // Set default font to Calibri (or Arial as fallback)
        $this->pdf->SetFont('helvetica', '', 11); // Helvetica is closest to Calibri in TCPDF
        
        // Set text color to #203764 (dark blue)
        $this->pdf->SetTextColor(32, 55, 100); // RGB values for #203764
    }
    
    public function generateCATResult($resultData) {
        // Add a new page for this result
        $this->pdf->AddPage();
        
        // Everything in ONE single box - header, content, everything
        $this->addCompleteFormBox($resultData);
        
        return $this->pdf;
    }
    
    private function addSeparateHeader() {
        // QSU Logo (left side) - same as Personal Data form
        $logoPath = __DIR__ . '/../assets/images/qsulogo.png';
        if (file_exists($logoPath)) {
            try {
                $this->pdf->Image($logoPath, 15, 10, 20, 20, 'PNG');
            } catch (Exception $e) {
                try {
                    $this->pdf->Image($logoPath, 15, 10, 20, 20);
                } catch (Exception $e2) {
                    // Fallback: Show placeholder
                    $this->pdf->Rect(15, 10, 20, 20, 'D');
                    $this->pdf->SetXY(15, 17);
                    $this->pdf->SetFont('times', 'B', 7);
                    $this->pdf->Cell(20, 5, 'QSU LOGO', 0, 1, 'C');
                }
            }
        }
        
        // University information (next to logo) - same as Personal Data form
        $this->pdf->SetXY(38, 13);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 5, 'QUIRINO STATE UNIVERSITY', 0, 1, 'L');
        
        $this->pdf->SetXY(38, 18);
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Cell(0, 4, 'SAS - Office of Guidance, Counseling and Admission', 0, 1, 'L');
        
        // Underline directly below the second line of text - aligned with the text
        $this->pdf->SetDrawColor(0, 0, 0); // Set line color to black
        $this->pdf->Line(38, 22, 195, 22); // Line positioned directly below the text, aligned with text start
        
        $this->pdf->SetY(30); // Set position for the form box
    }
    
    private function addCompleteFormBox($data) {
        // Create ONE box containing EVERYTHING - header, logo, content, all of it
        $boxStartY = 20; // Start from top of page
        
        // Draw ONE single main container box with rounded corners (like the image)
        $this->pdf->SetDrawColor(32, 55, 100); // #203764 color for border
        $this->pdf->SetLineWidth(1.0);
        $this->pdf->RoundedRect(10, $boxStartY, 190, 250, 3.5, '1111'); // Rounded corners like image
        
        // Set starting position inside the box
        $currentY = $boxStartY + 8;
        
        // QSU Header INSIDE the box
        $logoPath = __DIR__ . '/../assets/images/qsulogo.png';
        if (file_exists($logoPath)) {
            $this->pdf->Image($logoPath, 15, $currentY, 20, 20); // Logo inside box
        }
        
        // University Header Text inside the box - BLACK COLOR
        $this->pdf->SetTextColor(0, 0, 0); // Set to black for header
        $this->pdf->SetXY(40, $currentY);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 6, 'QUIRINO STATE UNIVERSITY', 0, 1, 'L');
        
        $this->pdf->SetXY(40, $currentY + 6);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 5, 'SAS - Office of Guidance, Counseling and Admission', 0, 1, 'L');
        
        // Reset to #203764 color for the rest of the content
        $this->pdf->SetTextColor(32, 55, 100);
        
        // Add horizontal line below university information
        $linePath = __DIR__ . '/../assets/images/line.png';
        if (file_exists($linePath)) {
            $this->pdf->Image($linePath, 40, $currentY + 13, 100, 2);
        } else {
            // Fallback: simple line if image doesn't exist
            $this->pdf->SetXY(40, $currentY + 13);
            $this->pdf->Cell(100, 0, '', 'B', 1, 'L');
        }
        
        $currentY += 25;
        
        // Top right info boxes (Examinee No. and Date of Exam)
        $this->pdf->SetXY(140, $currentY);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetDrawColor(32, 55, 100); // #203764 color for info boxes
        $this->pdf->SetLineWidth(0.3);
        
        // Examinee No. box
        $this->pdf->Cell(25, 6, 'Examinee No.', 1, 0, 'L');
        $this->pdf->Cell(25, 6, $data['student_id'] ?? '', 1, 1, 'C');
        
        // Date of Exam box
        $this->pdf->SetXY(140, $currentY + 6);
        $this->pdf->Cell(25, 6, 'Date of Exam', 1, 0, 'L');
        $this->pdf->Cell(25, 6, date('m/d/Y', strtotime($data['permit_exam_date'] ?? $data['exam_date'] ?? 'now')), 1, 1, 'C');
        
        $currentY += 20;

        // COLLEGE ADMISSION TEST (CAT) Title - matching image style
        $this->pdf->SetXY(15, $currentY);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 8, 'COLLEGE ADMISSION TEST (CAT)', 0, 1, 'L');
        $currentY += 15;

        // Single rounded box containing both name and course with dividing line - SMALLER SIZE
        $this->pdf->SetDrawColor(32, 55, 100); // #203764 color for input box
        $this->pdf->SetLineWidth(0.8);
        $this->pdf->RoundedRect(15, $currentY, 150, 22, 3, '1111'); // Smaller rounded box for both fields
        
        // Name (top part)
        $fullName = trim(($data['last_name'] ?? '') . ', ' . ($data['first_name'] ?? '') . ' ' . ($data['middle_name'] ?? ''));
        $this->pdf->SetXY(18, $currentY + 4);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 4, strtoupper($fullName), 0, 1, 'L');
        
        // Dividing line between name and course - ADJUSTED FOR SMALLER BOX
        $this->pdf->SetDrawColor(32, 55, 100); // #203764 color for dividing line
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Line(18, $currentY + 11, 162, $currentY + 11); // Horizontal dividing line (smaller)
        
        // Course (bottom part) - ADJUSTED FOR SMALLER BOX
        $this->pdf->SetXY(18, $currentY + 14);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 4, $data['course_first'] ?? '', 0, 1, 'L');
        
        $currentY += 30;
        
        // Subjects table - aligned with the rounded box above
        $this->pdf->SetXY(15, $currentY);
        $this->pdf->SetDrawColor(32, 55, 100); // #203764 color for table borders
        $this->pdf->SetLineWidth(0.3);
        
        // Table headers - ALIGNED WITH SMALLER ROUNDED BOX (150px total width)
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(75, 8, 'SUBJECTS', 1, 0, 'C');
        $this->pdf->Cell(37, 8, 'RELATIVE WEIGHT', 1, 0, 'C');
        $this->pdf->Cell(38, 8, 'RATINGS', 1, 1, 'C');
        $currentY += 8;
        
        // Subject rows - WIDER COLUMNS
        $this->pdf->SetFont('helvetica', '', 9);
        
        // Ensure we're using transmuted scores (0-100 scale), not weighted scores
        // Transmuted scores should be values like 79, 82, 75, 64, etc.
        // NOT weighted scores like 7.867, 12.360, 18.667, etc.
        $subjects = [
            ['name' => '1. GENERAL INFORMATION', 'weight' => '10%', 'rating' => number_format(floatval($data['gen_info_transmuted'] ?? 0), 0)],
            ['name' => '2. FILIPINO', 'weight' => '15%', 'rating' => number_format(floatval($data['filipino_transmuted'] ?? 0), 0)],
            ['name' => '3. ENGLISH', 'weight' => '25%', 'rating' => number_format(floatval($data['english_transmuted'] ?? 0), 0)],
            ['name' => '4. SCIENCE', 'weight' => '25%', 'rating' => number_format(floatval($data['science_transmuted'] ?? 0), 0)],
            ['name' => '5. MATHEMATICS', 'weight' => '25%', 'rating' => number_format(floatval($data['math_transmuted'] ?? 0), 0)]
        ];
        
        foreach ($subjects as $subject) {
            $this->pdf->SetXY(15, $currentY);
            $this->pdf->Cell(75, 7, $subject['name'], 1, 0, 'L');
            $this->pdf->Cell(37, 7, $subject['weight'], 1, 0, 'C');
            $this->pdf->Cell(38, 7, $subject['rating'], 1, 1, 'C');
            $currentY += 7;
        }
        
        // Overall Average Rating and General Weighted Average rows - SMALLER ALIGNED
        $this->pdf->SetXY(15, $currentY);
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(75, 7, 'OVERALL AVERAGE RATING', 1, 0, 'L');
        $this->pdf->Cell(37, 7, '50%', 1, 0, 'C');
        $this->pdf->Cell(38, 7, number_format($data['exam_rating'] ?? 0, 0), 1, 1, 'C');
        $currentY += 7;
        
        $this->pdf->SetXY(15, $currentY);
        $this->pdf->Cell(75, 7, 'GENERAL WEIGHTED AVERAGE (SHS)', 1, 0, 'L');
        $this->pdf->Cell(37, 7, '', 1, 0, 'C');
        $this->pdf->Cell(38, 7, number_format($data['f2_gwa'] ?? 0, 2), 1, 1, 'C');
        $currentY += 10;
        
        // IMPORTANT section
        $this->pdf->SetXY(15, $currentY);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, 'IMPORTANT:', 0, 1, 'L');
        $currentY += 8;
        
        $this->pdf->SetFont('helvetica', '', 8);
        $importantText = [
            '1. The result is valid only if there is any alteration.',
            '2. To qualify, based programs, the examinee must have an average rating of at least 75%. In the formation',
            'of the final score a 50% of the total is assigned to the entrance test score and Mathematics',
            'Test. Student Handbook 2017 Edition)'
        ];
        
        foreach ($importantText as $text) {
            $this->pdf->SetXY(15, $currentY);
            $this->pdf->Cell(0, 4, $text, 0, 1, 'L');
            $currentY += 5;
        }
        
        $currentY += 10;
        
        // Certification section
        $this->pdf->SetXY(15, $currentY);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell(0, 5, 'Certified True and Correct by:', 0, 1, 'L');
        $currentY += 15;
        
        // Compact signature section
        $pageWidth = 186; // Inner box width
        $leftMargin = 15;
        $signatureWidth = 70;
        $dateWidth = 70;
        $spacing = 8;
        
        // Calculate center positions
        $totalWidth = $signatureWidth + $spacing + $dateWidth;
        $startX = $leftMargin + ($pageWidth - $totalWidth) / 2;
        
        // Prepare student full name and processing date
        $studentFullName = trim(($data['last_name'] ?? '') . ', ' . ($data['first_name'] ?? '') . ' ' . ($data['middle_name'] ?? ''));
        $processingDate = date('m/d/Y', strtotime($data['processed_at'] ?? 'now'));
        
        // Names above the lines (compact spacing)
        $this->pdf->SetXY($startX, $currentY);
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->Cell($signatureWidth, 4, ($data['processed_by_name'] ?? 'System Administrator'), 0, 0, 'C');
        $this->pdf->SetXY($startX + $signatureWidth + $spacing, $currentY);
        $this->pdf->Cell($dateWidth, 4, strtoupper($studentFullName), 0, 1, 'C');
        $currentY += 5;
        
        // Signature lines (compact)
        $this->pdf->SetXY($startX, $currentY);
        $this->pdf->Cell($signatureWidth, 4, '________________________________', 0, 0, 'C');
        $this->pdf->SetXY($startX + $signatureWidth + $spacing, $currentY);
        $this->pdf->Cell($dateWidth, 4, '________________________________', 0, 1, 'C');
        $currentY += 5;
        
        // Titles below the lines (compact spacing)
        $this->pdf->SetXY($startX, $currentY);
        $this->pdf->Cell($signatureWidth, 4, 'Psychometrician', 0, 0, 'C');
        $this->pdf->SetXY($startX + $signatureWidth + $spacing, $currentY);
        $this->pdf->Cell($dateWidth, 4, $processingDate, 0, 1, 'C');
        
        // Processing information at bottom of the box
        $currentY += 15;
        $this->pdf->SetXY(15, $currentY);
        $this->pdf->SetFont('helvetica', '', 7);
        $this->pdf->Cell(0, 3, 'Processed by: ' . ($data['processed_by_name'] ?? 'System Administrator'), 0, 1, 'L');
        $this->pdf->SetXY(15, $currentY + 3);
        $this->pdf->Cell(0, 3, 'Generated on: ' . date('M d, Y H:i'), 0, 1, 'L');
    }
    
    private function addApplicantInfo($data) {
        // Applicant Information Box - Excel style with thin gridlines
        $this->pdf->SetDrawColor(200, 200, 200); // Light gray for gridlines
        $this->pdf->SetLineWidth(0.1); // Thin gridlines
        $this->pdf->Rect(10, 70, 190, 35);
        
        // Header with light gray background
        $this->pdf->SetFillColor(240, 240, 240); // Light gray background
        $this->pdf->SetXY(10, 70);
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(190, 8, 'APPLICANT INFORMATION', 1, 1, 'C', true);
        
        $this->pdf->SetFillColor(255, 255, 255); // White background for data
        $this->pdf->SetFont('helvetica', '', 11);
        
        // Row 1
        $this->pdf->SetXY(15, 80);
        $this->pdf->Cell(20, 6, 'No.:', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(40, 6, $data['student_id'] ?? 'N/A', 0, 0, 'L');
        
        $this->pdf->SetFont('helvetica', '', 11);
        $this->pdf->SetXY(120, 80);
        $this->pdf->Cell(30, 6, 'Permit Number:', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 6, $data['permit_number'] ?? 'N/A', 0, 1, 'L');
        
        // Row 2
        $this->pdf->SetFont('helvetica', '', 11);
        $this->pdf->SetXY(15, 86);
        $this->pdf->Cell(30, 6, 'Student Name:', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', 'B', 11);
        $fullName = trim(($data['last_name'] ?? '') . ', ' . ($data['first_name'] ?? '') . ' ' . ($data['middle_name'] ?? ''));
        $this->pdf->Cell(0, 6, strtoupper($fullName), 0, 1, 'L');
        
        // Row 3
        $this->pdf->SetFont('helvetica', '', 11);
        $this->pdf->SetXY(15, 92);
        $this->pdf->Cell(40, 6, 'School Last Attended:', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 6, strtoupper($data['last_school'] ?? 'N/A'), 0, 1, 'L');
        
        // Row 4
        $this->pdf->SetFont('helvetica', '', 11);
        $this->pdf->SetXY(15, 98);
        $this->pdf->Cell(30, 6, 'Course Taken:', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 6, $data['course_first'] ?? 'N/A', 0, 1, 'L');
        
        $this->pdf->Ln(8);
    }
    
    private function addExamScoresTable($data) {
        $startY = $this->pdf->GetY() + 5;
        
        // Exam Scores Header - Excel style
        $this->pdf->SetXY(15, $startY);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'SOLUTION PROCESS - HOW SCORES WERE COMPUTED', 0, 1, 'C');
        $this->pdf->Ln(3);
        
        // Excel-style table with proper formatting
        $this->pdf->SetDrawColor(200, 200, 200); // Light gray gridlines
        $this->pdf->SetLineWidth(0.1); // Thin gridlines
        
        // Header row with light gray background - Excel style
        $this->pdf->SetFillColor(240, 240, 240); // Light gray background
        $this->pdf->SetFont('helvetica', 'B', 11); // Calibri Bold 11pt equivalent
        
        $this->pdf->Cell(40, 8, 'Subject', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'Raw Score', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'Max Points', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Transmuted', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'Weight %', 1, 0, 'C', true);
        $this->pdf->Cell(35, 8, 'Weighted Score', 1, 1, 'C', true);
        
        // Data rows - white background, regular font
        $this->pdf->SetFillColor(255, 255, 255); // White background
        $this->pdf->SetFont('helvetica', '', 11); // Calibri Regular 11pt equivalent
        
        // Subject rows
        $subjects = [
            ['name' => 'General Information', 'raw' => $data['gen_info_raw'] ?? 0, 'max' => 30, 'transmuted' => $data['gen_info_transmuted'] ?? 0, 'weight' => '10%'],
            ['name' => 'Filipino', 'raw' => $data['filipino_raw'] ?? 0, 'max' => 50, 'transmuted' => $data['filipino_transmuted'] ?? 0, 'weight' => '15%'],
            ['name' => 'English', 'raw' => $data['english_raw'] ?? 0, 'max' => 60, 'transmuted' => $data['english_transmuted'] ?? 0, 'weight' => '25%'],
            ['name' => 'Science', 'raw' => $data['science_raw'] ?? 0, 'max' => 60, 'transmuted' => $data['science_transmuted'] ?? 0, 'weight' => '25%'],
            ['name' => 'Mathematics', 'raw' => $data['math_raw'] ?? 0, 'max' => 50, 'transmuted' => $data['math_transmuted'] ?? 0, 'weight' => '25%']
        ];
        
        $weightValues = [0.10, 0.15, 0.25, 0.25, 0.25];
        
        foreach ($subjects as $index => $subject) {
            $weightedScore = $subject['transmuted'] * $weightValues[$index];
            
            // Left-aligned text, centered numbers - Excel style
            $this->pdf->Cell(40, 7, $subject['name'], 1, 0, 'L', true);
            $this->pdf->Cell(25, 7, $subject['raw'], 1, 0, 'C', true);
            $this->pdf->Cell(25, 7, $subject['max'], 1, 0, 'C', true);
            $this->pdf->Cell(30, 7, number_format($subject['transmuted'], 2), 1, 0, 'C', true);
            $this->pdf->Cell(25, 7, $subject['weight'], 1, 0, 'C', true);
            $this->pdf->Cell(35, 7, number_format($weightedScore, 3), 1, 1, 'C', true);
        }
        
        // Total row - Bold, centered - Excel style
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(40, 7, 'TOTAL', 1, 0, 'C', true);
        $this->pdf->Cell(25, 7, $data['raw_score'] ?? 0, 1, 0, 'C', true);
        $this->pdf->Cell(25, 7, '250', 1, 0, 'C', true);
        $this->pdf->Cell(30, 7, '–', 1, 0, 'C', true);
        $this->pdf->Cell(25, 7, '100%', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, number_format($data['exam_rating'] ?? 0, 3), 1, 1, 'C', true);
        
        $this->pdf->Ln(5);
        
        // Exam Rating explanation
        $this->pdf->SetFont('helvetica', '', 11);
        $this->pdf->Cell(0, 6, 'Thus the Exam Rating = ' . number_format($data['exam_rating'] ?? 0, 3), 0, 1, 'L');
        $this->pdf->Ln(5);
    }
    
    private function addFinalResults($data) {
        // Final Results Header - Excel style
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'OVERALL RATING (EXAM + ORAL INTERVIEW)', 0, 1, 'C');
        $this->pdf->Ln(3);
        
        // Excel-style results table
        $this->pdf->SetDrawColor(200, 200, 200); // Light gray gridlines
        $this->pdf->SetLineWidth(0.1); // Thin gridlines
        
        // Header row with light gray background
        $this->pdf->SetFillColor(240, 240, 240); // Light gray background
        $this->pdf->SetFont('helvetica', 'B', 11);
        
        $this->pdf->Cell(60, 8, 'Component', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, 'Rating', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Weight', 1, 0, 'C', true);
        $this->pdf->Cell(50, 8, 'Weighted Score', 1, 1, 'C', true);
        
        // Data rows - white background
        $this->pdf->SetFillColor(255, 255, 255); // White background
        $this->pdf->SetFont('helvetica', '', 11);
        
        // Exam row
        $examWeighted = ($data['exam_rating'] ?? 0) * 0.50;
        $this->pdf->Cell(60, 7, 'Exam', 1, 0, 'L', true);
        $this->pdf->Cell(40, 7, number_format($data['exam_rating'] ?? 0, 2), 1, 0, 'C', true);
        $this->pdf->Cell(30, 7, '50%', 1, 0, 'C', true);
        $this->pdf->Cell(50, 7, number_format($examWeighted, 2), 1, 1, 'C', true);
        
        // Oral Interview row
        $interviewWeighted = ($data['interview_score'] ?? 0) * 0.10;
        $this->pdf->Cell(60, 7, 'Oral Interview', 1, 0, 'L', true);
        $this->pdf->Cell(40, 7, number_format($data['interview_score'] ?? 0, 1), 1, 0, 'C', true);
        $this->pdf->Cell(30, 7, '10%', 1, 0, 'C', true);
        $this->pdf->Cell(50, 7, number_format($interviewWeighted, 2), 1, 1, 'C', true);
        
        // Total row - Bold
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(60, 7, 'TOTAL RATING', 1, 0, 'C', true);
        $this->pdf->Cell(40, 7, '–', 1, 0, 'C', true);
        $this->pdf->Cell(30, 7, '60%', 1, 0, 'C', true);
        $this->pdf->Cell(50, 7, number_format($data['exam_rating'] ?? 0, 2), 1, 1, 'C', true);
        
        $this->pdf->Ln(5);
        
        // Final result with Excel-style color coding
        $overallRating = $data['overall_rating'] ?? 'N/A';
        $this->pdf->SetFont('helvetica', 'B', 11);
        
        $this->pdf->Cell(30, 8, 'Result:', 0, 0, 'L');
        
        // Color-coded result like Excel (Red for FAILED, Green for PASSED)
        if (strtoupper($overallRating) === 'FAILED') {
            $this->pdf->SetTextColor(255, 0, 0); // Red for FAILED
            $this->pdf->Cell(0, 8, strtoupper($overallRating), 0, 1, 'L');
        } else {
            $this->pdf->SetTextColor(0, 128, 0); // Green for PASSED
            $this->pdf->Cell(0, 8, strtoupper($overallRating), 0, 1, 'L');
        }
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        
        $this->pdf->Ln(8);
    }
    
    private function addRequirementsChecklist($data) {
        // Requirements Header - Excel style
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'REQUIREMENTS CHECKLIST', 0, 1, 'C');
        $this->pdf->Ln(3);
        
        // Requirements list - Excel style (Calibri 10-11pt, empty cells)
        $requirements = [
            'High School Form 138',
            'Certification of Good Moral Character',
            'PSA Authenticated Birth Certificate',
            'Transfer Credential',
            'Certification of Grades'
        ];
        
        $this->pdf->SetFont('helvetica', '', 10); // Calibri 10pt equivalent
        $this->pdf->SetDrawColor(200, 200, 200); // Light gray gridlines
        $this->pdf->SetLineWidth(0.1);
        
        foreach ($requirements as $requirement) {
            // Empty checkbox cell (blank, no formatting unless marked manually)
            $this->pdf->Cell(15, 6, '', 1, 0, 'C'); // Empty cell with border
            $this->pdf->Cell(0, 6, $requirement, 0, 1, 'L'); // Requirement text
        }
        
        $this->pdf->Ln(5);
        
        // Final Summary - Excel style table
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'FINAL SUMMARY', 0, 1, 'C');
        $this->pdf->Ln(2);
        
        // Summary table with Excel formatting
        $this->pdf->SetFillColor(240, 240, 240); // Light gray background for headers
        $this->pdf->SetFont('helvetica', 'B', 11);
        
        // Summary data in table format
        $this->pdf->Cell(60, 7, 'Raw Score', 1, 0, 'C', true);
        $this->pdf->Cell(60, 7, 'Exam Rating', 1, 0, 'C', true);
        $this->pdf->Cell(60, 7, 'Total Rating', 1, 1, 'C', true);
        
        $this->pdf->SetFillColor(255, 255, 255); // White background for data
        $this->pdf->SetFont('helvetica', '', 11);
        
        $this->pdf->Cell(60, 7, ($data['raw_score'] ?? 0) . '/250', 1, 0, 'C', true);
        $this->pdf->Cell(60, 7, number_format($data['exam_rating'] ?? 0, 2), 1, 0, 'C', true);
        $this->pdf->Cell(60, 7, number_format($data['exam_rating'] ?? 0, 2) . '/100', 1, 1, 'C', true);
        
        $this->pdf->Ln(3);
        
        // Final result with Excel-style color coding
        $overallRating = $data['overall_rating'] ?? 'N/A';
        $this->pdf->SetFont('helvetica', 'B', 11);
        
        $this->pdf->Cell(30, 8, 'Final Result:', 0, 0, 'L');
        
        // Color-coded result (Red for FAILED, Green for PASSED)
        if (strtoupper($overallRating) === 'FAILED') {
            $this->pdf->SetTextColor(255, 0, 0); // Red for FAILED
            $this->pdf->Cell(0, 8, strtoupper($overallRating), 0, 1, 'L');
        } else {
            $this->pdf->SetTextColor(0, 128, 0); // Green for PASSED
            $this->pdf->Cell(0, 8, strtoupper($overallRating), 0, 1, 'L');
        }
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        
        $this->pdf->Ln(8);
    }
    
    private function addFooter($data) {
        // Processing Information - Excel style
        $this->pdf->SetXY(10, -25);
        $this->pdf->SetFont('helvetica', '', 9); // Calibri equivalent
        $this->pdf->Cell(0, 4, 'Processed by: ' . ($data['processed_by_name'] ?? 'System Administrator'), 0, 1, 'L');
        $this->pdf->Cell(0, 4, 'Processed on: ' . date('M d, Y H:i', strtotime($data['processed_at'] ?? 'now')), 0, 1, 'L');
        $this->pdf->Cell(0, 4, 'Generated on: ' . date('M d, Y H:i'), 0, 1, 'L');
        
        // Official stamp area - Excel style with thin gridlines
        $this->pdf->SetXY(140, -30);
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetDrawColor(200, 200, 200); // Light gray gridlines
        $this->pdf->SetLineWidth(0.1);
        $this->pdf->Rect(140, -30, 60, 20);
        $this->pdf->SetXY(140, -20);
        $this->pdf->Cell(60, 5, 'OFFICIAL STAMP', 0, 1, 'C');
    }
}
?>