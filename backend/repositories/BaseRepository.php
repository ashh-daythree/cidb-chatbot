<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

use Cidb\Backend\Config\DatabaseConnection;
use PDO;

abstract class BaseRepository
{
    public function __construct(protected readonly DatabaseConnection $connection)
    {
    }

    abstract protected function tableName(): string;

    protected function primaryKey(): string
    {
        return 'id';
    }

    public function findById(string $id): ?array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :id LIMIT 1',
            $this->tableName(),
            $this->primaryKey()
        );

        $statement = $this->connection->pdo()->prepare($sql);
        $this->bindValues($statement, ['id' => $id]);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    public function findByUUID(string $uuid): ?array
    {
        return $this->findById($uuid);
    }

    public function findAll(array $criteria = [], int $limit = 100, int $offset = 0, ?string $orderBy = null, string $direction = 'ASC'): array
    {
        $params = [];
        $whereClause = $this->buildWhereClause($criteria, $params);
        $this->assertIdentifier($this->tableName());

        $sql = sprintf('SELECT * FROM %s', $this->tableName());

        if ($whereClause !== '') {
            $sql .= ' WHERE ' . $whereClause;
        }

        if ($orderBy !== null && $orderBy !== '') {
            $this->assertIdentifier($orderBy);
            $sql .= sprintf(' ORDER BY %s %s', $orderBy, strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
        }

        $sql .= ' LIMIT :limit OFFSET :offset';

        $statement = $this->connection->pdo()->prepare($sql);

        $this->bindValues($statement, $params);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $criteria = [], ?string $orderBy = null, string $direction = 'ASC'): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $items = $this->findAll($criteria, $perPage, $offset, $orderBy, $direction);
        $total = $this->count($criteria);

        return [
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function filter(array $criteria = [], ?string $orderBy = null, string $direction = 'ASC', int $limit = 100, int $offset = 0): array
    {
        return $this->findAll($criteria, $limit, $offset, $orderBy, $direction);
    }

    public function search(array $columns, string $term, array $criteria = [], int $limit = 100, int $offset = 0, ?string $orderBy = null, string $direction = 'ASC'): array
    {
        if ($term === '') {
            return $this->findAll($criteria, $limit, $offset, $orderBy, $direction);
        }

        $this->assertIdentifier($this->tableName());
        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $params = [];
        $whereParts = [];

        foreach ($criteria as $column => $value) {
            $this->assertIdentifier($column);
            $placeholder = 'filter_' . $column;
            $whereParts[] = sprintf('%s = :%s', $column, $placeholder);
            $params[$placeholder] = $value;
        }

        $searchParts = [];
        foreach ($columns as $index => $column) {
            $placeholder = 'search_' . $index;
            $searchParts[] = sprintf('%s ILIKE :%s', $column, $placeholder);
            $params[$placeholder] = '%' . $term . '%';
        }

        $sql = sprintf('SELECT * FROM %s', $this->tableName());
        $clauses = [];

        if ($whereParts !== []) {
            $clauses[] = implode(' AND ', $whereParts);
        }

        if ($searchParts !== []) {
            $clauses[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        if ($clauses !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if ($orderBy !== null && $orderBy !== '') {
            $this->assertIdentifier($orderBy);
            $sql .= sprintf(' ORDER BY %s %s', $orderBy, strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
        }

        $sql .= ' LIMIT :limit OFFSET :offset';

        $statement = $this->connection->pdo()->prepare($sql);

        $this->bindValues($statement, $params);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(array $criteria = []): int
    {
        $params = [];
        $whereClause = $this->buildWhereClause($criteria, $params);
        $this->assertIdentifier($this->tableName());

        $sql = sprintf('SELECT COUNT(*) AS aggregate FROM %s', $this->tableName());

        if ($whereClause !== '') {
            $sql .= ' WHERE ' . $whereClause;
        }

        $statement = $this->connection->pdo()->prepare($sql);

        $this->bindValues($statement, $params);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return isset($row['aggregate']) ? (int) $row['aggregate'] : 0;
    }

    public function findOneBy(array $criteria): ?array
    {
        $rows = $this->findAll($criteria, 1, 0);

        return $rows[0] ?? null;
    }

    public function insert(array $data): array
    {
        if ($data === []) {
            throw new \InvalidArgumentException('Insert data cannot be empty.');
        }

        $this->assertIdentifier($this->tableName());
        $columns = array_keys($data);
        array_map([$this, 'assertIdentifier'], $columns);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING *',
            $this->tableName(),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $statement = $this->connection->pdo()->prepare($sql);

        $this->bindValues($statement, $data);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function update(string $id, array $data): ?array
    {
        if ($data === []) {
            throw new \InvalidArgumentException('Update data cannot be empty.');
        }

        $this->assertIdentifier($this->tableName());
        $this->assertIdentifier($this->primaryKey());
        $assignments = [];

        foreach (array_keys($data) as $column) {
            $this->assertIdentifier($column);
            $assignments[] = sprintf('%s = :%s', $column, $column);
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :id RETURNING *',
            $this->tableName(),
            implode(', ', $assignments),
            $this->primaryKey()
        );

        $statement = $this->connection->pdo()->prepare($sql);
        $statement->bindValue(':id', $id);

        $this->bindValues($statement, $data);
        $statement->bindValue(':id', $id);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    public function delete(string $id): bool
    {
        $this->assertIdentifier($this->tableName());
        $this->assertIdentifier($this->primaryKey());
        $sql = sprintf(
            'DELETE FROM %s WHERE %s = :id',
            $this->tableName(),
            $this->primaryKey()
        );

        $statement = $this->connection->pdo()->prepare($sql);

        $this->bindValues($statement, ['id' => $id]);
        return $statement->execute();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollback();
    }

    protected function runQuery(string $sql, array $bindings = []): \PDOStatement
    {
        $statement = $this->connection->pdo()->prepare($sql);
        $statement->execute($bindings);

        return $statement;
    }

    private function buildWhereClause(array $criteria, array &$params): string
    {
        $parts = [];

        foreach ($criteria as $column => $value) {
            $this->assertIdentifier($column);
            $placeholder = 'where_' . $column;
            $parts[] = sprintf('%s = :%s', $column, $placeholder);
            $params[$placeholder] = $value;
        }

        return implode(' AND ', $parts);
    }

    protected function assertIdentifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException(sprintf('Invalid SQL identifier: %s', $identifier));
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    protected function bindValues(\PDOStatement $statement, array $values): void
    {
        foreach ($values as $name => $value) {
            $parameter = str_starts_with((string) $name, ':') ? (string) $name : ':' . $name;
            $type = $this->pdoParameterType($value);
            $statement->bindValue($parameter, $value, $type);
        }
    }

    private function pdoParameterType(mixed $value): int
    {
        return match (true) {
            $value === null => PDO::PARAM_NULL,
            is_bool($value) => PDO::PARAM_BOOL,
            is_int($value) => PDO::PARAM_INT,
            default => PDO::PARAM_STR,
        };
    }
}
