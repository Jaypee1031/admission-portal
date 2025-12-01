<?php
// Admission form management functions
require_once 'courses.php';

class AdmissionForm {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Save admission form data
    public function saveAdmissionForm($studentId, $formData) {
        try {
            error_log("saveAdmissionForm called with studentId: " . $studentId);
            error_log("Form data received: " . print_r($formData, true));
            error_log("Ethnic affiliation in formData: " . ($formData['ethnic_affiliation'] ?? 'NOT SET'));
            error_log("Ethnic others specify in formData: " . ($formData['ethnic_others_specify'] ?? 'NOT SET'));
            
            // Prepare form data with defaults
            $data = [
                'student_id' => $studentId,
                'last_name' => $formData['last_name'] ?? '',
                'first_name' => $formData['first_name'] ?? '',
                'middle_name' => $formData['middle_name'] ?? '',
                'name_extension' => $formData['name_extension'] ?? '',
                'sex' => $formData['sex'] ?? '',
                'gender' => $formData['gender'] ?? '',
                'gender_specify' => $formData['gender_specify'] ?? '',
                'civil_status' => $formData['civil_status'] ?? '',
                'spouse_name' => $formData['spouse_name'] ?? '',
                'age' => (int)($formData['age'] ?? 0),
                'birth_date' => $formData['birth_date'] ?? '',
                'birth_place' => $formData['birth_place'] ?? '',
                'pwd' => (int)($formData['pwd'] ?? 0),
                'disability' => $formData['disability'] ?? '',
                'ethnic_affiliation' => $formData['ethnic_affiliation'] ?? '',
                'ethnic_others_specify' => $formData['ethnic_others_specify'] ?? '',
                'home_address' => $formData['home_address'] ?? '',
                'mobile_number' => $formData['mobile_number'] ?? '',
                'email_address' => $formData['email_address'] ?? '',
                'father_name' => $formData['father_name'] ?? '',
                'father_occupation' => $formData['father_occupation'] ?? '',
                'father_contact' => $formData['father_contact'] ?? '',
                'mother_name' => $formData['mother_name'] ?? '',
                'mother_occupation' => $formData['mother_occupation'] ?? '',
                'mother_contact' => $formData['mother_contact'] ?? '',
                'guardian_name' => $formData['guardian_name'] ?? '',
                'guardian_occupation' => $formData['guardian_occupation'] ?? '',
                'guardian_contact' => $formData['guardian_contact'] ?? '',
                'last_school' => $formData['last_school'] ?? '',
                'school_address' => $formData['school_address'] ?? '',
                'year_last_attended' => $formData['year_last_attended'] ?? '',
                'strand_taken' => $formData['strand_taken'] ?? '',
                'grade_12_gwa' => $formData['grade_12_gwa'] ?? null,
                'year_graduated' => $formData['year_graduated'] ?? '',
                'course_first' => $formData['course_first'] ?? '',
                'course_second' => $formData['course_second'] ?? '',
                'course_third' => $formData['course_third'] ?? '',
                'profile_photo' => $formData['profile_photo'] ?? null
            ];
            
            error_log("Prepared data array - ethnic_affiliation: " . ($data['ethnic_affiliation'] ?? 'NOT SET'));
            error_log("Prepared data array - ethnic_others_specify: " . ($data['ethnic_others_specify'] ?? 'NOT SET'));
            
            // Check if form already exists
            $stmt = $this->db->prepare("SELECT id FROM admission_forms WHERE student_id = ?");
            $stmt->execute([$studentId]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Updating existing form for student: " . $studentId);
                
                // Update existing form
                $sql = "UPDATE admission_forms SET 
                    last_name = :last_name, first_name = :first_name, middle_name = :middle_name, name_extension = :name_extension,
                    sex = :sex, gender = :gender, gender_specify = :gender_specify, civil_status = :civil_status, spouse_name = :spouse_name,
                    age = :age, birth_date = :birth_date, birth_place = :birth_place, pwd = :pwd, disability = :disability, ethnic_affiliation = :ethnic_affiliation, ethnic_others_specify = :ethnic_others_specify,
                    home_address = :home_address, mobile_number = :mobile_number, email_address = :email_address,
                    father_name = :father_name, father_occupation = :father_occupation, father_contact = :father_contact,
                    mother_name = :mother_name, mother_occupation = :mother_occupation, mother_contact = :mother_contact,
                    guardian_name = :guardian_name, guardian_occupation = :guardian_occupation, guardian_contact = :guardian_contact,
                    last_school = :last_school, school_address = :school_address, year_last_attended = :year_last_attended, strand_taken = :strand_taken, grade_12_gwa = :grade_12_gwa, year_graduated = :year_graduated,
                    course_first = :course_first, course_second = :course_second, course_third = :course_third,
                    profile_photo = :profile_photo
                    WHERE student_id = :student_id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($data);
                
                $formId = $this->db->prepare("SELECT id FROM admission_forms WHERE student_id = ?")->execute([$studentId]) ? 
                         $this->db->prepare("SELECT id FROM admission_forms WHERE student_id = ?")->fetchColumn() : null;
            } else {
                error_log("Inserting new form for student: " . $studentId);
                
                // Insert new form
                $sql = "INSERT INTO admission_forms (
                    student_id, last_name, first_name, middle_name, name_extension,
                    sex, gender, gender_specify, civil_status, spouse_name,
                    age, birth_date, birth_place, pwd, disability, ethnic_affiliation, ethnic_others_specify,
                    home_address, mobile_number, email_address,
                    father_name, father_occupation, father_contact,
                    mother_name, mother_occupation, mother_contact,
                    guardian_name, guardian_occupation, guardian_contact,
                    last_school, school_address, year_last_attended, strand_taken, grade_12_gwa, year_graduated,
                    course_first, course_second, course_third, profile_photo
                ) VALUES (
                    :student_id, :last_name, :first_name, :middle_name, :name_extension,
                    :sex, :gender, :gender_specify, :civil_status, :spouse_name,
                    :age, :birth_date, :birth_place, :pwd, :disability, :ethnic_affiliation, :ethnic_others_specify,
                    :home_address, :mobile_number, :email_address,
                    :father_name, :father_occupation, :father_contact,
                    :mother_name, :mother_occupation, :mother_contact,
                    :guardian_name, :guardian_occupation, :guardian_contact,
                    :last_school, :school_address, :year_last_attended, :strand_taken, :grade_12_gwa, :year_graduated,
                    :course_first, :course_second, :course_third, :profile_photo
                )";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($data);
                
                $formId = $this->db->lastInsertId();
            }
            
            error_log("Form saved successfully for student: " . $studentId . ", form_id: " . $formId);
            return ['success' => true, 'message' => 'Admission form saved successfully', 'form_id' => $formId];
            
        } catch (PDOException $e) {
            error_log("Database error in saveAdmissionForm: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get admission form data
    public function getAdmissionForm($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM admission_forms WHERE student_id = ?");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Check if admission form exists
    public function hasAdmissionForm($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM admission_forms WHERE student_id = ?");
            $stmt->execute([$studentId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get available courses
    public function getAvailableCourses() {
        // Try to load from courses table first
        try {
            $coursesManager = new Courses();
            $courses = $coursesManager->getActiveCourses();
            if (!empty($courses)) {
                return $courses;
            }
        } catch (Exception $e) {
            // Ignore and fall back to defaults
        }

        // Fallback: original hard-coded list
        return [
            // BOARD COURSES
            'Bachelor of Science in Agriculture major in Animal Science, Crop Science (BSA)',
            'Bachelor of Science in Agricultural Biosystems Engineering (BSABE)',
            'Bachelor of Science in Forestry (BSF)',
            'Bachelor of Science in Nutrition and Dietetics (BSND)',
            'Bachelor in Elementary Education (BEED)',
            'Bachelor of Secondary Education major in Filipino, Science, Math, English (BSED)',
            'Bachelor in Technology and Livelihood Education (BTLED)',
            'Bachelor of Science in Criminology (BS Crim)',
            
            // NON-BOARD COURSES
            'Bachelor of Science in Information Technology (BSIT)',
            'Bachelor of Science in Office Administration (BSOA)',
            'Bachelor of Science in Hospitality Management (BSHM)',
            'Bachelor of Science in Tourism Management (BSTM)',
            'Caregiving Course (CGC)'
        ];
    }
    
    // Get available strands (for Senior High School)
    public function getAvailableStrands() {
        return [
            'STEM (Science, Technology, Engineering, and Mathematics)',
            'ABM (Accountancy, Business, and Management)',
            'HUMSS (Humanities and Social Sciences)',
            'GAS (General Academic Strand)',
            'TVL-ICT (Technical-Vocational-Livelihood - Information and Communications Technology)',
            'TVL-HE (Technical-Vocational-Livelihood - Home Economics)',
            'TVL-IA (Technical-Vocational-Livelihood - Industrial Arts)',
            'TVL-AGRI (Technical-Vocational-Livelihood - Agriculture)',
            'TVL-FISH (Technical-Vocational-Livelihood - Fisheries)'
        ];
    }
}

// Initialize AdmissionForm class
$admissionForm = new AdmissionForm();
?>
