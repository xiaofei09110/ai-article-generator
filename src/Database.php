<?php
/**
 * 数据库管理类 - PDO单例
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * 获取PDO连接单例
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * 建立数据库连接
     */
    private static function connect(): void {
        $config = Config::getDB();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name']
        );

        try {
            self::$instance = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                [
                    // 禁用模拟预处理语句，使用真正的预处理（防SQL注入）
                    PDO::ATTR_EMULATE_PREPARES => false,
                    // 异常模式
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // 默认返回关联数组
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 执行预处理查询，返回PDOStatement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取单条记录
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        return $stmt->fetch() ?: null;
    }

    /**
     * 获取多条记录
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * 获取单一值
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn($column);
    }

    /**
     * 插入数据，返回lastInsertId
     */
    public static function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        self::query($sql, array_values($data));
        return (int)self::getInstance()->lastInsertId();
    }

    /**
     * 更新数据，返回受影响行数
     */
    public static function update(string $table, array $data, array $where): int {
        $setClause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($k) => "$k = ?", array_keys($where)));

        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        $params = array_merge(array_values($data), array_values($where));

        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * 删除数据，返回受影响行数
     */
    public static function delete(string $table, array $where): int {
        $whereClause = implode(' AND ', array_map(fn($k) => "$k = ?", array_keys($where)));
        $sql = "DELETE FROM $table WHERE $whereClause";

        $stmt = self::query($sql, array_values($where));
        return $stmt->rowCount();
    }

    /**
     * 开始事务
     */
    public static function beginTransaction(): void {
        self::getInstance()->beginTransaction();
    }

    /**
     * 提交事务
     */
    public static function commit(): void {
        self::getInstance()->commit();
    }

    /**
     * 回滚事务
     */
    public static function rollback(): void {
        self::getInstance()->rollBack();
    }

    /**
     * 执行原生SQL（不返回结果，用于DDL）
     */
    public static function execute(string $sql): int {
        try {
            return self::getInstance()->exec($sql);
        } catch (PDOException $e) {
            throw new Exception('SQL执行失败: ' . $e->getMessage());
        }
    }
}
