<?php

require_once __DIR__ . '/../core/Repository.php';

class TestResultRepository extends Repository
{
    protected $table = 'test_results';
    protected $primaryKey = 'id';

    public function findLatestByStudent(int $studentId): ?array
    {
        $qb = QueryBuilder::table($this->table)
            ->select('*')
            ->where('student_id', '=', $studentId)
            ->orderBy('created_at', 'DESC')
            ->limit(1);

        $sql      = $qb->toSql();
        $bindings = $qb->getBindings();

        $stmt = $this->prepareAndExecute($sql, $bindings);
        $row  = $stmt ? $stmt->fetch() : false;

        return $row ?: null;
    }
}
