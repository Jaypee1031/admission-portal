<?php

require_once __DIR__ . '/../core/Repository.php';

class StudentRepository extends Repository
{
    protected $table = 'students';
    protected $primaryKey = 'id';
    // Enable when you add deleted_at column:
    // protected $softDelete = true;

    public function findByEmail(string $email): ?array
    {
        $qb = QueryBuilder::table($this->table)
            ->select('*')
            ->where('email', '=', $email)
            ->limit(1);

        $sql      = $qb->toSql();
        $bindings = $qb->getBindings();

        $stmt = $this->prepareAndExecute($sql, $bindings);
        $row  = $stmt ? $stmt->fetch() : false;

        return $row ?: null;
    }

    public function getRecentApplicants(int $page = 1, int $perPage = 10): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $select = "students.*, CONCAT(students.first_name, " .
                  "CASE WHEN students.middle_name IS NOT NULL AND students.middle_name != '' " .
                  "THEN CONCAT(' ', students.middle_name, ' ') ELSE ' ' END, " .
                  "students.last_name) AS name";

        $qb = QueryBuilder::table('students')
            ->select($select)
            ->orderBy('students.created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset);

        $sql      = $qb->toSql();
        $bindings = $qb->getBindings();

        $stmt = $this->prepareAndExecute($sql, $bindings);
        $rows = $stmt ? $stmt->fetchAll() : array();

        $countStmt = $this->prepareAndExecute('SELECT COUNT(*) AS cnt FROM students');
        $totalRow  = $countStmt ? $countStmt->fetch() : array('cnt' => 0);
        $total     = (int) ($totalRow['cnt'] ?? 0);

        return array(
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        );
    }

    public function getStatusCounts(): array
    {
        $sql  = 'SELECT status, COUNT(*) AS cnt FROM ' . $this->table . ' GROUP BY status';
        $stmt = $this->prepareAndExecute($sql);
        $rows = $stmt ? $stmt->fetchAll() : array();

        $result = array();
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function getTotalCount(): int
    {
        $stmt = $this->prepareAndExecute('SELECT COUNT(*) AS cnt FROM ' . $this->table);
        $row  = $stmt ? $stmt->fetch() : array('cnt' => 0);
        return (int) ($row['cnt'] ?? 0);
    }

    public function getTypeCounts(): array
    {
        $sql  = 'SELECT type, COUNT(*) AS cnt FROM ' . $this->table . ' GROUP BY type';
        $stmt = $this->prepareAndExecute($sql);
        if (!$stmt) {
            return array();
        }

        // FETCH_KEY_PAIR: [type => count]
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Search applicants with filters and pagination (mirrors admin/applicants.php logic).
     *
     * @param string|null $status
     * @param string|null $type
     * @param string|null $search
     * @param int         $page
     * @param int         $perPage
     * @return array [data, total, per_page, current_page]
     */
    public function searchApplicants(?string $status, ?string $type, ?string $search, int $page = 1, int $perPage = 10): array
    {
        $whereConditions = array();
        $params = array();

        if ($status !== null && $status !== '') {
            $whereConditions[] = 's.status = ?';
            $params[] = $status;
        }

        if ($type !== null && $type !== '') {
            $whereConditions[] = 's.type = ?';
            $params[] = $type;
        }

        if ($search !== null && $search !== '') {
            $whereConditions[] = "(CONCAT(s.first_name, 
                                  CASE WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
                                       THEN CONCAT(' ', s.middle_name, ' ') 
                                       ELSE ' ' END, 
                                  s.last_name) LIKE ? OR s.email LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $offset  = ($page - 1) * $perPage;

        // Total count for current filters
        $countSql = "
            SELECT COUNT(*)
            FROM students s
            {$whereClause}
        ";

        $countStmt = $this->prepareAndExecute($countSql, $params);
        $total     = 0;
        if ($countStmt) {
            $total = (int) $countStmt->fetchColumn();
        }

        // Paged data query
        $sql = "
            SELECT s.*, 
                   CONCAT(s.first_name, 
                          CASE WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
                               THEN CONCAT(' ', s.middle_name, ' ') 
                               ELSE ' ' END, 
                          s.last_name) as name
            FROM students s
            {$whereClause}
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $dataParams   = $params;
        $dataParams[] = $perPage;
        $dataParams[] = $offset;

        $stmt = $this->prepareAndExecute($sql, $dataParams);
        $rows = $stmt ? $stmt->fetchAll() : array();

        return array(
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
        );
    }

    public function updateStatus(int $studentId, string $status): bool
    {
        $ok = $this->update($studentId, array('status' => $status));
        if ($ok) {
            $this->recordAudit('update_status', $studentId, array('status' => $status));
        }
        return $ok;
    }
}
