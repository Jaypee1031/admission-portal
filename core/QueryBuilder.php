<?php

class QueryBuilder
{
    /** @var string */
    protected $table;

    /** @var array */
    protected $columns = array('*');

    /** @var array */
    protected $wheres = array();

    /** @var array */
    protected $joins = array();

    /** @var array */
    protected $orderBys = array();

    /** @var int|null */
    protected $limit;

    /** @var int|null */
    protected $offset;

    /** @var array */
    protected $bindings = array();

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public static function table($table)
    {
        $qb = new self();
        $qb->table = $table;
        return $qb;
    }

    /**
     * @param array|string $columns
     * @return $this
     */
    public function select($columns)
    {
        if (is_string($columns)) {
            $columns = array($columns);
        }
        $this->columns = $columns;
        return $this;
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator, $value, $boolean = 'AND')
    {
        $paramName = $this->makeParameterName($column);
        $this->wheres[] = array(
            'boolean'    => strtoupper($boolean),
            'expression' => $column . ' ' . $operator . ' :' . $paramName,
        );
        $this->bindings[$paramName] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @return $this
     */
    public function orWhere($column, $operator, $value)
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * WHERE column IS NULL
     *
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNull($column, $boolean = 'AND')
    {
        $this->wheres[] = array(
            'boolean'    => strtoupper($boolean),
            'expression' => $column . ' IS NULL',
        );
        return $this;
    }

    /**
     * WHERE column IS NOT NULL
     *
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'AND')
    {
        $this->wheres[] = array(
            'boolean'    => strtoupper($boolean),
            'expression' => $column . ' IS NOT NULL',
        );
        return $this;
    }

    /**
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join($table, $first, $operator, $second, $type = 'INNER')
    {
        $this->joins[] = sprintf('%s JOIN %s ON %s %s %s', strtoupper($type), $table, $first, $operator, $second);
        return $this;
    }

    /**
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function leftJoin($table, $first, $operator, $second)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBys[] = $column . ' ' . strtoupper($direction);
        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * Build the final SQL string.
     *
     * @return string
     */
    public function toSql()
    {
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table;

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $parts = array();
            foreach ($this->wheres as $index => $where) {
                $prefix = $index === 0 ? 'WHERE' : $where['boolean'];
                $parts[] = $prefix . ' ' . $where['expression'];
            }
            $sql .= ' ' . implode(' ', $parts);
        }

        if (!empty($this->orderBys)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . (int) $this->offset;
        }

        return $sql;
    }

    /**
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Generate a unique parameter name for a column.
     *
     * @param string $column
     * @return string
     */
    protected function makeParameterName($column)
    {
        $base = preg_replace('/[^a-z0-9_]/i', '_', $column);
        $name = $base;
        $i = 0;
        while (array_key_exists($name, $this->bindings)) {
            $name = $base . '_' . (++$i);
        }
        return $name;
    }
}
