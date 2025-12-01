<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/pdf_favicon_helper.php';

class F2PersonalDataPDF {
    private $pdf;
    private $currentY = 0;
    
    public function __construct() {
        // Use long bond paper format (8.5" x 13")
        $this->pdf = new TCPDF('P', 'mm', array(216, 330), true, 'UTF-8', false);
        $this->setupPDF();
    }
    
    private function setupPDF() {
        // Set document information
        $this->pdf->SetCreator('Quirino State University');
        $this->pdf->SetAuthor('OGCA');
        $this->pdf->SetTitle('Personal Data Form');
        $this->pdf->SetSubject('Personal Data Form');
        $this->pdf->SetKeywords('QSU, Personal Data, Admission, Student Information');
        
        // Set PDF metadata with favicon information
        PDFFaviconHelper::setPDFMetadata($this->pdf, 'Personal Data Form', 'Personal Data Form', 'QSU, Personal Data, Admission, Student Information');
        
        // Remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Set margins for long bond paper
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetAutoPageBreak(TRUE, 20);
    }
    
    public function generateF2PersonalDataPDF($formData, $studentInfo = null) {
        $this->pdf->AddPage();
        $this->currentY = 0;
        
        // Header with logo and student photo
        $this->addHeader($studentInfo);
        
        // I. Personal Information
        $this->addPersonalInfo($formData);
        
        // II. Family Information
        $this->addFamilyInfo($formData);
        
        // III. Educational / Vocational Information
        $this->addEducationalInfo($formData);
        
        // IV. Skills Information
        $this->addSkillsInfo($formData);
        
        // V. Health Record
        $this->addHealthRecord($formData);
        
        // Declaration
        $this->addDeclaration($formData);
        
        // Footer
        $this->addFooter($studentInfo);
        
        return $this->pdf;
    }
    
    private function addHeader($studentInfo = null) {
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
        
        // Add favicon next to logo
        PDFFaviconHelper::addFaviconToPDF($this->pdf, 'assets/images/favicon_io/favicon-32x32.png', 37, 10, 6);
        
        // University information (next to logo)
        $this->pdf->SetXY(38, 13);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->Cell(0, 5, 'QUIRINO STATE UNIVERSITY', 0, 1, 'L');
        
        $this->pdf->SetXY(38, 18);
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Cell(0, 4, 'Office of Guidance, Counseling and Admission', 0, 1, 'L');
        
        // Add horizontal line below university information
        $linePath = __DIR__ . '/../assets/images/line.png';
        if (file_exists($linePath)) {
            $this->pdf->Image($linePath, 38, 22, 157, 2);
        } else {
            // Fallback: simple line if image doesn't exist
            $this->pdf->SetXY(38, 22);
            $this->pdf->Cell(157, 0, '', 'B', 1, 'L');
        }
        
        $this->currentY = 30;
    }
    
    private function addPersonalInfo($data) {
        $p = $data['personal_info'] ?? [];
        
        // Main form title with green background
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->SetFillColor(179, 228, 160); // Light green color
        $this->pdf->Cell(180, 7, 'PERSONAL DATA FORM', 1, 1, 'C', true);
        $this->currentY += 7;
        
        // Section header - I. PERSONAL (no background, just label)
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'B', 10);
        $this->pdf->SetFillColor(179, 228, 160); // Light green color
        $this->pdf->Cell(180, 6, 'I. PERSONAL', 1, 1, 'L', true);
        $this->currentY += 6;
        
        // Table settings
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->SetFillColor(255, 255, 255);
        $leftMargin = 15;
        $rowHeight = 5; // Reduced from 6 to 5 for more compact layout
        
        // Row 1: Last Name, First Name, Middle Name (label and value in ONE cell)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        
        // Last Name - label and value together in one cell
        $lastNameText = 'Last Name: ' . ($p['last_name'] ?? '');
        $this->pdf->Cell(60, $rowHeight, $lastNameText, 1, 0, 'L');
        
        // First Name - label and value together in one cell
        $firstNameText = 'First Name: ' . ($p['first_name'] ?? '');
        $this->pdf->Cell(60, $rowHeight, $firstNameText, 1, 0, 'L');
        
        // Middle Name - label and value together in one cell
        $middleNameText = 'Middle Name: ' . ($p['middle_name'] ?? '');
        $this->pdf->Cell(60, $rowHeight, $middleNameText, 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 2: Civil Status, Name of Spouse (each in ONE cell)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $civilStatusText = 'Civil Status: ' . ($p['civil_status'] ?? '');
        $spouseText = 'Name of Spouse (If Married) ' . ($p['spouse_name'] ?? '');
        $this->pdf->Cell(60, $rowHeight, $civilStatusText, 1, 0, 'L');
        $this->pdf->Cell(120, $rowHeight, $spouseText, 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 3: Course/Year Level, Sex, Ethnicity (each in ONE cell)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $courseText = 'Course/Year Level ' . ($p['course_year_level'] ?? '');
        $sexText = 'Sex: ' . ($p['sex'] ?? '');
        
        // Handle ethnicity with checkboxes style
        $ethnicity = $p['ethnicity'] ?? '';
        $ethnicityDisplay = '';
        if ($ethnicity === 'Ilocano') $ethnicityDisplay = '( X ) Ilocano  ( ) Ifugao  ( ) Tagalog  Others:';
        elseif ($ethnicity === 'Ifugao') $ethnicityDisplay = '( ) Ilocano  ( X ) Ifugao  ( ) Tagalog  Others:';
        elseif ($ethnicity === 'Tagalog') $ethnicityDisplay = '( ) Ilocano  ( ) Ifugao  ( X ) Tagalog  Others:';
        elseif ($ethnicity === 'Others') $ethnicityDisplay = '( ) Ilocano  ( ) Ifugao  ( ) Tagalog  Others: ' . ($p['ethnicity_others_specify'] ?? '');
        else $ethnicityDisplay = '( ) Ilocano  ( ) Ifugao  ( ) Tagalog  Others:';
        $ethnicityText = 'Ethnicity: ' . $ethnicityDisplay;
        
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(60, $rowHeight, $courseText, 1, 0, 'L');
        $this->pdf->Cell(40, $rowHeight, $sexText, 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(80, $rowHeight, $ethnicityText, 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 4: Date of Birth, Age, Religion (each in ONE cell with checkboxes)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', '', 9);
        $dobText = 'Date of Birth ' . ($p['date_of_birth'] ?? '');
        $ageText = 'Age: ' . ($p['age'] ?? '');
        $religionText = 'Religion: ( ) Ilocano  ( ) Ifugao  ( ) Tagalog  Others:';
        
        $this->pdf->Cell(60, $rowHeight, $dobText, 1, 0, 'L');
        $this->pdf->Cell(40, $rowHeight, $ageText, 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(80, $rowHeight, $religionText, 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 5: Place of Birth, Religion value (each in ONE cell)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', '', 9);
        $pobText = 'Place of Birth ' . ($p['place_of_birth'] ?? '');
        $religionValueText = 'Religion: ' . ($p['religion'] ?? '');
        
        $this->pdf->Cell(100, $rowHeight, $pobText, 1, 0, 'L');
        $this->pdf->Cell(80, $rowHeight, $religionValueText, 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 6: Address and Contact No (each in ONE cell)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $addressText = 'Address ' . ($p['address'] ?? '');
        $contactText = 'Contact No. ' . ($p['contact_number'] ?? '');
        
        $this->pdf->Cell(120, $rowHeight, $addressText, 1, 0, 'L');
        $this->pdf->Cell(60, $rowHeight, $contactText, 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // NO spacing between Personal and Family sections - they should be connected
    }
    
    private function addFamilyInfo($data) {
        $f = $data['family_info'] ?? [];
        
        // Section header - matching the green header style (NO spacing, connected to previous section)
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'B', 10);
        $this->pdf->SetFillColor(179, 228, 160); // Light green color
        $this->pdf->Cell(180, 6, 'II. FAMILY', 1, 1, 'L', true);
        $this->currentY += 6;
        
        // Table settings
        $this->pdf->SetFont('times', 'B', 9);
        $leftMargin = 15;
        $rowHeight = 5; // Reduced from 6 to 5 for more compact layout
        
        // Row 1: Father | empty | Mother headers
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->Cell(75, $rowHeight, 'Father', 1, 0, 'C');
        $this->pdf->Cell(30, $rowHeight, '', 1, 0, 'C');
        $this->pdf->Cell(75, $rowHeight, 'Mother', 1, 1, 'C');
        $this->currentY += $rowHeight;
        
        // Row 2: Father data | Name label | Mother data
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(75, $rowHeight, $f['father_name'] ?? '', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, $rowHeight, 'Name', 1, 0, 'C');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(75, $rowHeight, $f['mother_name'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 3: Father data | Occupation label | Mother data
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->Cell(75, $rowHeight, $f['father_occupation'] ?? '', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, $rowHeight, 'Occupation', 1, 0, 'C');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(75, $rowHeight, $f['mother_occupation'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 4: Father data | Ethnicity label | Mother data
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->Cell(75, $rowHeight, $f['father_ethnicity'] ?? '', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, $rowHeight, 'Ethnicity', 1, 0, 'C');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(75, $rowHeight, $f['mother_ethnicity'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Parents' Status section - matching the image layout
        $startY = $this->currentY;
        $this->pdf->SetXY($leftMargin, $startY);
        $this->pdf->SetFont('times', 'B', 9);
        
        // Parents' Status label (left column) - spans both rows
        $this->pdf->Cell(35, 24, 'Parents\' Status', 1, 0, 'L');
        
        // Row 1: Living together (top cell in middle column) - with database data
        $this->pdf->SetXY($leftMargin + 35, $startY);
        $this->pdf->SetFont('times', '', 8);
        $livingTogether = $f['parents_living_together'] ?? '';
        $livingTogetherYes = ($livingTogether === 'Yes') ? '(X)' : '( )';
        $livingTogetherNo = ($livingTogether === 'No') ? '(X)' : '( )';
        $this->pdf->Cell(110, 6, "Living together: $livingTogetherYes YES    $livingTogetherNo NO", 1, 0, 'L');
        
        // Right column is empty for Living together row
        $this->pdf->Cell(35, 6, '', 1, 1, 'L');
        
        // Row 2: Separated details (bottom cell in middle column) - with database data
        $this->pdf->SetXY($leftMargin + 35, $startY + 6);
        $this->pdf->SetFont('times', '', 8);
        
        $separated = $f['parents_separated'] ?? '';
        $separatedYes = ($separated === 'Yes') ? '(X)' : '( )';
        $separatedNo = ($separated === 'No') ? '(X)' : '( )';
        
        $separationReason = $f['separation_reason'] ?? '';
        $workChecked = (strpos($separationReason, 'work') !== false) ? 'X' : ' ';
        $conflictChecked = (strpos($separationReason, 'conflict') !== false) ? 'X' : ' ';
        
        $livingWith = $f['living_with'] ?? '';
        $motherChecked = ($livingWith === 'Mother') ? 'X' : ' ';
        $fatherChecked = ($livingWith === 'Father') ? 'X' : ' ';
        $relativesChecked = ($livingWith === 'Relatives') ? 'X' : ' ';
        $othersChecked = ($livingWith === 'Others') ? 'X' : ' ';
        
        $separatedText = "Separated:          $separatedYes YES      $separatedNo NO\n";
        $separatedText .= "__ Due to work (Ang nanay o tatay ay sa malayo nag-tratrabaho)\n";
        $separatedText .= "__  Due to conflict (Nagkaroon ng problema sa pagitan ng magulang)\n";
        $separatedText .= "If separated, living with? (  ) Mother  (  ) Father  (  ) Relatives\n";
        $separatedText .= "                                         (  ) Others, specify_____";
        $this->pdf->MultiCell(110, 3.6, $separatedText, 1, 'L');
        
        // Your age when parents separated (right side) - spans from row 2 down
        $this->pdf->SetXY($leftMargin + 145, $startY + 6);
        $this->pdf->SetFont('times', 'B', 8);
        $ageText = "Your age when\nparents separated?\n(Ilang taon ka ng maghiwalay\nang yong magulang?)";
        $this->pdf->MultiCell(35, 18, $ageText, 1, 'L');
        
        $this->currentY = $startY + 24;
        
        // NO spacing - Guardian section should be connected to Parents' Status
        // Guardian's Name and Relationship row
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(35, $rowHeight, 'Guardian\'s Name:', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(55, $rowHeight, $f['guardian_name'] ?? '', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(50, $rowHeight, 'Relationship with the guardian', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(40, $rowHeight, $f['guardian_relationship'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Address and Guardian's Contact Number row
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(35, $rowHeight, 'Address', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(55, $rowHeight, $f['guardian_address'] ?? '', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(50, $rowHeight, 'Guardian\'s Contact Number', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(40, $rowHeight, $f['guardian_contact_number'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Siblings Information header row
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(135, $rowHeight, 'Name of Siblings (Mga kapatid, pangalan\' hanggang pinaka-bata)', 1, 0, 'L');
        $this->pdf->Cell(45, $rowHeight, 'Birth Order (Pang ilan ka?)', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Siblings data from database
        $siblingsInfo = $f['siblings_info'] ?? '';
        $this->pdf->SetFont('times', '', 9);
        
        // Display siblings information in the table
        if (!empty($siblingsInfo)) {
            // Split siblings info into lines and display
            $siblingsLines = explode("\n", $siblingsInfo);
            $maxRows = 3; // Maximum 3 rows for siblings
            
            for ($i = 0; $i < $maxRows; $i++) {
                $this->pdf->SetXY($leftMargin, $this->currentY);
                $siblingLine = $siblingsLines[$i] ?? '';
                
                // Parse sibling name and birth order if format is "Name (Order)"
                if (preg_match('/^(.+?)\s*\((.+?)\)$/', $siblingLine, $matches)) {
                    $siblingName = trim($matches[1]);
                    $birthOrder = trim($matches[2]);
                } else {
                    $siblingName = $siblingLine;
                    $birthOrder = '';
                }
                
                $this->pdf->Cell(135, $rowHeight, $siblingName, 1, 0, 'L');
                $this->pdf->Cell(45, $rowHeight, $birthOrder, 1, 1, 'L');
                $this->currentY += $rowHeight;
            }
        } else {
            // Empty rows if no siblings data
            for ($i = 0; $i < 3; $i++) {
                $this->pdf->SetXY($leftMargin, $this->currentY);
                $this->pdf->Cell(135, $rowHeight, '', 1, 0, 'L');
                $this->pdf->Cell(45, $rowHeight, '', 1, 1, 'L');
                $this->currentY += $rowHeight;
            }
        }
        
        // NO spacing - next section should be connected
    }
    
    private function addEducationalInfo($data) {
        $e = $data['education'] ?? [];
        
        
        // Section header - matching the green header style
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'B', 10);
        $this->pdf->SetFillColor(179, 228, 160); // Light green color
        $this->pdf->Cell(180, 6, 'III. EDUCATIONAL/VOCATIONAL', 1, 1, 'L', true);
        $this->currentY += 6;
        
        // Table settings
        $this->pdf->SetFont('times', '', 9);
        $leftMargin = 15;
        $rowHeight = 6;
        
        // Elementary School
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, $rowHeight, 'Elementary', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(150, $rowHeight, $e['elementary_school'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Secondary School
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, $rowHeight, 'Secondary', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(150, $rowHeight, $e['secondary_school'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // For Transferee Students ONLY section
        // Left column: "For Transferee Students ONLY" and "School/University Last Attended" in one cell
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->MultiCell(90, $rowHeight, "For Transferee Students ONLY\n\nSchool/University Last Attended (Kumpletuhin):", 1, 'L');
        
        // Right column: School's Name (top)
        $this->pdf->SetXY($leftMargin + 90, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, $rowHeight, 'School\'s Name:', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(60, $rowHeight, $e['school_name'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // School's Address (bottom) - align with the left cell
        $this->pdf->SetXY($leftMargin + 90, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, $rowHeight, 'School\'s Address:', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(60, $rowHeight, $e['school_address'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // No blank cell - next section should be connected directly
        
        // Row 1: General Average (left) and Course Intended to Take (right) - smaller cells
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(40, $rowHeight, 'General Average:', 1, 0, 'L');
        $this->pdf->Cell(30, $rowHeight, $e['general_average'] ?? '', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(110, $rowHeight, 'Course Intended to Take:', 1, 1, 'C');
        $this->currentY += $rowHeight;
        
        // Row 2: Nature of Schooling (left) and 1st Choice (right) - adjusted sizes for data
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(70, $rowHeight, 'Nature of Schooling', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(35, $rowHeight, '1st Choice', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(75, $rowHeight, $e['course_first_choice'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 3: Continues? with Yes/No options (left) and 2nd Choice (right) - adjusted sizes for data
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(25, $rowHeight, 'Continues?', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        
        // Show actual selection with checkboxes
        $natureContinuous = $e['nature_of_schooling_continuous'] ?? '';
        $yesChecked = ($natureContinuous === 'Yes') ? '(✓)' : '( )';
        $noChecked = ($natureContinuous === 'No') ? '(✓)' : '( )';
        
        $this->pdf->Cell(20, $rowHeight, $yesChecked . 'Yes', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 8);
        $this->pdf->Cell(25, $rowHeight, $noChecked . 'NO', 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(35, $rowHeight, '2nd Choice', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(75, $rowHeight, $e['course_second_choice'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 4: Reason (If interrupted) spans 2 rows (left) and 3rd Choice (right) - adjusted sizes for data
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(70, $rowHeight * 2, 'Reason (If interrupted)', 1, 0, 'L');
        
        // Add the actual reason text inside the cell
        $reasonText = $e['reason_if_interrupted'] ?? '';
        if (!empty($reasonText)) {
            $this->pdf->SetXY($leftMargin + 2, $this->currentY + 2);
            $this->pdf->SetFont('times', '', 7);
            $this->pdf->MultiCell(66, 4, $reasonText, 0, 'L');
        }
        
        $this->pdf->SetXY($leftMargin + 70, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(35, $rowHeight, '3rd Choice', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(75, $rowHeight, $e['course_third_choice'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // Row 5: Empty (left) and Parent's Choice (right) - adjusted sizes for data
        $this->pdf->SetXY($leftMargin + 70, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(35, $rowHeight, 'Parent\'s Choice:', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(75, $rowHeight, $e['parents_choice'] ?? '', 1, 1, 'L');
        $this->currentY += $rowHeight;
        
        // NO spacing - next section should be connected
    }
    
    private function addSkillsInfo($data) {
        $s = $data['skills'] ?? [];
        
        // Section header - matching the green header style (connected to previous section)
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'B', 11);
        $this->pdf->SetFillColor(179, 228, 160); // Light green color
        $this->pdf->Cell(180, 6, 'IV. SKILLS / TALENTS / SPECIAL ABILITIES', 1, 1, 'L', true);
        $this->currentY += 6;
        
        // Table settings
        $this->pdf->SetFont('times', '', 9);
        $leftMargin = 15;
        $rowHeight = 6; // Reduced height to fit more content
        
        // Row 1: Talents, Talents data, and Awards (3-column structure)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(60, $rowHeight, 'Talent/s', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(60, $rowHeight, $s['talents'] ?? '', 1, 0, 'L'); // Talents data
        $this->pdf->SetFont('times', 'B', 9);
        $awardsText = 'Awards: ' . ($s['awards'] ?? '');
        $this->pdf->Cell(60, $rowHeight, $awardsText, 1, 1, 'L'); // Awards: data
        $this->currentY += $rowHeight;
        
        // Row 2: Hobbies, Hobbies data, and empty cell
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(60, $rowHeight, 'Hobbies', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(60, $rowHeight, $s['hobbies'] ?? '', 1, 0, 'L'); // Hobbies data
        $this->pdf->Cell(60, $rowHeight, '', 1, 1, 'L'); // Empty cell
        $this->currentY += $rowHeight;
        
        // NO spacing - connect to next section
    }
    
    private function addHealthRecord($data) {
        $h = $data['health_record'] ?? [];
        
        // Section header - matching the green header style (connected to previous section)
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'B', 11);
        $this->pdf->SetFillColor(179, 228, 160); // Light green color
        $this->pdf->Cell(180, 6, 'V. HEALTH RECORD', 1, 1, 'L', true);
        $this->currentY += 6;
        
        // Table settings
        $this->pdf->SetFont('times', '', 9);
        $leftMargin = 15;
        $rowHeight = 5; // Reduced height to fit more content
        
        // Disability (specify)
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(60, $rowHeight, 'Disability (specify):', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(120, $rowHeight, $h['disability_specify'] ?? '', 1, 1, 'L'); // Disability data
        $this->currentY += $rowHeight;
        
        // Have you been confined/rehabilitated for the past 3 years
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(80, $rowHeight, 'Have you been confined/rehabilitated for the past 3 years', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $confined = $h['confined_rehabilitated'] ?? '';
        $confinedYes = ($confined === 'Yes') ? '(X)' : '( )';
        $confinedNo = ($confined === 'No') ? '(X)' : '( )';
        $this->pdf->Cell(40, $rowHeight, "$confinedYes YES $confinedNo NO", 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(20, $rowHeight, 'When?', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(40, $rowHeight, $h['confined_when'] ?? '', 1, 1, 'L'); // When data
        $this->currentY += $rowHeight;
        
        // Have you been treated for an illness
        $this->pdf->SetXY($leftMargin, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(80, $rowHeight, 'Have you been treated for an illness', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 8);
        $treated = $h['treated_for_illness'] ?? '';
        $treatedYes = ($treated === 'Yes') ? '(X)' : '( )';
        $treatedNo = ($treated === 'No') ? '(X)' : '( )';
        $this->pdf->Cell(40, $rowHeight, "$treatedYes YES $treatedNo NO", 1, 0, 'L');
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(20, $rowHeight, 'When?', 1, 0, 'L');
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->Cell(40, $rowHeight, $h['treated_when'] ?? '', 1, 1, 'L'); // When data
        $this->currentY += $rowHeight;
        
        // NO spacing - connect to next section
    }
    
    private function addDeclaration($data) {
        $d = $data['declaration'] ?? [];
        
        // Declaration section - single cell with border (connected to previous section)
        $declarationStartY = $this->currentY;
        $declarationHeight = 20; // Reduced height to fit on one page
        
        // Create single cell border for entire declaration
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->Cell(180, $declarationHeight, '', 1, 1, 'L');
        
        // Declaration text (inside the cell)
        $this->pdf->SetXY(20, $this->currentY + 2);
        $this->pdf->SetFont('times', 'I', 9);
        $declarationText = 'I hereby declare that the details I provided to this form are true and correct';
        $this->pdf->Cell(100, 3, $declarationText, 0, 0, 'L');
        
        // Signature and Date area (right side, inside the cell) - compact spacing
        // Signature value first
        $this->pdf->SetXY(140, $this->currentY + 1);
        $this->pdf->SetFont('times', 'B', 8);
        $this->pdf->Cell(40, 3, strtoupper($d['signature_over_printed_name'] ?? ''), 0, 1, 'R');
        
        // Signature line
        $this->pdf->Line(140, $this->currentY + 4, 180, $this->currentY + 4);
        
        // Signature label below
        $this->pdf->SetXY(140, $this->currentY + 5);
        $this->pdf->SetFont('times', 'BI', 7);
        $this->pdf->Cell(40, 3, 'Signature over printed name', 0, 1, 'R');
        
        // Date value first
        $this->pdf->SetXY(140, $this->currentY + 8);
        $this->pdf->SetFont('times', 'B', 8);
        $this->pdf->Cell(40, 3, $d['date_accomplished'] ?? '', 0, 1, 'R');
        
        // Date line
        $this->pdf->Line(140, $this->currentY + 12, 180, $this->currentY + 12);
        
        // Date label below
        $this->pdf->SetXY(140, $this->currentY + 13);
        $this->pdf->SetFont('times', 'BI', 7);
        $this->pdf->Cell(40, 3, 'Date accomplished', 0, 1, 'R');
        
        $this->currentY += $declarationHeight;
        
        // Horizontal separator line with guidance counselor note (connected to declaration cell)
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'BI', 9);
        $this->pdf->Cell(180, 6, 'This portion is to be filled-up by the Guidance Counselor', 1, 1, 'C');
        $this->currentY += 6;
        
        // Guidance Counselor Impressions section
        $this->pdf->SetXY(15, $this->currentY);
        $this->pdf->SetFont('times', 'B', 9);
        $this->pdf->Cell(30, 20, 'Impressions:', 1, 0, 'L');
        $this->pdf->Cell(150, 20, '', 1, 1, 'L'); // Large empty space for counselor notes
        $this->currentY += 20;
    }
    
    private function addFooter($studentInfo) {
        // Position footer at bottom of current page
        $footerY = 300; // Moved further down for long bond paper
        
       
        // Document control information
        $this->pdf->SetXY(15, $footerY);
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(180, 4, 'QSU-OGCA-F002', 0, 1, 'L');
        
        $this->pdf->SetXY(15, $footerY + 4);
        $this->pdf->SetFont('times', '', 8);
        $this->pdf->Cell(180, 4, 'Rev. 01 (Feb. 03, 2025)', 0, 1, 'L');
        
        // Reset text color
        $this->pdf->SetTextColor(0, 0, 0);
    }
}
?>
