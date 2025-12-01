<?php

class BulkActionsService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function updateRequirementStatus(int $requirementId, string $status, string $remarks): void
    {
        $stmt = $this->db->prepare("UPDATE requirements SET status = ?, remarks = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $remarks, $requirementId]);
    }

    public function deleteStudentWithRelations(int $studentId): void
    {
        $this->db->beginTransaction();

        $this->db->prepare("DELETE FROM requirements WHERE student_id = ?")->execute([$studentId]);
        $this->db->prepare("DELETE FROM admission_forms WHERE student_id = ?")->execute([$studentId]);
        $this->db->prepare("DELETE FROM test_permits WHERE student_id = ?")->execute([$studentId]);

        try {
            $this->db->prepare("DELETE FROM f2_personal_data_forms WHERE student_id = ?")->execute([$studentId]);
        } catch (PDOException $e) {
        }

        try {
            $this->db->prepare("DELETE FROM test_results WHERE student_id = ?")->execute([$studentId]);
        } catch (PDOException $e) {
        }

        $stmt = $this->db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$studentId]);

        $this->db->commit();
    }
}
