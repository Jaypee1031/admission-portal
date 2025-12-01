<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/pdf_favicon_helper.php';

class TestResultPDF {
    private $pdf;
    
    public function __construct() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setupPDF();
    }
    
    private function setupPDF() {
        // Set document information
        $this->pdf->SetCreator('Quirino State University');
        $this->pdf->SetAuthor('OGCA');
        $this->pdf->SetTitle('Test Result');
        $this->pdf->SetSubject('College Admission Test Result');
        $this->pdf->SetKeywords('QSU, Test Result, Admission, CAT, Entrance Exam');
        
        // Set PDF metadata with favicon information
        PDFFaviconHelper::setPDFMetadata($this->pdf, 'Test Result', 'College Admission Test Result', 'QSU, Test Result, Admission, CAT, Entrance Exam');
        
        // Remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Set margins
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $this->pdf->AddPage();
    }
    
    public function generateTestResult($resultData) {
        // Header
        $this->addHeader();
        
        // Student Information
        $this->addStudentInfo($resultData);
        
        // Test Results
        $this->addTestResults($resultData);
        
        // Overall Results
        $this->addOverallResults($resultData);
        
        // Recommendation
        if (!empty($resultData['recommendation'])) {
            $this->addRecommendation($resultData);
        }
        
        // Footer
        $this->addFooter($resultData);
        
        return $this->pdf;
    }
    
    private function addHeader() {
        // QSU Logo (left side)
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
        
        // University information (next to logo)
        $this->pdf->SetXY(38, 13);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 5, 'QUIRINO STATE UNIVERSITY', 0, 1, 'L');
        
        $this->pdf->SetXY(38, 18);
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Cell(0, 4, 'SAS - Office of Guidance, Counseling and Admission', 0, 1, 'L');
        
        // Add horizontal line below university information
        $linePath = __DIR__ . '/../assets/images/line.png';
        if (file_exists($linePath)) {
            $this->pdf->Image($linePath, 38, 22, 157, 2);
        } else {
            // Fallback: simple line if image doesn't exist
            $this->pdf->SetXY(38, 22);
            $this->pdf->Cell(157, 0, '', 'B', 1, 'L');
        }
        // Main form title - same styling as Personal Data section
        $this->pdf->SetXY(15, 25);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->SetFillColor(179, 228, 160); // Light green color
        $this->pdf->Cell(180, 6, 'COLLEGE ADMISSION TEST RESULT', 1, 1, 'C', true);
        
        $this->pdf->SetY(35);
    }
    
    private function addStudentInfo($data) {
        // Student Information Box
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Rect(15, 45, 180, 35);
        
        $this->pdf->SetXY(20, 47);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 6, 'STUDENT INFORMATION', 0, 1, 'L');
        
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->SetXY(20, 55);
        $this->pdf->Cell(40, 5, 'Name:', 0, 0, 'L');
        $this->pdf->Cell(0, 5, $data['first_name'] . ' ' . $data['last_name'] . ($data['middle_name'] ? ' ' . $data['middle_name'] : ''), 0, 1, 'L');
        
        $this->pdf->SetXY(20, 60);
        $this->pdf->Cell(40, 5, 'Email:', 0, 0, 'L');
        $this->pdf->Cell(0, 5, $data['email'], 0, 1, 'L');
        
        $this->pdf->SetXY(20, 65);
        $this->pdf->Cell(40, 5, 'Student Type:', 0, 0, 'L');
        $this->pdf->Cell(0, 5, $data['type'], 0, 1, 'L');
        
        $this->pdf->SetXY(20, 70);
        $this->pdf->Cell(40, 5, 'Permit Number:', 0, 0, 'L');
        $this->pdf->Cell(0, 5, $data['permit_number'], 0, 1, 'L');
        
        $this->pdf->Ln(10);
    }
    
    private function addTestResults($data) {
        // Test Results Box
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Rect(15, 90, 180, 80);
        
        $this->pdf->SetXY(20, 92);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 6, 'TEST RESULTS', 0, 1, 'L');
        
        // Exam Information
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->SetXY(20, 100);
        $this->pdf->Cell(40, 5, 'Exam Date:', 0, 0, 'L');
        $this->pdf->Cell(0, 5, date('M d, Y', strtotime($data['exam_date'])), 0, 1, 'L');
        
        $this->pdf->SetXY(20, 105);
        $this->pdf->Cell(40, 5, 'Exam Time:', 0, 0, 'L');
        $this->pdf->Cell(0, 5, date('g:i A', strtotime($data['exam_time'])), 0, 1, 'L');
        
        $this->pdf->SetXY(20, 110);
        $this->pdf->Cell(40, 5, 'Exam Room:', 0, 0, 'L');
        $this->pdf->Cell(0, 5, $data['exam_room'], 0, 1, 'L');
        
        // Subject Scores Table
        $this->pdf->SetXY(20, 120);
        $this->pdf->SetFont('times', 'B', 10);
        $this->pdf->Cell(60, 8, 'Subject', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Raw Score', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Transmuted', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Weight', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Weighted Score', 1, 1, 'C');
        
        $this->pdf->SetFont('times', '', 9);
        
        // General Info
        $this->pdf->SetXY(20, 128);
        $this->pdf->Cell(60, 6, 'General Information', 1, 0, 'L');
        $this->pdf->Cell(30, 6, ($data['gen_info_raw'] ?? 'N/A') . '/30', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format($data['gen_info_transmuted'] ?? 0, 1) . '%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, '10%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format(($data['gen_info_transmuted'] ?? 0) * 0.10, 1), 1, 1, 'C');
        
        // Filipino
        $this->pdf->SetXY(20, 134);
        $this->pdf->Cell(60, 6, 'Filipino', 1, 0, 'L');
        $this->pdf->Cell(30, 6, ($data['filipino_raw'] ?? 'N/A') . '/50', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format($data['filipino_transmuted'] ?? 0, 1) . '%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, '15%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format(($data['filipino_transmuted'] ?? 0) * 0.15, 1), 1, 1, 'C');
        
        // English
        $this->pdf->SetXY(20, 140);
        $this->pdf->Cell(60, 6, 'English', 1, 0, 'L');
        $this->pdf->Cell(30, 6, ($data['english_raw'] ?? 'N/A') . '/60', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format($data['english_transmuted'] ?? 0, 1) . '%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, '25%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format(($data['english_transmuted'] ?? 0) * 0.25, 1), 1, 1, 'C');
        
        // Science
        $this->pdf->SetXY(20, 146);
        $this->pdf->Cell(60, 6, 'Science', 1, 0, 'L');
        $this->pdf->Cell(30, 6, ($data['science_raw'] ?? 'N/A') . '/60', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format($data['science_transmuted'] ?? 0, 1) . '%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, '25%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format(($data['science_transmuted'] ?? 0) * 0.25, 1), 1, 1, 'C');
        
        // Math
        $this->pdf->SetXY(20, 152);
        $this->pdf->Cell(60, 6, 'Mathematics', 1, 0, 'L');
        $this->pdf->Cell(30, 6, ($data['math_raw'] ?? 'N/A') . '/50', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format($data['math_transmuted'] ?? 0, 1) . '%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, '25%', 1, 0, 'C');
        $this->pdf->Cell(30, 6, number_format(($data['math_transmuted'] ?? 0) * 0.25, 1), 1, 1, 'C');
        
        $this->pdf->Ln(10);
    }
    
    private function addOverallResults($data) {
        // Overall Results Box
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Rect(15, 180, 180, 40);
        
        $this->pdf->SetXY(20, 182);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 6, 'OVERALL RESULTS', 0, 1, 'L');
        
        // Overall Results Table
        $this->pdf->SetXY(20, 190);
        $this->pdf->SetFont('times', 'B', 10);
        $this->pdf->Cell(45, 8, 'Exam Rating', 1, 0, 'C');
        $this->pdf->Cell(45, 8, 'Exam Percentage', 1, 0, 'C');
        $this->pdf->Cell(45, 8, 'Total Rating', 1, 0, 'C');
        $this->pdf->Cell(45, 8, 'Final Rank', 1, 1, 'C');
        
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->SetXY(20, 198);
        $this->pdf->Cell(45, 8, number_format($data['exam_rating'] ?? 0, 1), 1, 0, 'C');
        $this->pdf->Cell(45, 8, number_format($data['exam_percentage'] ?? 0, 1) . '%', 1, 0, 'C');
        $this->pdf->Cell(45, 8, number_format($data['total_rating'] ?? 0, 1), 1, 0, 'C');
        $this->pdf->Cell(45, 8, '#' . ($data['final_rank'] ?? 'N/A'), 1, 1, 'C');
        
        // Overall Rating
        $this->pdf->SetXY(20, 210);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(40, 8, 'Overall Rating:', 0, 0, 'L');
        $this->pdf->SetFont('times', 'B', 14);
        $this->pdf->Cell(0, 8, $data['overall_rating'] ?? 'N/A', 0, 1, 'L');
        
        $this->pdf->Ln(5);
    }
    
    private function addRecommendation($data) {
        // Recommendation Box
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Rect(15, 230, 180, 25);
        
        $this->pdf->SetXY(20, 232);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 6, 'RECOMMENDATION', 0, 1, 'L');
        
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->SetXY(20, 240);
        $this->pdf->MultiCell(170, 5, $data['recommendation'], 0, 'L');
        
        $this->pdf->Ln(5);
    }
    
    private function addFooter($data) {
        // Processing Information
        $this->pdf->SetXY(15, 265);
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(0, 4, 'Processed by: ' . ($data['processed_by_name'] ?? 'System Administrator'), 0, 1, 'L');
        $this->pdf->Cell(0, 4, 'Processed on: ' . date('M d, Y H:i', strtotime($data['processed_at'])), 0, 1, 'L');
        
        // Official Stamp Area
        $this->pdf->SetXY(120, 200);
        $this->pdf->SetFont('times', 'B', 10);
        $this->pdf->Cell(70, 20, '', 1, 0, 'C');
        $this->pdf->SetXY(120, 210);
        $this->pdf->Cell(70, 5, 'OFFICIAL STAMP', 0, 1, 'C');
        
        // Document Code
        $this->pdf->SetXY(15, 250);
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(0, 4, 'Document Code: F3-TR-001 | Revision: 1.0 | Date: ' . date('Y-m-d'), 0, 1, 'L');
    }
}
?>
