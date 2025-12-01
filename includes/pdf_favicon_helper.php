<?php
/**
 * PDF Favicon Helper
 * Provides favicon functionality for PDF documents
 */

class PDFFaviconHelper {
    
    /**
     * Add favicon to PDF document
     * @param TCPDF $pdf The PDF object
     * @param string $faviconPath Path to favicon file
     * @param float $x X position (default: 5)
     * @param float $y Y position (default: 5)
     * @param float $width Width in mm (default: 10)
     */
    public static function addFaviconToPDF($pdf, $faviconPath = 'assets/images/favicon_io/favicon-32x32.png', $x = 5, $y = 5, $width = 10) {
        try {
            // Check if favicon file exists
            if (file_exists($faviconPath)) {
                // Add favicon as a small image in the top-left corner
                $pdf->Image($faviconPath, $x, $y, $width, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
                
                // Add some spacing after the favicon
                $pdf->SetY($y + $width + 2);
            }
        } catch (Exception $e) {
            // Silently fail if favicon cannot be added
            error_log('Failed to add favicon to PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Set PDF metadata with favicon information
     * @param TCPDF $pdf The PDF object
     * @param string $title Document title
     * @param string $subject Document subject
     * @param string $keywords Document keywords
     */
    public static function setPDFMetadata($pdf, $title, $subject, $keywords) {
        $pdf->SetCreator('Quirino State University');
        $pdf->SetAuthor('OGCA');
        $pdf->SetTitle($title);
        $pdf->SetSubject($subject);
        $pdf->SetKeywords($keywords);
        
        // Note: TCPDF doesn't support custom metadata fields like SetMetadata()
        // The favicon will be added as an image in the PDF content instead
    }
    
    /**
     * Add QSU header with favicon to PDF
     * @param TCPDF $pdf The PDF object
     * @param string $title Header title
     * @param float $x X position (default: 15)
     * @param float $y Y position (default: 10)
     */
    public static function addQSUHeader($pdf, $title, $x = 15, $y = 10) {
        try {
            // Add favicon
            if (file_exists('assets/images/favicon_io/favicon-32x32.png')) {
                $pdf->Image('assets/images/favicon_io/favicon-32x32.png', $x, $y, 8, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
            }
            
            // Add title next to favicon
            $pdf->SetXY($x + 10, $y + 1);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(32, 55, 100); // QSU blue color
            $pdf->Cell(0, 8, $title, 0, 1, 'L');
            
            // Reset font and color
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            
            // Add some spacing
            $pdf->SetY($y + 15);
            
        } catch (Exception $e) {
            // Silently fail if header cannot be added
            error_log('Failed to add QSU header to PDF: ' . $e->getMessage());
        }
    }
}
