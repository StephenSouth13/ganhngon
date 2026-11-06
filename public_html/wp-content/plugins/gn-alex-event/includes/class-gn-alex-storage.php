<?php
class Gn_Alex_Storage {
    public bool $inTx = false;

    private function __construct(public PDO $pdo, public string $driver, public string $prefix) {
        if (!preg_match('/^[a-z0-9_]+$/', $prefix)) {
            throw new InvalidArgumentException('prefix');
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function sqlite(string $path, string $prefix = 'gn_alex_'): self {
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA busy_timeout=8000');
        $pdo->exec('PRAGMA foreign_keys=ON');
        return new self($pdo, 'sqlite', $prefix);
    }

    public static function mysql(string $dsn, string $user, string $pass, string $prefix = 'gn_alex_'): self {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ]);
        $pdo->exec('SET SESSION innodb_lock_wait_timeout=8');
        return new self($pdo, 'mysql', $prefix);
    }

    public function install(): void {
        foreach (Gn_Alex_Schema::statements($this->driver, $this->prefix) as $sql) {
            $this->pdo->exec($sql);
        }
    }

    public function table(string $name): string {
        if (!preg_match('/^[a-z_]+$/', $name)) {
            throw new InvalidArgumentException('table');
        }
        return $this->prefix . $name;
    }

    public function begin(): void {
        if ($this->inTx) {
            throw new RuntimeException('nested transaction');
        }
        if ($this->driver === 'sqlite') {
            $this->pdo->exec('BEGIN IMMEDIATE');
        } else {
            $this->pdo->beginTransaction();
        }
        $this->inTx = true;
    }

    public function commit(): void {
        if (!$this->inTx) {
            return;
        }
        if ($this->driver === 'sqlite') {
            $this->pdo->exec('COMMIT');
        } else {
            $this->pdo->commit();
        }
        $this->inTx = false;
    }

    public function rollback(): void {
        if (!$this->inTx) {
            return;
        }
        $this->inTx = false;
        try {
            if ($this->driver === 'sqlite') {
                $this->pdo->exec('ROLLBACK');
            } else {
                $this->pdo->rollBack();
            }
        } catch (PDOException $e) {
            // The driver may already have aborted the transaction.
        }
    }

    public function fetch(string $sql, array $params = []): ?array {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function exec(string $sql, array $params = []): int {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public function insert(string $sql, array $params = []): int {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    public function lockSql(string $table, string $where = 'id=?'): string {
        $t = $this->table($table);
        $sql = "SELECT * FROM {$t} WHERE {$where}";
        if ($this->driver === 'mysql') {
            $sql .= ' FOR UPDATE';
        }
        return $sql;
    }

    public function lockRow(string $table, int $id): ?array {
        return $this->fetch($this->lockSql($table), [$id]);
    }
}
