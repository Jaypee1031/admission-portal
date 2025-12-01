<?php
// Simple test PDF to verify basic functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple PDF content
$pdf = "%PDF-1.4\n";
$pdf .= "1 0 obj\n";
$pdf .= "<<\n";
$pdf .= "/Type /Catalog\n";
$pdf .= "/Pages 2 0 R\n";
$pdf .= ">>\n";
$pdf .= "endobj\n\n";

$pdf .= "2 0 obj\n";
$pdf .= "<<\n";
$pdf .= "/Type /Pages\n";
$pdf .= "/Count 1\n";
$pdf .= "/Kids [3 0 R]\n";
$pdf .= ">>\n";
$pdf .= "endobj\n\n";

$pdf .= "3 0 obj\n";
$pdf .= "<<\n";
$pdf .= "/Type /Page\n";
$pdf .= "/Parent 2 0 R\n";
$pdf .= "/MediaBox [0 0 595 842]\n";
$pdf .= "/Contents 4 0 R\n";
$pdf .= "/Resources <<\n";
$pdf .= "/ProcSet [/PDF /Text]\n";
$pdf .= "/Font <<\n";
$pdf .= "/F1 << /Type /Font /Subtype /Type1 /Name /F1 /BaseFont /Helvetica /Encoding /MacRomanEncoding >>\n";
$pdf .= ">>\n";
$pdf .= ">>\n";
$pdf .= ">>\n";
$pdf .= "endobj\n\n";

// Simple content
$content = "BT\n";
$content .= "/F1 12 Tf\n";
$content .= "0 0 0 rg\n";
$content .= "50 800 Td\n";
$content .= "(Test PDF - If you can see this, PDF generation works!) Tj\n";
$content .= "ET\n";

$pdf .= "4 0 obj\n";
$pdf .= "<<\n";
$pdf .= "/Length " . strlen($content) . "\n";
$pdf .= ">>\n";
$pdf .= "stream\n";
$pdf .= $content;
$pdf .= "endstream\n";
$pdf .= "endobj\n\n";

$pdf .= "xref\n";
$pdf .= "0 5\n";
$pdf .= "0000000000 65535 f \n";
$pdf .= "0000000009 00000 n \n";
$pdf .= "0000000058 00000 n \n";
$pdf .= "0000000115 00000 n \n";
$pdf .= "0000000200 00000 n \n";

$pdf .= "trailer\n";
$pdf .= "<<\n";
$pdf .= "/Size 5\n";
$pdf .= "/Root 1 0 R\n";
$pdf .= ">>\n";
$pdf .= "startxref\n";
$pdf .= (strlen($pdf) + 20) . "\n";
$pdf .= "%%EOF\n";

// Set headers for PDF display
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="test.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdf;
?>
