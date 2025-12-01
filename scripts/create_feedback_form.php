<?php
/**
 * Create Word Document Feedback Form
 * Generates a comprehensive feedback form for website testing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;

echo "📝 Creating Website Testing Feedback Form...\n";

try {
    // Create new PHPWord object
    $phpWord = new PhpWord();
    
    // Set document properties
    $properties = $phpWord->getDocInfo();
    $properties->setCreator('University Admission Portal');
    $properties->setTitle('Website Testing Feedback Form');
    $properties->setDescription('Comprehensive feedback form for testing the University Admission Portal');
    $properties->setSubject('Website Testing');
    
    // Add a section
    $section = $phpWord->addSection();
    
    // Title
    $section->addText('University Admission Portal', ['size' => 16, 'bold' => true], ['alignment' => 'center']);
    $section->addText('Website Testing Feedback Form', ['size' => 14, 'bold' => true], ['alignment' => 'center']);
    $section->addTextBreak(2);
    
    // Test Information Section
    $section->addText('Test Information:', ['size' => 12, 'bold' => true]);
    $section->addText('Date: _________________');
    $section->addText('Tester Name: _________________');
    $section->addText('Device Type: _________________ (Desktop/Laptop/Mobile/Tablet)');
    $section->addText('Browser: _________________');
    $section->addText('Operating System: _________________');
    $section->addText('Network: _________________ (WiFi/Mobile Data)');
    $section->addTextBreak();
    
    $section->addText('Website Access:');
    $section->addText('URL Used: _________________');
    $section->addText('Connection Status: _________________ (Success/Issues)');
    $section->addTextBreak();
    
    // Section 1: General Impressions
    $section->addText('SECTION 1: GENERAL IMPRESSIONS', ['size' => 12, 'bold' => true]);
    $section->addText('Rate the overall experience (1-5, where 5 is excellent):');
    $section->addText('Overall Design: ___/5');
    $section->addText('Ease of Navigation: ___/5');
    $section->addText('Page Loading Speed: ___/5');
    $section->addText('Mobile Responsiveness: ___/5');
    $section->addTextBreak();
    
    $section->addText('Comments:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    // Section 2: Student Registration Process
    $section->addText('SECTION 2: STUDENT REGISTRATION PROCESS', ['size' => 12, 'bold' => true]);
    $section->addText('Rate each step (1-5, where 5 is excellent):');
    $section->addText('Registration Form: ___/5');
    $section->addText('Document Upload: ___/5');
    $section->addText('Form Validation: ___/5');
    $section->addText('Success Confirmation: ___/5');
    $section->addTextBreak();
    
    $section->addText('Issues Encountered:');
    $section->addText('□ Form fields not working properly');
    $section->addText('□ File upload problems');
    $section->addText('□ Validation errors');
    $section->addText('□ Slow loading');
    $section->addText('□ Other: _________________');
    $section->addTextBreak();
    
    $section->addText('Comments:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    // Section 3: Admin Functionality
    $section->addText('SECTION 3: ADMIN FUNCTIONALITY', ['size' => 12, 'bold' => true]);
    $section->addText('Rate each feature (1-5, where 5 is excellent):');
    $section->addText('Login Process: ___/5');
    $section->addText('Dashboard Interface: ___/5');
    $section->addText('Student Management: ___/5');
    $section->addText('Document Review: ___/5');
    $section->addText('PDF Generation: ___/5');
    $section->addText('Test Permit Management: ___/5');
    $section->addText('Test Results Management: ___/5');
    $section->addTextBreak();
    
    $section->addText('Issues Encountered:');
    $section->addText('□ Login problems');
    $section->addText('□ Dashboard errors');
    $section->addText('□ File download issues');
    $section->addText('□ PDF generation problems');
    $section->addText('□ Data display errors');
    $section->addText('□ Other: _________________');
    $section->addTextBreak();
    
    $section->addText('Comments:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    // Section 4: Security & Performance
    $section->addText('SECTION 4: SECURITY & PERFORMANCE', ['size' => 12, 'bold' => true]);
    $section->addText('Rate security features (1-5, where 5 is excellent):');
    $section->addText('HTTPS Connection: ___/5');
    $section->addText('Session Management: ___/5');
    $section->addText('Data Protection: ___/5');
    $section->addText('Access Control: ___/5');
    $section->addTextBreak();
    
    $section->addText('Performance Issues:');
    $section->addText('□ Slow page loading');
    $section->addText('□ Timeout errors');
    $section->addText('□ Connection drops');
    $section->addText('□ Memory issues');
    $section->addText('□ Other: _________________');
    $section->addTextBreak();
    
    $section->addText('Comments:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    // Section 5: Mobile Experience
    $section->addText('SECTION 5: MOBILE EXPERIENCE', ['size' => 12, 'bold' => true]);
    $section->addText('Rate mobile functionality (1-5, where 5 is excellent):');
    $section->addText('Mobile Layout: ___/5');
    $section->addText('Touch Navigation: ___/5');
    $section->addText('Form Input: ___/5');
    $section->addText('File Upload: ___/5');
    $section->addText('PDF Viewing: ___/5');
    $section->addTextBreak();
    
    $section->addText('Mobile-Specific Issues:');
    $section->addText('□ Layout problems');
    $section->addText('□ Text too small');
    $section->addText('□ Buttons hard to tap');
    $section->addText('□ Forms difficult to fill');
    $section->addText('□ Other: _________________');
    $section->addTextBreak();
    
    $section->addText('Comments:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    // Section 6: Specific Features Testing
    $section->addText('SECTION 6: SPECIFIC FEATURES TESTING', ['size' => 12, 'bold' => true]);
    $section->addText('Test Results (Pass/Fail/Not Tested):');
    $section->addText('□ Student Registration: ___/___/___');
    $section->addText('□ Document Upload: ___/___/___');
    $section->addText('□ F2 Form Generation: ___/___/___');
    $section->addText('□ Test Permit Generation: ___/___/___');
    $section->addText('□ Test Results Entry: ___/___/___');
    $section->addText('□ PDF Download: ___/___/___');
    $section->addText('□ Admin Dashboard: ___/___/___');
    $section->addText('□ Student Dashboard: ___/___/___');
    $section->addText('□ Login/Logout: ___/___/___');
    $section->addText('□ Password Reset: ___/___/___');
    $section->addTextBreak();
    
    $section->addText('Feature-Specific Comments:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    // Section 7: Bug Reports
    $section->addText('SECTION 7: BUG REPORTS', ['size' => 12, 'bold' => true]);
    
    for ($i = 1; $i <= 3; $i++) {
        $section->addText("Bug #$i:");
        $section->addText('Description: _________________________________________________');
        $section->addText('Steps to Reproduce: _________________________________________________');
        $section->addText('Expected Result: _________________________________________________');
        $section->addText('Actual Result: _________________________________________________');
        $section->addText('Severity: □ Critical □ High □ Medium □ Low');
        $section->addTextBreak();
    }
    
    // Section 8: Suggestions
    $section->addText('SECTION 8: SUGGESTIONS FOR IMPROVEMENT', ['size' => 12, 'bold' => true]);
    $section->addText('User Experience Improvements:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    $section->addText('Feature Requests:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    $section->addText('Design Suggestions:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    // Section 9: Overall Assessment
    $section->addText('SECTION 9: OVERALL ASSESSMENT', ['size' => 12, 'bold' => true]);
    $section->addText('Would you recommend this system to other users?');
    $section->addText('□ Yes □ No □ Maybe');
    $section->addTextBreak();
    
    $section->addText('Reason: _________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    $section->addText('What is the most positive aspect of the system?');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    $section->addText('What needs the most improvement?');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    $section->addText('Additional Comments:');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addText('_________________________________________________');
    $section->addTextBreak();
    
    $section->addText('Tester Signature: _________________ Date: _________________');
    $section->addTextBreak();
    $section->addText('Thank you for your valuable feedback!', ['italic' => true], ['alignment' => 'center']);
    
    // Save the document
    $filename = __DIR__ . '/../docs/Website_Testing_Feedback_Form.docx';
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($filename);
    
    echo "✅ Feedback form created successfully!\n";
    echo "📁 File saved to: $filename\n";
    echo "📝 You can now open this Word document and print or share it for testing.\n\n";
    
} catch (Exception $e) {
    echo "❌ Error creating feedback form: " . $e->getMessage() . "\n";
    echo "💡 Make sure PHPWord is installed: composer require phpoffice/phpword\n";
}
?>
