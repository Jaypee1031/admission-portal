<?php

class ApplicantListService
{
    private StudentRepository $students;
    private RequirementsRepository $requirements;
    private AdmissionFormRepository $admissionForms;
    private TestResultRepository $testResults;
    private PDO $db;

    public function __construct(
        StudentRepository $students,
        RequirementsRepository $requirements,
        AdmissionFormRepository $admissionForms,
        TestResultRepository $testResults,
        PDO $db
    ) {
        $this->students       = $students;
        $this->requirements   = $requirements;
        $this->admissionForms = $admissionForms;
        $this->testResults    = $testResults;
        $this->db             = $db;
    }

    public function getApplicants(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $offset  = ($page - 1) * $perPage;

        $status = $filters['status'] ?? '';
        $type   = $filters['type'] ?? '';
        $search = $filters['search'] ?? '';

        $result      = $this->students->searchApplicants($status, $type, $search, $page, $perPage);
        $applicants  = $result['data'];
        $total       = $result['total'];

        foreach ($applicants as &$applicant) {
            $studentId = (int) ($applicant['id'] ?? 0);

            $reqData = $this->requirements->countForStudent($studentId);
            $applicant['requirements_count']    = $reqData['total'] ?? 0;
            $applicant['approved_requirements'] = $reqData['approved'] ?? 0;

            $admissionForm = $this->admissionForms->findByStudentId($studentId);
            $applicant['has_admission_form'] = $admissionForm ? 1 : 0;

            $tpStmt = $this->db->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved FROM test_permits WHERE student_id = ?");
            $tpStmt->execute([$studentId]);
            $tpData = $tpStmt->fetch();
            $applicant['has_test_permit']          = $tpData['total'] ?? 0;
            $applicant['has_approved_test_permit'] = $tpData['approved'] ?? 0;

            try {
                $f2Stmt = $this->db->prepare("SELECT COUNT(*) FROM f2_personal_data_forms WHERE student_id = ? AND first_name IS NOT NULL AND last_name IS NOT NULL");
                $f2Stmt->execute([$studentId]);
                $applicant['has_f2_form'] = $f2Stmt->fetchColumn() > 0 ? 1 : 0;
            } catch (PDOException $e) {
                $applicant['has_f2_form'] = 0;
            }

            $testResultData = $this->testResults->findLatestByStudent($studentId);
            $applicant['has_test_results'] = $testResultData ? 1 : 0;
            $applicant['test_result_id']   = $testResultData ? ($testResultData['id'] ?? 0) : 0;
        }

        $start   = $total > 0 ? $offset + 1 : 0;
        $end     = $total > 0 ? min($offset + count($applicants), $total) : 0;
        $hasPrev = $page > 1;
        $hasNext = $offset + $perPage < $total;

        return [
            'data'       => $applicants,
            'total'      => $total,
            'per_page'   => $perPage,
            'page'       => $page,
            'start'      => $start,
            'end'        => $end,
            'has_prev'   => $hasPrev,
            'has_next'   => $hasNext,
        ];
    }
}
