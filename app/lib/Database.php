<?php
/**
 * Database Connection and Query Manager
 * 
 * PDO-based database class with connection pooling, query caching,
 * and performance monitoring optimized for shared hosting environments.
 */

class Database
{
    private static $instance = null;
    private static $connection = null;
    private static $read_connection = null;
    private static $config = null;
    private static $query_cache = [];
    private static $query_count = 0;
    private static $slow_queries = [];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {}
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::loadConfig();
        }
        
        return self::$instance;
    }
    
    /**
     * Load database configuration
     */
    private static function loadConfig(): void
    {
        if (self::$config === null) {
            self::$config = require CONFIG_PATH . '/database.php';
        }
    }
    
    /**
     * Get write connection (master)
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::loadConfig();
            self::$connection = self::createConnection();
        }
        
        return self::$connection;
    }
    
    /**
     * Get read connection (replica or master)
     */
    public static function getReadConnection(): PDO
    {
        if (self::$read_connection === null) {
            self::loadConfig();
            
            // Use read replica if configured, otherwise use main connection
            if (isset(self::$config['connections']['mysql_read'])) {
                self::$read_connection = self::createConnection('mysql_read');
            } else {
                self::$read_connection = self::getConnection();
            }
        }
        
        return self::$read_connection;
    }
    
    /**
     * Create PDO connection
     */
    private static function createConnection(string $connection_name = 'mysql'): PDO
    {
        $config = self::$config['connections'][$connection_name];
        
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        
        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
            // Set additional options for performance
            $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
            
            return $pdo;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * Execute a SELECT query with optional caching
     */
    public static function select(string $query, array $params = [], bool $use_cache = false, int $cache_ttl = 300): array
    {
        $start_time = microtime(true);
        $cache_key = null;
        
        // Check cache if enabled
        if ($use_cache && self::$config['performance']['query_cache']) {
            $cache_key = md5($query . serialize($params));
            if (isset(self::$query_cache[$cache_key])) {
                $cached_data = self::$query_cache[$cache_key];
                if ($cached_data['expires'] > time()) {
                    return $cached_data['data'];
                }
            }
        }
        
        try {
            $connection = self::getReadConnection();
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            // Cache the result
            if ($use_cache && $cache_key) {
                self::$query_cache[$cache_key] = [
                    'data' => $result,
                    'expires' => time() + $cache_ttl
                ];
            }
            
            self::logQuery($query, $params, $start_time);
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Database SELECT error: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Execute a single row SELECT query
     */
    public static function selectOne(string $query, array $params = []): ?array
    {
        $result = self::select($query . " LIMIT 1", $params);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Execute INSERT query and return last insert ID
     */
    public static function insert(string $query, array $params = []): int
    {
        $start_time = microtime(true);
        
        try {
            $connection = self::getConnection();
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            
            $insert_id = $connection->lastInsertId();
            
            self::logQuery($query, $params, $start_time);
            
            return (int)$insert_id;
            
        } catch (PDOException $e) {
            error_log("Database INSERT error: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database insert failed");
        }
    }
    
    /**
     * Execute UPDATE query and return affected rows
     */
    public static function update(string $query, array $params = []): int
    {
        $start_time = microtime(true);
        
        try {
            $connection = self::getConnection();
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            
            $affected_rows = $stmt->rowCount();
            
            // Clear relevant cache entries
            self::clearCacheByPattern($query);
            
            self::logQuery($query, $params, $start_time);
            
            return $affected_rows;
            
        } catch (PDOException $e) {
            error_log("Database UPDATE error: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database update failed");
        }
    }
    
    /**
     * Execute DELETE query and return affected rows
     */
    public static function delete(string $query, array $params = []): int
    {
        $start_time = microtime(true);
        
        try {
            $connection = self::getConnection();
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            
            $affected_rows = $stmt->rowCount();
            
            // Clear relevant cache entries
            self::clearCacheByPattern($query);
            
            self::logQuery($query, $params, $start_time);
            
            return $affected_rows;
            
        } catch (PDOException $e) {
            error_log("Database DELETE error: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database delete failed");
        }
    }
    
    /**
     * Begin database transaction
     */
    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }
    
    /**
     * Commit database transaction
     */
    public static function commit(): void
    {
        self::getConnection()->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public static function rollback(): void
    {
        self::getConnection()->rollback();
    }
    
    /**
     * Execute raw query (for complex queries)
     */
    public static function raw(string $query, array $params = []): PDOStatement
    {
        $start_time = microtime(true);
        
        try {
            $connection = self::getConnection();
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            
            self::logQuery($query, $params, $start_time);
            
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Database RAW query error: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Get table columns for a given table
     */
    public static function getTableColumns(string $table): array
    {
        $query = "SHOW COLUMNS FROM `{$table}`";
        $result = self::select($query, [], true, 3600); // Cache for 1 hour
        
        return array_column($result, 'Field');
    }
    
    /**
     * Check if table exists
     */
    public static function tableExists(string $table): bool
    {
        $query = "SHOW TABLES LIKE ?";
        $result = self::select($query, [$table]);
        
        return !empty($result);
    }
    
    /**
     * Escape string for safe SQL usage
     */
    public static function escape(string $value): string
    {
        return self::getConnection()->quote($value);
    }
    
    /**
     * Build WHERE conditions from array
     */
    public static function buildWhereConditions(array $conditions, string $operator = 'AND'): array
    {
        $where_parts = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Handle IN clauses
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $where_parts[] = "`{$field}` IN ({$placeholders})";
                $params = array_merge($params, $value);
            } elseif (is_null($value)) {
                $where_parts[] = "`{$field}` IS NULL";
            } else {
                $where_parts[] = "`{$field}` = ?";
                $params[] = $value;
            }
        }
        
        $where_clause = empty($where_parts) ? '' : 'WHERE ' . implode(" {$operator} ", $where_parts);
        
        return [$where_clause, $params];
    }
    
    /**
     * Log query for performance monitoring
     */
    private static function logQuery(string $query, array $params, float $start_time): void
    {
        $execution_time = microtime(true) - $start_time;
        self::$query_count++;
        
        // Log slow queries
        $slow_threshold = self::$config['performance']['slow_query_threshold'] ?? 2.0;
        if ($execution_time > $slow_threshold) {
            self::$slow_queries[] = [
                'query' => $query,
                'params' => $params,
                'time' => $execution_time,
                'timestamp' => time()
            ];
            
            if (self::$config['performance']['slow_query_log'] ?? true) {
                error_log("Slow query ({$execution_time}s): " . $query);
            }
        }
        
        // Log all queries if debugging
        if (self::$config['monitoring']['log_queries'] ?? false) {
            error_log("Query ({$execution_time}s): " . $query . " | Params: " . json_encode($params));
        }
    }
    
    /**
     * Clear cache entries by pattern
     */
    private static function clearCacheByPattern(string $query): void
    {
        // Extract table names from query to clear relevant cache
        if (preg_match('/\b(UPDATE|DELETE|INSERT)\s+.*?\b(\w+)\b/i', $query, $matches)) {
            $table = $matches[2];
            
            foreach (array_keys(self::$query_cache) as $cache_key) {
                // Simple pattern matching - could be improved
                if (strpos($cache_key, $table) !== false) {
                    unset(self::$query_cache[$cache_key]);
                }
            }
        }
    }
    
    /**
     * Get performance statistics
     */
    public static function getStats(): array
    {
        return [
            'query_count' => self::$query_count,
            'slow_queries' => count(self::$slow_queries),
            'cache_entries' => count(self::$query_cache),
            'recent_slow_queries' => array_slice(self::$slow_queries, -5)
        ];
    }
    
    /**
     * Clear all cached queries
     */
    public static function clearCache(): void
    {
        self::$query_cache = [];
    }
    
    /**
     * Close database connections
     */
    public static function disconnect(): void
    {
        self::$connection = null;
        self::$read_connection = null;
    }
    
    /**
     * Test database connection
     */
    public static function testConnection(): bool
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
}
?>