<?php

class AdmissionFormController extends Controller
{
    private Auth $auth;
    private AdmissionForm $admissionForm;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->admissionForm = new AdmissionForm();
    }

    public function index(): void
    {
        $registrationOpen = $this->isRegistrationOpen();

        // Custom student access check to preserve iframe behaviour
        if (!isStudent()) {
            if (!$registrationOpen) {
                // If admission is closed, show a friendly message instead of login form
                die('<div class="alert alert-info">QSU admission is currently closed. Online registration is not available at this time. Please contact the admission office for more information.</div>');
            }

            if (isset($_GET['iframe']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'dashboard.php') !== false)) {
                die('<div class="alert alert-danger">Please log in to access this form.</div>');
            }
            redirect('../index.php');
        }

        $user = $this->auth->getCurrentUser();
        $studentId = $user['id'];

        $progress = $this->auth->getStudentProgress($studentId);
        if (!$progress['requirements']) {
            showAlert('Please upload all required documents first', 'error');
            redirect('requirements.php');
        }

        try {
            $formData = $this->admissionForm->getAdmissionForm($studentId);
            $courses  = $this->admissionForm->getAvailableCourses();
            $strands  = $this->admissionForm->getAvailableStrands();

            if (!$formData) {
                $formData = array();
            }
        } catch (Exception $e) {
            error_log('Admission form error: ' . $e->getMessage());
            $formData = array();
            $courses  = array();
            $strands  = array();
        }

        $alert = getAlert();

        if (isset($_GET['clear_alert'])) {
            clearAlert();
            $alert = null;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log('POST data received: ' . print_r($_POST, true));

            $validator  = new AdmissionFormValidator();
            $fileHelper = new FileUploader();
            $uploader   = new AdmissionFormFileUploader($this->admissionForm, $fileHelper);

            $formData = $validator->buildFormData($_POST, $user);

            $transfereeError = $validator->validateTransfereeSpecific($_POST, $user);
            if ($transfereeError !== null) {
                showAlert($transfereeError, 'error');
                redirect('admission_form.php');
            }

            $formData = $uploader->handleProfilePhoto($studentId, $formData, $_POST, $_FILES);

            error_log('Form submission started for student ID: ' . $studentId);
            error_log('Form data: ' . print_r($formData, true));
            error_log('Ethnic affiliation: ' . ($formData['ethnic_affiliation'] ?? 'NOT SET'));
            error_log('Ethnic others specify: ' . ($formData['ethnic_others_specify'] ?? 'NOT SET'));

            $result = $this->admissionForm->saveAdmissionForm($studentId, $formData);
            error_log('Save result: ' . print_r($result, true));

            if ($result['success']) {
                showAlert('Admission form saved successfully!', 'success');
            } else {
                showAlert('Failed to save admission form: ' . $result['message'], 'error');
            }
        }

        $this->render('student/admission_form/index', array(
            'user'     => $user,
            'alert'    => $alert,
            'formData' => $formData,
            'courses'  => $courses,
            'strands'  => $strands,
        ));
    }
    
    /**
     * Check if registration is currently open based on admin settings.
     */
    private function isRegistrationOpen(): bool
    {
        try {
            $db = getDB();
            $db->exec("CREATE TABLE IF NOT EXISTS test_permit_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(100) UNIQUE,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");

            $stmt = $db->prepare("SELECT setting_value FROM test_permit_settings WHERE setting_key = 'registration_open'");
            $stmt->execute();
            $value = $stmt->fetchColumn();
            if ($value === false) {
                return true; // default open
            }
            return (int)$value === 1;
        } catch (Exception $e) {
            return true; // fail open to avoid breaking access for existing students
        }
    }
}
