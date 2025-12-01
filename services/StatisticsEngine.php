<?php

class StatisticsEngine
{
    private StudentRepository $students;
    private F2PersonalDataForm $f2FormService;
    private TestResults $testResultsService;
    private PDO $db;

    public function __construct(
        StudentRepository $students,
        F2PersonalDataForm $f2FormService,
        TestResults $testResultsService,
        PDO $db
    ) {
        $this->students          = $students;
        $this->f2FormService     = $f2FormService;
        $this->testResultsService = $testResultsService;
        $this->db                = $db;
    }

    public function getDashboardStats(): array
    {
        $cacheEnabled = defined('ENABLE_CACHE') && ENABLE_CACHE;
        $cacheKey     = 'dashboard_stats';

        if ($cacheEnabled) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $totalApplicants = $this->students->getTotalCount();
        $statusStats     = $this->students->getStatusCounts();
        $typeStats       = $this->students->getTypeCounts();

        $f2Stats         = $this->f2FormService->getF2FormStats();
        $testResultStats = $this->testResultsService->getTestResultStats();

        $testPermits     = $this->getTestPermitStats();
        $upcomingExams   = $this->getUpcomingExams();

        $result = [
            'totalApplicants'     => $totalApplicants,
            'status'              => $statusStats,
            'type'                => $typeStats,
            'f2Stats'             => $f2Stats,
            'testResultStats'     => $testResultStats,
            'pendingTestPermits'  => $testPermits['pending'],
            'approvedTestPermits' => $testPermits['approved'],
            'upcomingExams'       => $upcomingExams,
        ];

        if ($cacheEnabled) {
            $ttl = defined('CACHE_TTL_DASHBOARD') ? CACHE_TTL_DASHBOARD : 60;
            Cache::set($cacheKey, $result, $ttl);
        }

        return $result;
    }

    private function getTestPermitStats(): array
    {
        $pending  = 0;
        $approved = 0;

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM test_permits WHERE status = 'Pending'");
            $stmt->execute();
            $pending = (int) $stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM test_permits WHERE status = 'Approved'");
            $stmt->execute();
            $approved = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'status') === false) {
                throw $e;
            }
        }

        return [
            'pending'  => $pending,
            'approved' => $approved,
        ];
    }

    private function getUpcomingExams(): array
    {
        try {
            $stmt = $this->db->prepare("\n                SELECT tp.*, s.email, af.first_name, af.last_name\n                FROM test_permits tp\n                JOIN students s ON tp.student_id = s.id\n                LEFT JOIN admission_forms af ON s.id = af.student_id\n                WHERE tp.exam_date >= CURDATE()\n                ORDER BY tp.exam_date, tp.exam_time\n                LIMIT 5\n            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
