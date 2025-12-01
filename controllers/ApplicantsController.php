<?php

class ApplicantsController extends Controller
{
    private Auth $auth;
    private PDO $db;
    private StudentRepository $studentRepository;
    private AdmissionFormRepository $admissionFormRepository;
    private RequirementsRepository $requirementsRepository;
    private TestResultRepository $testResultRepository;
    private ApplicantFilterEngine $filterEngine;
    private ApplicantListService $applicantListService;
    private BulkActionsService $bulkActionsService;

    public function __construct()
    {
        $this->db   = getDB();
        $this->auth = new Auth();

        $this->studentRepository       = new StudentRepository();
        $this->admissionFormRepository = new AdmissionFormRepository();
        $this->requirementsRepository  = new RequirementsRepository();
        $this->testResultRepository    = new TestResultRepository();

        $this->filterEngine = new ApplicantFilterEngine();

        $this->applicantListService = new ApplicantListService(
            $this->studentRepository,
            $this->requirementsRepository,
            $this->admissionFormRepository,
            $this->testResultRepository,
            $this->db
        );

        $this->bulkActionsService = new BulkActionsService($this->db);
    }

    public function index(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!verifyCSRFToken($csrfToken)) {
                showAlert('Security check failed. Please try again.', 'error');
                redirect('applicants.php');
            }

            if (isset($_POST['update_status'])) {
                $this->handleStatusUpdate();
                return;
            }

            if (isset($_POST['update_requirement'])) {
                $this->handleRequirementUpdate();
                return;
            }

            if (isset($_POST['delete_student'])) {
                $this->handleDeleteStudent();
                return;
            }
        }

        $alert = getAlert();

        $filters      = $this->filterEngine->getFiltersFromArray($_GET);
        $statusFilter = $filters['status'] ?? '';
        $typeFilter   = $filters['type'] ?? '';
        $searchQuery  = $filters['search'] ?? '';

        try {
            $applicantsPerPage = 10;
            $applicantsPage    = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

            $listResult      = $this->applicantListService->getApplicants($filters, $applicantsPage, $applicantsPerPage);
            $applicants      = $listResult['data'];
            $applicantsTotal = $listResult['total'];

            $applicantsStart   = $listResult['start'];
            $applicantsEnd     = $listResult['end'];
            $hasPrevApplicants = $listResult['has_prev'];
            $hasNextApplicants = $listResult['has_next'];
        } catch (PDOException $e) {
            $applicants        = array();
            $applicantsTotal   = 0;
            $applicantsStart   = 0;
            $applicantsEnd     = 0;
            $hasPrevApplicants = false;
            $hasNextApplicants = false;
            $applicantsPerPage = 10;
            $applicantsPage    = 1;
        }

        try {
            $statusStats = $this->studentRepository->getStatusCounts();
            $typeStats   = $this->studentRepository->getTypeCounts();
        } catch (PDOException $e) {
            $statusStats = array();
            $typeStats   = array();
        }

        $this->render('admin/applicants/index', array(
            'alert'               => $alert,
            'filters'             => $filters,
            'statusFilter'        => $statusFilter,
            'typeFilter'          => $typeFilter,
            'searchQuery'         => $searchQuery,
            'applicants'          => $applicants,
            'applicantsTotal'     => $applicantsTotal,
            'applicantsStart'     => $applicantsStart,
            'applicantsEnd'       => $applicantsEnd,
            'hasPrevApplicants'   => $hasPrevApplicants,
            'hasNextApplicants'   => $hasNextApplicants,
            'applicantsPerPage'   => $applicantsPerPage,
            'applicantsPage'      => $applicantsPage,
            'statusStats'         => $statusStats,
            'typeStats'           => $typeStats,
        ));
    }

    private function handleStatusUpdate(): void
    {
        $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $newStatus = sanitizeInput($_POST['status'] ?? '');
        $remarks   = sanitizeInput($_POST['remarks'] ?? '');

        if ($studentId <= 0 || $newStatus === '') {
            showAlert('Invalid status update request.', 'error');
            redirect('applicants.php');
        }

        try {
            $this->studentRepository->updateStatus($studentId, $newStatus);

            $this->auth->refreshStudentSession($studentId);

            if (class_exists('Cache')) {
                Cache::flush();
            }

            showAlert('Student status updated successfully', 'success');
            redirect('applicants.php');
        } catch (PDOException $e) {
            showAlert('Failed to update status: ' . $e->getMessage(), 'error');
        }
    }

    private function handleRequirementUpdate(): void
    {
        $requirementId = isset($_POST['requirement_id']) ? (int)$_POST['requirement_id'] : 0;
        $newStatus     = sanitizeInput($_POST['requirement_status'] ?? '');
        $remarks       = sanitizeInput($_POST['requirement_remarks'] ?? '');

        if ($requirementId <= 0 || $newStatus === '') {
            showAlert('Invalid requirement update request.', 'error');
            redirect('applicants.php');
        }

        try {
            $this->bulkActionsService->updateRequirementStatus($requirementId, $newStatus, $remarks);

            if (class_exists('Cache')) {
                Cache::flush();
            }

            showAlert('Requirement status updated successfully', 'success');
            redirect('applicants.php');
        } catch (PDOException $e) {
            showAlert('Failed to update requirement: ' . $e->getMessage(), 'error');
        }
    }

    private function handleDeleteStudent(): void
    {
        $studentId    = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $confirmation = sanitizeInput($_POST['confirmation'] ?? '');

        if ($studentId <= 0 || $confirmation !== 'DELETE') {
            showAlert('Please type "DELETE" to confirm deletion', 'error');
            redirect('applicants.php');
        }

        try {
            $this->bulkActionsService->deleteStudentWithRelations($studentId);

            if (class_exists('Cache')) {
                Cache::flush();
            }

            showAlert('Student and all related data deleted successfully', 'success');
            redirect('applicants.php');
        } catch (PDOException $e) {
            showAlert('Failed to delete student: ' . $e->getMessage(), 'error');
        }
    }
}
