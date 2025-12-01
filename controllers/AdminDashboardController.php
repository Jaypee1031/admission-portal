<?php

class AdminDashboardController extends Controller
{
    private Auth $auth;
    private PDO $db;
    private StatisticsEngine $statisticsEngine;
    private RecentApplicantsWidget $recentApplicantsWidget;

    public function __construct()
    {
        $this->db   = getDB();
        $this->auth = new Auth();

        $studentRepository       = new StudentRepository();
        $admissionFormRepository = new AdmissionFormRepository();
        $requirementsRepository  = new RequirementsRepository();
        $testResultRepository    = new TestResultRepository();

        $this->statisticsEngine = new StatisticsEngine(
            $studentRepository,
            new F2PersonalDataForm(),
            new TestResults(),
            $this->db
        );

        $this->recentApplicantsWidget = new RecentApplicantsWidget(
            $studentRepository,
            $requirementsRepository,
            $admissionFormRepository,
            $testResultRepository,
            $this->db
        );
    }

    public function index(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_status') {
                $this->handleStatusUpdate();
                return;
            }

            if ($action === 'delete_student') {
                $this->handleDeleteStudent();
                return;
            }
        }

        $user = $this->auth->getCurrentUser();

        try {
            $stats = $this->statisticsEngine->getDashboardStats();

            $totalApplicants = $stats['totalApplicants'] ?? 0;
            $statusStats     = $stats['status'] ?? array();
            $typeStats       = $stats['type'] ?? array();

            $pendingApplicants  = $statusStats['Pending'] ?? 0;
            $approvedApplicants = $statusStats['Approved'] ?? 0;
            $rejectedApplicants = $statusStats['Rejected'] ?? 0;

            $pendingTestPermits  = $stats['pendingTestPermits'] ?? 0;
            $approvedTestPermits = $stats['approvedTestPermits'] ?? 0;

            $upcomingExams = $stats['upcomingExams'] ?? array();

            $f2Stats         = $stats['f2Stats'] ?? array('f2_enabled' => 0, 'f2_completed' => 0, 'f2_pending' => 0);
            $testResultStats = $stats['testResultStats'] ?? array('total_results' => 0);
        } catch (PDOException $e) {
            $totalApplicants = $pendingApplicants = $approvedApplicants = $rejectedApplicants = 0;
            $typeStats = array();
            $pendingTestPermits = 0;
            $approvedTestPermits = 0;
            $upcomingExams = array();
            $f2Stats = array('f2_enabled' => 0, 'f2_completed' => 0, 'f2_pending' => 0);
            $testResultStats = array('total_results' => 0);
        }

        $alert = getAlert();

        $recentLimit = 10;
        $recentPage  = isset($_GET['recent_page']) && (int)$_GET['recent_page'] > 0 ? (int)$_GET['recent_page'] : 1;

        try {
            $recentResult     = $this->recentApplicantsWidget->getRecentApplicants($recentPage, $recentLimit);
            $recentApplicants = $recentResult['data'];
            $recentTotal      = $recentResult['total'];
            $recentStart      = $recentResult['start'];
            $recentEnd        = $recentResult['end'];
            $recentHasPrev    = $recentResult['has_prev'];
            $recentHasNext    = $recentResult['has_next'];
        } catch (PDOException $e) {
            $recentApplicants = array();
            $recentTotal      = 0;
            $recentStart      = 0;
            $recentEnd        = 0;
            $recentHasPrev    = false;
            $recentHasNext    = false;
        }

        $upcomingSlots = $this->getUpcomingSlots();

        $this->render('admin/dashboard/index', array(
            'user'                 => $user,
            'alert'                => $alert,
            'totalApplicants'      => $totalApplicants,
            'pendingApplicants'    => $pendingApplicants,
            'approvedApplicants'   => $approvedApplicants,
            'rejectedApplicants'   => $rejectedApplicants,
            'pendingTestPermits'   => $pendingTestPermits,
            'approvedTestPermits'  => $approvedTestPermits,
            'typeStats'            => $typeStats,
            'upcomingExams'        => $upcomingExams,
            'f2Stats'              => $f2Stats,
            'testResultStats'      => $testResultStats,
            'recentApplicants'     => $recentApplicants,
            'recentTotal'          => $recentTotal,
            'recentStart'          => $recentStart,
            'recentEnd'            => $recentEnd,
            'recentHasPrev'        => $recentHasPrev,
            'recentHasNext'        => $recentHasNext,
            'recentPage'           => $recentPage,
            'recentLimit'          => $recentLimit,
            'upcomingSlots'        => $upcomingSlots,
        ));
    }

    private function handleStatusUpdate(): void
    {
        $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $newStatus = sanitizeInput($_POST['status'] ?? '');
        $remarks   = sanitizeInput($_POST['remarks'] ?? '');

        if ($studentId <= 0 || $newStatus === '') {
            showAlert('Invalid status update request.', 'error');
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE students SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $studentId]);

            $this->auth->refreshStudentSession($studentId);

            if (class_exists('Cache')) {
                Cache::flush();
            }

            showAlert('Student status updated successfully', 'success');
            redirect('/admin/dashboard.php');
        } catch (PDOException $e) {
            showAlert('Failed to update status: ' . $e->getMessage(), 'error');
        }
    }

    private function handleDeleteStudent(): void
    {
        $studentId     = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $confirmation  = sanitizeInput($_POST['confirmation'] ?? '');

        if ($studentId <= 0 || $confirmation !== 'DELETE') {
            showAlert('Please type "DELETE" to confirm deletion', 'error');
            redirect('/admin/dashboard.php');
        }

        try {
            $this->db->beginTransaction();

            $this->db->prepare("DELETE FROM requirements WHERE student_id = ?")->execute([$studentId]);
            $this->db->prepare("DELETE FROM admission_forms WHERE student_id = ?")->execute([$studentId]);
            $this->db->prepare("DELETE FROM test_permits WHERE student_id = ?")->execute([$studentId]);

            try {
                $this->db->prepare("DELETE FROM f2_personal_data_forms WHERE student_id = ?")->execute([$studentId]);
            } catch (PDOException $e) {
                // Table might not exist, ignore
            }

            try {
                $this->db->prepare("DELETE FROM test_results WHERE student_id = ?")->execute([$studentId]);
            } catch (PDOException $e) {
                // Table might not exist, ignore
            }

            $stmt = $this->db->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$studentId]);

            $this->db->commit();

            if (class_exists('Cache')) {
                Cache::flush();
            }

            showAlert('Student and all related data deleted successfully', 'success');
            redirect('/admin/dashboard.php');
        } catch (PDOException $e) {
            $this->db->rollBack();
            showAlert('Failed to delete student: ' . $e->getMessage(), 'error');
        }
    }

    private function getUpcomingSlots(): array
    {
        $upcomingSlots = array();

        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM test_permit_settings WHERE setting_key = 'max_permits_per_day'");
            $stmt->execute();
            $maxCapacity = $stmt->fetchColumn() ?: 50;

            $stmt = $this->db->prepare("
                SELECT 
                    exam_date, 
                    exam_time, 
                    exam_room,
                    COUNT(*) as current_bookings,
                    ? as max_capacity,
                    (? - COUNT(*)) as remaining_slots
                FROM test_permits 
                WHERE exam_date >= CURDATE() 
                AND status IN ('Pending', 'Approved')
                GROUP BY exam_date, exam_time, exam_room
                ORDER BY exam_date, exam_time
                LIMIT 6
            ");
            $stmt->execute([$maxCapacity, $maxCapacity]);
            $upcomingSlots = $stmt->fetchAll();
        } catch (PDOException $e) {
            $upcomingSlots = array();
        }

        return $upcomingSlots;
    }
}
