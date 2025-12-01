<?php
// Requirements management functions

class Requirements {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Get requirements list based on student type and marital status
    public function getRequirementsList($studentType, $studentId = null) {
        $requirements = [];
        
        // Check if student is married
        $isMarried = false;
        if ($studentId) {
            $stmt = $this->db->prepare("SELECT civil_status FROM admission_forms WHERE student_id = ?");
            $stmt->execute([$studentId]);
            $admissionForm = $stmt->fetch();
            $isMarried = ($admissionForm && $admissionForm['civil_status'] === 'Married');
        }
        
        if ($studentType === 'Freshman') {
            $requirements = [
                'grade_12_report_card' => 'Certified True Copy of Grade 12 report card (1st sem)',
                'good_moral_character' => 'Certificate of Good Moral Character',
                'birth_certificate' => 'NSO/PSA Birth Certificate (2 photocopies)',
                'id_pictures' => '2x2 ID picture (6pcs) – with name tag and white background'
            ];
            
            // Add marriage certificate only if student is married
            if ($isMarried) {
                $requirements['marriage_certificate'] = 'Marriage Certificate (2 photocopies)';
            }
        } elseif ($studentType === 'Transferee') {
            $requirements = [
                'transfer_credential' => 'Certificate of Transfer Credential (original and photocopy)',
                'complete_grades' => 'Certification of Complete Grades (original and photocopy)',
                'good_moral_character' => 'Certificate of Good Moral Character (original and photocopy)',
                'birth_certificate' => 'NSO/PSA Birth Certificate (2 photocopies)',
                'id_pictures' => '2x2 ID picture (6pcs) – with name tag and white background'
            ];
            
            // Add marriage certificate only if student is married
            if ($isMarried) {
                $requirements['marriage_certificate'] = 'Marriage Certificate (2 photocopies)';
            }
        }
        
        return $requirements;
    }
    
    // Upload requirement file
    public function uploadRequirement($studentId, $documentName, $file) {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = UPLOAD_PATH . 'requirements/' . $studentId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = $documentName . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Save to database
                $stmt = $this->db->prepare("
                    INSERT INTO requirements (student_id, document_name, file_path, file_type, file_size, status) 
                    VALUES (?, ?, ?, ?, ?, 'Pending')
                ");
                
                $relativePath = 'uploads/requirements/' . $studentId . '/' . $fileName;
                $stmt->execute([
                    $studentId,
                    $documentName,
                    $relativePath,
                    $file['type'],
                    $file['size']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'file_id' => $this->db->lastInsertId()
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to upload file'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Validate uploaded file with server-side verification
    private function validateFile($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File size exceeds maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
        }
        
        // Check file size is not zero
        if ($file['size'] <= 0) {
            return ['success' => false, 'message' => 'File is empty'];
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            return ['success' => false, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS)];
        }
        
        // Server-side MIME type validation using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Validate MIME type against allowed types
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            error_log("Invalid MIME type detected: " . $mimeType . " for file: " . $file['name']);
            return ['success' => false, 'message' => 'Invalid file type. Server validation failed.'];
        }
        
        // Additional validation for images - check image dimensions
        if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return ['success' => false, 'message' => 'Invalid image file'];
            }
        }
        
        return ['success' => true];
    }
    
    // Get student requirements
    public function getStudentRequirements($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM requirements 
                WHERE student_id = ? 
                ORDER BY 
                    CASE document_name
                        WHEN 'grade_12_report_card' THEN 1
                        WHEN 'good_moral_character' THEN 2
                        WHEN 'birth_certificate' THEN 3
                        WHEN 'marriage_certificate' THEN 4
                        WHEN 'id_pictures' THEN 5
                        WHEN 'brown_folder' THEN 6
                        ELSE 7
                    END,
                    uploaded_at DESC
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Check if all requirements are uploaded
    public function areAllRequirementsUploaded($studentId, $studentType) {
        $requirementsList = $this->getRequirementsList($studentType, $studentId);
        $uploadedRequirements = $this->getStudentRequirements($studentId);
        
        $uploadedDocs = [];
        foreach ($uploadedRequirements as $req) {
            $uploadedDocs[] = $req['document_name'];
        }
        
        foreach ($requirementsList as $key => $name) {
            if (!in_array($key, $uploadedDocs)) {
                return false;
            }
        }
        
        return true;
    }
    
    // Delete requirement
    public function deleteRequirement($requirementId, $studentId) {
        try {
            // Get file path
            $stmt = $this->db->prepare("SELECT file_path FROM requirements WHERE id = ? AND student_id = ?");
            $stmt->execute([$requirementId, $studentId]);
            $requirement = $stmt->fetch();
            
            if (!$requirement) {
                return ['success' => false, 'message' => 'Requirement not found'];
            }
            
            // Delete file
            $filePath = ROOT_PATH . '/' . $requirement['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from database
            $stmt = $this->db->prepare("DELETE FROM requirements WHERE id = ? AND student_id = ?");
            $stmt->execute([$requirementId, $studentId]);
            
            return ['success' => true, 'message' => 'Requirement deleted successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get requirement statistics
    public function getRequirementStats($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                FROM requirements 
                WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        }
    }
}

// Initialize Requirements class
$requirements = new Requirements();
?>
