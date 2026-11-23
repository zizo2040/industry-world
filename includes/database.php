<?php
/**
 * Database Connection Manager
 * PDO-based secure database layer with prepared statements
 */

class Database {
    private static ?PDO $instance = null;
    private static array $queryLog = [];
    
    /**
     * Get singleton database instance
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATE
                ];
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please try again later.");
            }
        }
        return self::$instance;
    }
    
    /**
     * Execute prepared statement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $start = microtime(true);
        try {
            $stmt = self::getInstance()->prepare($sql);
            
            foreach ($params as $key => $value) {
                $paramKey = is_int($key) ? $key + 1 : ':' . ltrim($key, ':');
                
                if (is_int($value)) {
                    $type = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                    $type = PDO::PARAM_BOOL;
                } elseif ($value === null) {
                    $type = PDO::PARAM_NULL;
                } else {
                    $type = PDO::PARAM_STR;
                }
                
                $stmt->bindValue($paramKey, $value, $type);
            }
            
            $stmt->execute();
            
            self::$queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'time' => round((microtime(true) - $start) * 1000, 2)
            ];
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database query failed.");
        }
    }
    
    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert and return ID
     */
    public static function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, $data);
        
        return (int) self::getInstance()->lastInsertId();
    }
    
    /**
     * Update records
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        $params = [];
        foreach ($data as $key => $value) {
            $paramKey = "set_{$key}";
            $setParts[] = "{$key} = :{$paramKey}";
            $params[$paramKey] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE " . $where;
        $allParams = array_merge($params, $whereParams);
        $stmt = self::query($sql, $allParams);
        
        return $stmt->rowCount();
    }
    
    /**
     * Soft delete
     */
    public static function softDelete(string $table, string $where, array $params = []): int {
        $sql = "UPDATE {$table} SET deleted_at = NOW() WHERE " . $where;
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction(): void {
        self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit(): void {
        self::getInstance()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback(): void {
        self::getInstance()->rollBack();
    }
    
    /**
     * Get query log
     */
    public static function getQueryLog(): array {
        return self::$queryLog;
    }
    
    /**
     * Close connection
     */
    public static function close(): void {
        self::$instance = null;
    }
}
