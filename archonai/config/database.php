<?php
declare(strict_types=1);

/**
 * ArchonAI - Database Configuration & PDO Factory
 */

require_once __DIR__ . '/app.php';

class Database {
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Get or initialize PDO connection singleton
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::init();
        }
        return self::$instance;
    }

    /**
     * Initialize connection using PDO
     */
    private static function init(): void {
        $host = (string)env('DB_HOST', '127.0.0.1');
        $port = (int)env('DB_PORT', 3306);
        $db   = (string)env('DB_DATABASE', 'archonai');
        $user = (string)env('DB_USERNAME', 'root');
        $pass = (string)env('DB_PASSWORD', '');
        $charset = (string)env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
            self::$config = [
                'host' => $host,
                'port' => $port,
                'database' => $db,
                'charset' => $charset,
            ];
        } catch (PDOException $e) {
            // Log technical details safely without exposing passwords in logs
            $safeMsg = sprintf(
                "Database connection error: %s [Host: %s, Port: %d, DB: %s]",
                $e->getMessage(),
                $host,
                $port,
                $db
            );
            error_log($safeMsg);

            // Re-throw or display sanitized message depending on debug mode
            if (env('APP_DEBUG', false)) {
                throw new PDOException("Database connection failed for '{$db}' at {$host}:{$port}. " . $e->getMessage(), (int)$e->getCode());
            } else {
                throw new PDOException("Database connection unavailable. Please check system logs.", 500);
            }
        }
    }

    /**
     * Test connection status and return connection metadata without credentials
     */
    public static function testConnection(): array {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as active_db");
            $info = $stmt->fetch();
            
            // Query table count
            $tableStmt = $pdo->query("SHOW TABLES");
            $tables = $tableStmt->fetchAll(PDO::FETCH_COLUMN);

            return [
                'status' => 'success',
                'server_version' => $info['version'] ?? 'Unknown',
                'active_database' => $info['active_db'] ?? (string)env('DB_DATABASE', 'archonai'),
                'tables_count' => count($tables),
                'tables' => $tables,
                'charset' => (string)env('DB_CHARSET', 'utf8mb4'),
                'host' => (string)env('DB_HOST', '127.0.0.1') . ':' . (string)env('DB_PORT', 3306),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}

/**
 * Global helper to access DB connection
 */
function db(): PDO {
    return Database::getConnection();
}
