<?php

class DatabaseConnection
{
    /** @var DatabaseConnection|null */
    private static $instance = null;

    /** @var PDO */
    private $pdo;

    /** @var string */
    private $driver;

    /**
     * Private constructor. Use getInstance().
     *
     * @param PDO|null $pdo
     */
    private function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } else {
            // Reuse existing global singleton connection
            $this->pdo = getDB();
        }

        $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * @param PDO|null $pdo
     * @return DatabaseConnection
     */
    public static function getInstance($pdo = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }

        return self::$instance;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
