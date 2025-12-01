<?php

require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/QueryBuilder.php';

abstract class Repository
{
    protected $table;
    protected $primaryKey = 'id';
    protected $softDelete = false;
    protected $softDeleteColumn = 'deleted_at';

    /** @var DatabaseConnection */
    protected $connection;

    /** @var PDO */
    protected $pdo;

    public function __construct()
    {
        $this->connection = DatabaseConnection::getInstance();
        $this->pdo        = $this->connection->getPdo();
    }

    public function find($id)
    {
        $qb = QueryBuilder::table($this->table)->select('*')->where($this->primaryKey, '=', $id);

        if ($this->softDelete) {
            $qb->whereNull($this->softDeleteColumn);
        }

        $sql      = $qb->toSql();
        $bindings = $qb->getBindings();

        $stmt = $this->prepareAndExecute($sql, $bindings);
        $row  = $stmt ? $stmt->fetch() : false;

        return $row ?: null;
    }

    public function create(array $data)
    {
        $columns      = array_keys($data);
        $placeholders = array();

        foreach ($columns as $column) {
            $placeholders[] = ':' . $column;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->prepareAndExecute($sql, $data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update($id, array $data)
    {
        $setParts = array();
        $params   = array();

        foreach ($data as $column => $value) {
            $setParts[]        = $column . ' = :' . $column;
            $params[$column]   = $value;
        }

        $params[$this->primaryKey] = $id;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :%s',
            $this->table,
            implode(', ', $setParts),
            $this->primaryKey,
            $this->primaryKey
        );

        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt !== false;
    }

    public function delete($id)
    {
        if ($this->softDelete) {
            $sql = sprintf(
                'UPDATE %s SET %s = CURRENT_TIMESTAMP WHERE %s = :id',
                $this->table,
                $this->softDeleteColumn,
                $this->primaryKey
            );
            $params = array('id' => $id);
        } else {
            $sql = sprintf(
                'DELETE FROM %s WHERE %s = :id',
                $this->table,
                $this->primaryKey
            );
            $params = array('id' => $id);
        }

        $stmt = $this->prepareAndExecute($sql, $params);

        $this->recordAudit('delete', $id, array());

        return $stmt !== false;
    }

    public function paginate($page = 1, $perPage = 20, array $filters = array(), array $orderBy = array())
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);
        $offset  = ($page - 1) * $perPage;

        $qb = QueryBuilder::table($this->table)->select('*');

        foreach ($filters as $column => $value) {
            $qb->where($column, '=', $value);
        }

        if ($this->softDelete) {
            $qb->whereNull($this->softDeleteColumn);
        }

        foreach ($orderBy as $column => $direction) {
            $qb->orderBy($column, $direction);
        }

        $qb->limit($perPage)->offset($offset);

        $sql      = $qb->toSql();
        $bindings = $qb->getBindings();

        $stmt = $this->prepareAndExecute($sql, $bindings);
        $rows = $stmt ? $stmt->fetchAll() : array();

        $countSql = sprintf('SELECT COUNT(*) AS cnt FROM (%s) AS sub', $sql);
        $countStmt = $this->prepareAndExecute($countSql, $bindings);
        $totalRow  = $countStmt ? $countStmt->fetch() : array('cnt' => 0);
        $total     = (int) $totalRow['cnt'];

        $lastPage = (int) ceil($total / $perPage);

        return array(
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
        );
    }

    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    public function commit()
    {
        $this->connection->commit();
    }

    public function rollBack()
    {
        $this->connection->rollBack();
    }

    protected function getFromCache($key)
    {
        return null;
    }

    protected function setCache($key, $value, $ttlSeconds = 60)
    {
    }

    protected function recordAudit($action, $entityId, array $data)
    {
    }

    protected function prepareAndExecute($sql, array $params = array())
    {
        try {
            $stmt = $this->pdo->prepare($sql);

            $hasNamedParameters = false;
            foreach (array_keys($params) as $key) {
                if (!is_int($key)) {
                    $hasNamedParameters = true;
                    break;
                }
            }

            if ($hasNamedParameters) {
                foreach ($params as $name => $value) {
                    $paramName = (strpos($name, ':') === 0) ? $name : ':' . $name;

                    if (is_int($value)) {
                        $type = PDO::PARAM_INT;
                    } elseif (is_bool($value)) {
                        $type = PDO::PARAM_BOOL;
                    } elseif (is_null($value)) {
                        $type = PDO::PARAM_NULL;
                    } else {
                        $type = PDO::PARAM_STR;
                    }

                    $stmt->bindValue($paramName, $value, $type);
                }
                $stmt->execute();
            } else {
                $stmt->execute($params);
            }

            return $stmt;
        } catch (PDOException $e) {
            error_log('Repository query error in ' . static::class . ': ' . $e->getMessage());
            return false;
        }
    }
}
