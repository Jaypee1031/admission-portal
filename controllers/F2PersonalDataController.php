<?php

class F2PersonalDataController extends Controller
{
    private Auth $auth;
    private PDO $db;
    private F2PersonalDataForm $f2Form;

    public function __construct()
    {
        $this->auth  = new Auth();
        $this->db    = getDB();
        $this->f2Form = new F2PersonalDataForm();
    }

    public function index(): void
    {
        requireStudent();

        $user = $this->auth->getCurrentUser();

        $progress = $this->auth->getStudentProgress($user['id']);
        if (!$progress['f2_form_enabled']) {
            showAlert('Personal Data Form is not available for your account. Please contact the administrator.', 'error');
            redirect('/student/dashboard');
        }

        // Get student's full name and GWA from database
        $stmt = $this->db->prepare('SELECT first_name, last_name, overall_gwa FROM students WHERE id = ?');
        $stmt->execute(array($user['id']));
        $studentData    = $stmt->fetch();
        $studentFullName = trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''));
        $studentGWA      = $studentData['overall_gwa'] ?? '';

        // Get GWA from F2 personal data form if available
        $stmt = $this->db->prepare('SELECT general_average FROM f2_personal_data_forms WHERE student_id = ?');
        $stmt->execute(array($user['id']));
        $f2Data = $stmt->fetch();
        $f2GWA  = $f2Data['general_average'] ?? '';

        // Get existing form data
        $existingData = $this->f2Form->getF2FormData($user['id']);
        $isCompleted  = $this->f2Form->isF2FormCompleted($user['id']);

        // Get admission form data to pre-fill Personal Data form
        $stmt = $this->db->prepare('SELECT * FROM admission_forms WHERE student_id = ?');
        $stmt->execute(array($user['id']));
        $admissionFormData = $stmt->fetch();

        $alert = getAlert();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Calculate age from birth date
            $age = null;
            if (!empty($_POST['date_of_birth'])) {
                $birthDate = new DateTime($_POST['date_of_birth']);
                $today     = new DateTime();
                $age       = $today->diff($birthDate)->y;
            }

            $formData = array(
                'personal_info' => array(
                    'last_name'                => sanitizeInput($_POST['last_name'] ?? ''),
                    'first_name'               => sanitizeInput($_POST['first_name'] ?? ''),
                    'middle_name'              => sanitizeInput($_POST['middle_name'] ?? ''),
                    'civil_status'             => sanitizeInput($_POST['civil_status'] ?? ''),
                    'spouse_name'              => sanitizeInput($_POST['spouse_name'] ?? ''),
                    'course_year_level'        => sanitizeInput($_POST['course_year_level'] ?? ''),
                    'sex'                      => sanitizeInput($_POST['sex'] ?? ''),
                    'ethnicity'                => sanitizeInput($_POST['ethnicity'] ?? ''),
                    'ethnicity_others_specify' => sanitizeInput($_POST['ethnicity_others_specify'] ?? ''),
                    'date_of_birth'            => sanitizeInput($_POST['date_of_birth'] ?? ''),
                    'age'                      => $age,
                    'place_of_birth'           => sanitizeInput($_POST['place_of_birth'] ?? ''),
                    'religion'                 => sanitizeInput($_POST['religion'] ?? ''),
                    'address'                  => sanitizeInput($_POST['address'] ?? ''),
                    'contact_number'           => sanitizeInput($_POST['contact_number'] ?? ''),
                ),
                'family_info' => (new FamilyInfoHandler())->buildFamilyInfo($_POST),
                'education'   => array(
                    'elementary_school'              => sanitizeInput($_POST['elementary_school'] ?? ''),
                    'secondary_school'               => sanitizeInput($_POST['secondary_school'] ?? ''),
                    'school_university_last_attended'=> sanitizeInput($_POST['school_university_last_attended'] ?? ''),
                    'school_name'                    => sanitizeInput($_POST['school_name'] ?? ''),
                    'school_address'                 => sanitizeInput($_POST['school_address'] ?? ''),
                    'general_average'                => sanitizeInput($_POST['general_average'] ?? ''),
                    'course_first_choice'            => sanitizeInput($_POST['course_first_choice'] ?? ''),
                    'course_second_choice'           => sanitizeInput($_POST['course_second_choice'] ?? ''),
                    'course_third_choice'            => sanitizeInput($_POST['course_third_choice'] ?? ''),
                    'parents_choice'                 => sanitizeInput($_POST['parents_choice'] ?? ''),
                    'nature_of_schooling_continuous' => sanitizeInput($_POST['nature_of_schooling_continuous'] ?? ''),
                    'reason_if_interrupted'          => sanitizeInput($_POST['reason_if_interrupted'] ?? ''),
                ),
                'skills' => array(
                    'talents' => sanitizeInput($_POST['talents'] ?? ''),
                    'awards'  => sanitizeInput($_POST['awards'] ?? ''),
                    'hobbies' => sanitizeInput($_POST['hobbies'] ?? ''),
                ),
                'health_record' => array(
                    'disability_specify'     => sanitizeInput($_POST['disability_specify'] ?? ''),
                    'confined_rehabilitated' => sanitizeInput($_POST['confined_rehabilitated'] ?? ''),
                    'confined_when'          => sanitizeInput($_POST['confined_when'] ?? ''),
                    'treated_for_illness'    => sanitizeInput($_POST['treated_for_illness'] ?? ''),
                    'treated_when'           => sanitizeInput($_POST['treated_when'] ?? ''),
                ),
                'declaration' => array(
                    'signature_over_printed_name' => sanitizeInput($_POST['signature_over_printed_name'] ?? ''),
                    'date_accomplished'           => sanitizeInput($_POST['date_accomplished'] ?? date('Y-m-d')),
                ),
            );

            $siblingManager = new SiblingManager($this->f2Form);
            $siblingsResult = $siblingManager->saveSiblingsFromPost($user['id'], $_POST['siblings'] ?? array());

            $result = $this->f2Form->saveF2FormData($user['id'], $formData);

            if ($result['success'] && $siblingsResult['success']) {
                showAlert('Personal Data form saved successfully!', 'success');
                redirect('/student/f2_personal_data_form.php');
            } else {
                showAlert($result['message'], 'error');
            }
        }

        $autoFill = new AutoFillEngine();
        $formData = $autoFill->buildInitialFormData(
            $existingData ?: array(),
            $admissionFormData ?: array(),
            $user,
            $studentFullName,
            $studentGWA,
            $f2GWA
        );

        $this->render('student/f2_personal_data_form/index', array(
            'user'             => $user,
            'alert'            => $alert,
            'formData'         => $formData,
            'existingData'     => $existingData,
            'isCompleted'      => $isCompleted,
            'studentFullName'  => $studentFullName,
            'studentGWA'       => $studentGWA,
            'f2GWA'            => $f2GWA,
            'f2Form'           => $this->f2Form,
        ));
    }
}
