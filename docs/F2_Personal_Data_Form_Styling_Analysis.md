# F2 Personal Data Form - Styling Analysis

## Document Structure Analysis

### Page Layout
- **Paper Size**: Long Bond Paper (8.5" x 13")
- **Orientation**: Portrait
- **Margins**: 
  - Top: 15mm
  - Bottom: 20mm
  - Left: 15mm
  - Right: 15mm

### Typography Standards

#### Font Family
- **Primary Font**: Times New Roman (serif)
- **Fallback**: Arial (sans-serif)

#### Font Sizes
- **Document Title**: 14pt Bold
- **Section Headers**: 12pt Bold
- **Sub-section Headers**: 11pt Bold
- **Field Labels**: 10pt Bold
- **Field Values**: 10pt Regular
- **Small Text**: 9pt Regular
- **Footer Text**: 8pt Regular

#### Font Styles
- **Bold**: Headers, labels, important text
- **Regular**: Field values, body text
- **Italic**: Optional fields, notes
- **Underline**: Required fields, emphasis

### Color Scheme
- **Header Background**: Light Green (#B3E4A0)
- **Text Color**: Black (#000000)
- **Border Color**: Black (#000000)
- **Accent Color**: Dark Green (#2E7D32)

### Section Structure

#### 1. Header Section
- **University Logo**: 20x20mm, top-left
- **University Name**: 14pt Bold, centered
- **Office Name**: 12pt Regular, centered
- **Horizontal Line**: 2mm thick, full width
- **Form Title**: 14pt Bold, centered, green background

#### 2. Personal Information (Section I)
- **Section Header**: 12pt Bold, green background
- **Field Layout**: 2-column table
- **Field Height**: 6mm
- **Label Style**: Bold, left-aligned
- **Value Style**: Regular, left-aligned

#### 3. Family Information (Section II)
- **Section Header**: 12pt Bold, green background
- **Sub-sections**:
  - Father/Mother Information: 3-column table
  - Parents' Status: Complex layout with checkboxes
  - Guardian Information: 4-column table
  - Siblings Information: 2-column table

#### 4. Educational/Vocational (Section III)
- **Section Header**: 12pt Bold, green background
- **Sub-sections**:
  - Elementary/Secondary: Simple 2-column
  - Transferee Information: Complex 3-column layout
  - Course Choices: 2-column table with headers

#### 5. Skills/Talents (Section IV)
- **Section Header**: 12pt Bold, green background
- **Field Layout**: 2-column table
- **Multi-line fields**: For detailed descriptions

#### 6. Health Record (Section V)
- **Section Header**: 12pt Bold, green background
- **Field Layout**: 2-column table
- **Checkbox fields**: For yes/no questions

#### 7. Declaration (Section VI)
- **Section Header**: 12pt Bold, green background
- **Declaration Text**: 10pt Regular, justified
- **Signature Fields**: 2-column table
- **Date Field**: Right-aligned

#### 8. Footer Section
- **Document Control**: 8pt Regular, gray text
- **Student Information**: 8pt Regular
- **Official Use Section**: 10pt Bold, bordered

### Table Styling
- **Border Width**: 0.5pt
- **Cell Padding**: 2mm
- **Row Height**: 6mm (standard)
- **Header Rows**: Green background, bold text
- **Data Rows**: White background, regular text

### Checkbox Styling
- **Format**: ( ) Option Text
- **Selected**: (X) Option Text
- **Font Size**: 9pt Regular
- **Spacing**: 2 spaces between options

### Spacing Standards
- **Section Spacing**: 3mm between sections
- **Field Spacing**: 1mm between fields
- **Line Spacing**: 1.2x font size
- **Paragraph Spacing**: 2mm

### Alignment Standards
- **Headers**: Center-aligned
- **Labels**: Left-aligned
- **Values**: Left-aligned
- **Numbers**: Right-aligned
- **Dates**: Right-aligned

### Page Break Rules
- **Section Breaks**: Allow between major sections
- **Table Breaks**: Avoid splitting tables
- **Header Continuity**: Repeat section headers on new pages

### Quality Standards
- **Consistency**: Uniform styling throughout
- **Readability**: Clear hierarchy and spacing
- **Professional**: University-grade appearance
- **Accessibility**: High contrast, clear fonts
- **Printability**: Optimized for standard printers

## Implementation Notes

### TCPDF Specific Settings
```php
// Font settings
$this->pdf->SetFont('times', 'B', 12); // Bold 12pt
$this->pdf->SetFont('times', '', 10);  // Regular 10pt

// Color settings
$this->pdf->SetFillColor(179, 228, 160); // Light green
$this->pdf->SetTextColor(0, 0, 0);       // Black

// Cell settings
$this->pdf->Cell(180, 6, 'Text', 1, 1, 'C', true); // 180mm wide, 6mm high, bordered, centered, filled
```

### Responsive Design
- **Field Widths**: Proportional to content importance
- **Text Wrapping**: MultiCell for long text
- **Page Breaks**: Automatic with manual overrides
- **Margins**: Consistent across all pages

This analysis provides a comprehensive guide for implementing the F2 Personal Data Form with professional university standards.
