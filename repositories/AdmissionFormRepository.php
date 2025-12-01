<?php

require_once __DIR__ . '/../core/Repository.php';

class AdmissionFormRepository extends Repository
{
    protected $table = 'admission_forms';
    protected $primaryKey = 'id';

    public function findByStudentId(int $studentId): ?array
    {
        $qb = QueryBuilder::table($this->table)
            ->select('*')
            ->where('student_id', '=', $studentId)
            ->limit(1);

        $sql      = $qb->toSql();
        $bindings = $qb->getBindings();

        $stmt = $this->prepareAndExecute($sql, $bindings);
        $row  = $stmt ? $stmt->fetch() : false;

        return $row ?: null;
    }
}
