<?php

class StudentDashboardController extends Controller
{
    private Auth $auth;
    private PDO $db;
    private F2PersonalDataForm $f2Form;
    private TestResults $testResults;

    public function __construct()
    {
        $this->auth   = new Auth();
        $this->db     = getDB();
        $this->f2Form = new F2PersonalDataForm();
        $this->testResults = new TestResults();
    }

    public function index(): void
    {
        requireStudent();

        $user     = $this->auth->getCurrentUser();
        $progress = $this->auth->getStudentProgress($user['id']);

        // Refresh user data to get updated status
        $this->auth->refreshStudentSession($user['id']);
        $user = $this->auth->getCurrentUser();

        // Get Personal Data form status
        $f2FormEnabled   = $this->f2Form->isF2FormEnabled($user['id']);
        $f2FormCompleted = $this->f2Form->isF2FormCompleted($user['id']);

        // Get test result status
        $testResult         = $this->testResults->getTestResult($user['id']);
        $testResultAvailable = $user['test_result_available'] ?? false;

        // Get approval status (with error handling for missing columns)
        $approvalStatus = null;

        try {
            $stmt = $this->db->prepare('
                SELECT 
                    tp.status as permit_status,
                    tp.approved_at as permit_approved_at,
                    COUNT(r.id) as total_requirements,
                    COUNT(CASE WHEN r.status = "Approved" THEN 1 END) as approved_requirements
                FROM students s
                LEFT JOIN test_permits tp ON s.id = tp.student_id
                LEFT JOIN requirements r ON s.id = r.student_id
                WHERE s.id = ?
                GROUP BY s.id, tp.status, tp.approved_at
            ');
            $stmt->execute(array($user['id']));
            $approvalStatus = $stmt->fetch();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'status') !== false) {
                $approvalStatus = null;
            } else {
                throw $e;
            }
        }

        // Calculate progress percentage
        $totalSteps = 7; // Registration, Requirements, Admission Form, Test Permit, Exam, Personal Data, Test Results
        $completedSteps = 1; // Registration is always completed if logged in
        if ($progress['requirements']) $completedSteps++;
        if ($progress['admission_form']) $completedSteps++;
        if ($progress['test_permit_approved']) $completedSteps++;
        if ($progress['exam_completed'] || $progress['test_results_available']) $completedSteps++;
        if ($progress['f2_form_completed']) $completedSteps++;
        if ($progress['test_results_available']) $completedSteps++;
        $progressPercentage = ($completedSteps / $totalSteps) * 100;

        // Get current step
        $currentStep = 1; // Start with requirements
        if ($progress['requirements']) $currentStep = 2;
        if ($progress['admission_form']) $currentStep = 3;
        if ($progress['test_permit_approved']) $currentStep = 4;
        if ($progress['exam_completed'] || $progress['test_results_available']) $currentStep = 5;
        if ($progress['f2_form_completed']) $currentStep = 6;
        if ($progress['test_results_available']) $currentStep = 7;

        $this->render('student/dashboard/index', array(
            'user'               => $user,
            'progress'           => $progress,
            'f2FormEnabled'      => $f2FormEnabled,
            'f2FormCompleted'    => $f2FormCompleted,
            'testResult'         => $testResult,
            'testResultAvailable'=> $testResultAvailable,
            'approvalStatus'     => $approvalStatus,
            'totalSteps'         => $totalSteps,
            'completedSteps'     => $completedSteps,
            'progressPercentage' => $progressPercentage,
            'currentStep'        => $currentStep,
        ));
    }
}
