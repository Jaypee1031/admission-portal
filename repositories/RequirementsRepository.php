<?php

require_once __DIR__ . '/../core/Repository.php';

class RequirementsRepository extends Repository
{
    protected $table = 'requirements';
    protected $primaryKey = 'id';

    public function countForStudent(int $studentId): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved
                FROM requirements
                WHERE student_id = :sid";

        $stmt = $this->prepareAndExecute($sql, array('sid' => $studentId));
        $row  = $stmt ? $stmt->fetch() : false;

        if (!$row) {
            return array('total' => 0, 'approved' => 0);
        }

        return array(
            'total'    => (int) ($row['total'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
        );
    }
}
