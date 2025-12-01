<?php
require_once __DIR__ . '/../config/database.php';

class F2PersonalDataForm {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Enable Personal Data form access for a student (admin only)
     */
    public function enableF2Form($studentId) {
        try {
            $stmt = $this->db->prepare("UPDATE students SET f2_form_enabled = 1 WHERE id = ?");
            $stmt->execute([$studentId]);
            
            // Refresh student session if they are currently logged in
            $auth = new Auth();
            $auth->refreshStudentSession($studentId);
            
            return ['success' => true, 'message' => 'Personal Data form access enabled for student'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to enable Personal Data form access'];
        }
    }
    
    /**
     * Disable Personal Data form access for a student (admin only)
     */
    public function disableF2Form($studentId) {
        try {
            $stmt = $this->db->prepare("UPDATE students SET f2_form_enabled = 0 WHERE id = ?");
            $stmt->execute([$studentId]);
            
            // Refresh student session if they are currently logged in
            $auth = new Auth();
            $auth->refreshStudentSession($studentId);
            
            return ['success' => true, 'message' => 'Personal Data form access disabled for student'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to disable Personal Data form access'];
        }
    }
    
    /**
     * Check if Personal Data form is enabled for a student
     */
    public function isF2FormEnabled($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT f2_form_enabled FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $result = $stmt->fetch();
            return $result ? (bool)$result['f2_form_enabled'] : false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Check if Personal Data form is completed by a student
     */
    public function isF2FormCompleted($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT f2_form_completed FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $result = $stmt->fetch();
            return $result ? (bool)$result['f2_form_completed'] : false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get siblings data for a student
     */
    public function getSiblings($studentId) {
        try {
            // Get siblings from siblings_info column
            $stmt = $this->db->prepare("SELECT siblings_info FROM f2_personal_data_forms WHERE student_id = ?");
            $stmt->execute([$studentId]);
            $formData = $stmt->fetch();
            
            if (!$formData || empty($formData['siblings_info'])) {
                return [];
            }
            
            $siblingsInfo = $formData['siblings_info'];
            $siblings = [];
            
            // Parse siblings info - format: "Name (1st)\nName2 (2nd)"
            $lines = explode("\n", $siblingsInfo);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Extract name and birth order from format "Name (1st)"
                if (preg_match('/^(.+?)\s*\((\d+)(?:st|nd|rd|th)\)$/', $line, $matches)) {
                    $name = trim($matches[1]);
                    $birthOrder = (int)$matches[2];
                    
                    if (!empty($name)) {
                        $siblings[] = [
                            'sibling_name' => $name,
                            'birth_order' => $birthOrder
                        ];
                    }
                } else if (!empty($line)) {
                    // Fallback for simple name format
                    $siblings[] = [
                        'sibling_name' => $line,
                        'birth_order' => count($siblings) + 1
                    ];
                }
            }
            
            // Sort by birth order
            usort($siblings, function($a, $b) {
                return $a['birth_order'] - $b['birth_order'];
            });
            
            return $siblings;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Save siblings data for a student
     */
    public function saveSiblings($studentId, $siblingsData) {
        try {
            // Format siblings data as text
            $siblingsText = '';
            if (!empty($siblingsData)) {
                $siblingsList = [];
                foreach ($siblingsData as $sibling) {
                    if (!empty(trim($sibling['name']))) {
                        $order = $sibling['order'];
                        $suffix = $order == 1 ? 'st' : ($order == 2 ? 'nd' : ($order == 3 ? 'rd' : 'th'));
                        $siblingsList[] = trim($sibling['name']) . " ($order$suffix)";
                    }
                }
                $siblingsText = implode("\n", $siblingsList);
            }
            
            // Update siblings_info column
            $stmt = $this->db->prepare("UPDATE f2_personal_data_forms SET siblings_info = ? WHERE student_id = ?");
            $stmt->execute([$siblingsText, $studentId]);
            
            return ['success' => true, 'message' => 'Siblings data saved successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to save siblings data'];
        }
    }
    
    /**
     * Get Personal Data form data for a student
     */
    public function getF2FormData($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM f2_personal_data_forms WHERE student_id = ?");
            $stmt->execute([$studentId]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Convert individual columns to the expected format
                $formData = [
                    'personal_info' => [
                        'last_name' => $result['last_name'] ?? '',
                        'first_name' => $result['first_name'] ?? '',
                        'middle_name' => $result['middle_name'] ?? '',
                        'civil_status' => $result['civil_status'] ?? '',
                        'spouse_name' => $result['spouse_name'] ?? '',
                        'course_year_level' => $result['course_year_level'] ?? '',
                        'sex' => $result['sex'] ?? '',
                        'ethnicity' => $result['ethnicity'] ?? '',
                        'ethnicity_others_specify' => $result['ethnicity_others_specify'] ?? '',
                        'date_of_birth' => $result['date_of_birth'] ?? '',
                        'age' => $result['age'] ?? '',
                        'place_of_birth' => $result['place_of_birth'] ?? '',
                        'religion' => $result['religion'] ?? '',
                        'address' => $result['address'] ?? '',
                        'contact_number' => $result['contact_number'] ?? '',
                        'email' => $result['email'] ?? ''
                    ],
                    'family_info' => [
                        'father_name' => $result['father_name'] ?? '',
                        'father_occupation' => $result['father_occupation'] ?? '',
                        'father_ethnicity' => $result['father_ethnicity'] ?? '',
                        'mother_name' => $result['mother_name'] ?? '',
                        'mother_occupation' => $result['mother_occupation'] ?? '',
                        'mother_ethnicity' => $result['mother_ethnicity'] ?? '',
                        'parents_living_together' => $result['parents_living_together'] ?? '',
                        'parents_separated' => $result['parents_separated'] ?? '',
                        'separation_reason' => $result['separation_reason'] ?? '',
                        'living_with' => $result['living_with'] ?? '',
                        'age_when_separated' => $result['age_when_separated'] ?? '',
                        'guardian_name' => $result['guardian_name'] ?? '',
                        'guardian_relationship' => $result['guardian_relationship'] ?? '',
                        'guardian_address' => $result['guardian_address'] ?? '',
                        'guardian_contact_number' => $result['guardian_contact_number'] ?? '',
                        'siblings_info' => $result['siblings_info'] ?? ''
                    ],
                    'education' => [
                        'elementary_school' => $result['elementary_school'] ?? '',
                        'secondary_school' => $result['secondary_school'] ?? '',
                        'school_university_last_attended' => $result['school_university_last_attended'] ?? '',
                        'school_name' => $result['school_name'] ?? '',
                        'school_address' => $result['school_address'] ?? '',
                        'general_average' => $result['general_average'] ?? '',
                        'gwa_1st_sem' => $result['gwa_1st_sem'] ?? '',
                        'gwa_2nd_sem' => $result['gwa_2nd_sem'] ?? '',
                        'course_first_choice' => $result['course_first_choice'] ?? '',
                        'course_second_choice' => $result['course_second_choice'] ?? '',
                        'course_third_choice' => $result['course_third_choice'] ?? '',
                        'parents_choice' => $result['parents_choice'] ?? '',
                        'nature_of_schooling_continuous' => $result['nature_of_schooling_continuous'] ?? '',
                        'reason_if_interrupted' => $result['reason_if_interrupted'] ?? '',
                        'year_graduated' => $result['year_graduated'] ?? '',
                        'strand_taken' => $result['strand_taken'] ?? '',
                        'reason_for_choice' => $result['reason_for_choice'] ?? '',
                        'career_plans' => $result['career_plans'] ?? ''
                    ],
                    'skills' => [
                        'talents' => $result['talents'] ?? '',
                        'awards' => $result['awards'] ?? '',
                        'hobbies' => $result['hobbies'] ?? ''
                    ],
                    'health_record' => [
                        'disability_specify' => $result['disability_specify'] ?? '',
                        'confined_rehabilitated' => $result['confined_rehabilitated'] ?? '',
                        'confined_when' => $result['confined_when'] ?? '',
                        'treated_for_illness' => $result['treated_for_illness'] ?? '',
                        'treated_when' => $result['treated_when'] ?? ''
                    ],
                    'declaration' => [
                        'signature_over_printed_name' => $result['signature_over_printed_name'] ?? '',
                        'date_accomplished' => $result['date_accomplished'] ?? ''
                    ]
                ];
                
                // Add metadata fields
                $formData['submitted_at'] = $result['submitted_at'] ?? null;
                $formData['created_at'] = $result['created_at'] ?? null;
                $formData['updated_at'] = $result['updated_at'] ?? null;
                
                return $formData;
            }
            
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Save Personal Data form data
     */
    public function saveF2FormData($studentId, $formData) {
        try {
            $existingForm = $this->getF2FormData($studentId);
            
            // Extract data from nested arrays
            $personalInfo = $formData['personal_info'] ?? [];
            $familyInfo = $formData['family_info'] ?? [];
            $education = $formData['education'] ?? [];
            $skills = $formData['skills'] ?? [];
            $healthRecord = $formData['health_record'] ?? [];
            $declaration = $formData['declaration'] ?? [];
            
            // Prepare data array
            $data = [
                'student_id' => $studentId,
                'last_name' => $personalInfo['last_name'] ?? '',
                'first_name' => $personalInfo['first_name'] ?? '',
                'middle_name' => $personalInfo['middle_name'] ?? '',
                'civil_status' => $personalInfo['civil_status'] ?? '',
                'spouse_name' => $personalInfo['spouse_name'] ?? '',
                'course_year_level' => $personalInfo['course_year_level'] ?? '',
                'sex' => $personalInfo['sex'] ?? '',
                'ethnicity' => $personalInfo['ethnicity'] ?? '',
                'ethnicity_others_specify' => $personalInfo['ethnicity_others_specify'] ?? '',
                'date_of_birth' => $personalInfo['date_of_birth'] ?? '',
                'age' => $personalInfo['age'] ?? null,
                'place_of_birth' => $personalInfo['place_of_birth'] ?? '',
                'religion' => $personalInfo['religion'] ?? '',
                'address' => $personalInfo['address'] ?? '',
                'contact_number' => $personalInfo['contact_number'] ?? '',
                'father_name' => $familyInfo['father_name'] ?? '',
                'father_occupation' => $familyInfo['father_occupation'] ?? '',
                'father_ethnicity' => $familyInfo['father_ethnicity'] ?? '',
                'mother_name' => $familyInfo['mother_name'] ?? '',
                'mother_occupation' => $familyInfo['mother_occupation'] ?? '',
                'mother_ethnicity' => $familyInfo['mother_ethnicity'] ?? '',
                'parents_living_together' => $familyInfo['parents_living_together'] ?? '',
                'parents_separated' => $familyInfo['parents_separated'] ?? '',
                'separation_reason' => $familyInfo['separation_reason'] ?? '',
                'living_with' => $familyInfo['living_with'] ?? '',
                'age_when_separated' => $familyInfo['age_when_separated'] ?? null,
                'guardian_name' => $familyInfo['guardian_name'] ?? '',
                'guardian_relationship' => $familyInfo['guardian_relationship'] ?? '',
                'guardian_address' => $familyInfo['guardian_address'] ?? '',
                'guardian_contact_number' => $familyInfo['guardian_contact_number'] ?? '',
                'siblings_info' => $familyInfo['siblings_info'] ?? '',
                'elementary_school' => $education['elementary_school'] ?? '',
                'secondary_school' => $education['secondary_school'] ?? '',
                'school_university_last_attended' => $education['school_university_last_attended'] ?? '',
                'school_name' => $education['school_name'] ?? '',
                'school_address' => $education['school_address'] ?? '',
                'general_average' => $education['general_average'] ?? null,
                'course_first_choice' => $education['course_first_choice'] ?? '',
                'course_second_choice' => $education['course_second_choice'] ?? '',
                'course_third_choice' => $education['course_third_choice'] ?? '',
                'parents_choice' => $education['parents_choice'] ?? '',
                'nature_of_schooling_continuous' => $education['nature_of_schooling_continuous'] ?? '',
                'reason_if_interrupted' => $education['reason_if_interrupted'] ?? '',
                'talents' => $skills['talents'] ?? '',
                'awards' => $skills['awards'] ?? '',
                'hobbies' => $skills['hobbies'] ?? '',
                'disability_specify' => $healthRecord['disability_specify'] ?? '',
                'confined_rehabilitated' => $healthRecord['confined_rehabilitated'] ?? '',
                'confined_when' => $healthRecord['confined_when'] ?? '',
                'treated_for_illness' => $healthRecord['treated_for_illness'] ?? '',
                'treated_when' => $healthRecord['treated_when'] ?? '',
                'signature_over_printed_name' => $declaration['signature_over_printed_name'] ?? '',
                'date_accomplished' => $declaration['date_accomplished'] ?? ''
            ];
            
            if ($existingForm) {
                // Update existing form
                $setClause = [];
                $values = [];
                
                foreach ($data as $field => $value) {
                    if ($field !== 'student_id') {
                        $setClause[] = "$field = ?";
                        $values[] = $value;
                    }
                }
                
                $setClause[] = "submitted_at = NOW()";
                $setClause[] = "updated_at = NOW()";
                $values[] = $studentId;
                
                $sql = "UPDATE f2_personal_data_forms SET " . implode(', ', $setClause) . " WHERE student_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($values);
            } else {
                // Insert new form
                $fields = array_keys($data);
                $placeholders = array_fill(0, count($fields), '?');
                
                $fields[] = 'submitted_at';
                $fields[] = 'created_at';
                $fields[] = 'updated_at';
                $placeholders[] = 'NOW()';
                $placeholders[] = 'NOW()';
                $placeholders[] = 'NOW()';
                
                $sql = "INSERT INTO f2_personal_data_forms (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array_values($data));
            }
            
            // Mark Personal Data form as completed
            $stmt = $this->db->prepare("UPDATE students SET f2_form_completed = 1 WHERE id = ?");
            $stmt->execute([$studentId]);
            
            // Refresh student session if they are currently logged in
            $auth = new Auth();
            $auth->refreshStudentSession($studentId);
            
            return ['success' => true, 'message' => 'Personal Data form data saved successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to save Personal Data form data: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all students with Personal Data form status
     */
    public function getAllStudentsWithF2Status() {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, 
                       CASE WHEN f2.id IS NOT NULL THEN 1 ELSE 0 END as has_f2_form,
                       f2.submitted_at
                FROM students s
                LEFT JOIN f2_personal_data_forms f2 ON s.id = f2.student_id
                ORDER BY s.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get Personal Data form statistics
     */
    public function getF2FormStats() {
        try {
            $stats = [];
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM students");
            $stmt->execute();
            $stats['total_students'] = $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM students WHERE f2_form_enabled = 1");
            $stmt->execute();
            $stats['f2_enabled'] = $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM students WHERE f2_form_completed = 1");
            $stmt->execute();
            $stats['f2_completed'] = $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM students WHERE f2_form_enabled = 1 AND f2_form_completed = 0");
            $stmt->execute();
            $stats['f2_pending'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return ['total_students' => 0, 'f2_enabled' => 0, 'f2_completed' => 0, 'f2_pending' => 0];
        }
    }
}
?>
