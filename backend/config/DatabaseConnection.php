<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

use PDO;
use PDOException;

final class DatabaseConnection
{
    private ?PDO $pdo = null;

    public function __construct(private readonly DatabaseConfig $config)
    {
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        if ($this->config->database() === '') {
            throw new PDOException('Database name is not configured.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;connect_timeout=%d',
            $this->config->host(),
            $this->config->port(),
            $this->config->database(),
            $this->config->timeoutSeconds()
        );

        $pdo = new PDO(
            $dsn,
            $this->config->username(),
            $this->config->password(),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => $this->config->timeoutSeconds(),
            ]
        );

        $pdo->exec("SET client_encoding TO 'UTF8'");

        $this->pdo = $pdo;

        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    public function rollback(): bool
    {
        if (!$this->pdo()->inTransaction()) {
            return false;
        }

        return $this->pdo()->rollBack();
    }
}

