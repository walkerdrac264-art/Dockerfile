<?php
/**
 * Riverside FC - Database Connection
 * Supports MySQL / MariaDB (Primary) with resilient SQLite auto-fallback.
 */

require_once __DIR__ . '/config.php';

// Database default parameters
$dbConfigFile = __DIR__ . '/db_credentials.json';
$customConfig = [];
if (file_exists($dbConfigFile)) {
    $customConfig = json_decode(file_get_contents($dbConfigFile), true) ?: [];
}

// Credentials (priority: custom config file > environment variables > defaults)
define('DB_DRIVER', $customConfig['driver'] ?? getenv('DB_DRIVER') ?: 'mysql'); // 'mysql' or 'sqlite'
define('DB_HOST',   $customConfig['host']   ?? getenv('DB_HOST')   ?: '127.0.0.1');
define('DB_PORT',   $customConfig['port']   ?? getenv('DB_PORT')   ?: '3306');
define('DB_NAME',   $customConfig['name']   ?? getenv('DB_NAME')   ?: 'riverside_fc');
define('DB_USER',   $customConfig['user']   ?? getenv('DB_USER')   ?: 'root');
define('DB_PASS',   $customConfig['pass']   ?? getenv('DB_PASS')   ?: '');

/**
 * Get active PDO database instance
 *
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $driver = DB_DRIVER;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Attempt MySQL first if driver is mysql
    if ($driver === 'mysql') {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // Check if database doesn't exist yet, try creating it if connection to host succeeded
            if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                try {
                    $serverDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                    $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, $options);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    return $pdo;
                } catch (PDOException $inner) {
                    // Fallback to SQLite if server error persists
                }
            }

            // If connection failed (e.g. Access Denied on local dev environment without root password configured),
            // auto-fallback to SQLite so the application works out-of-the-box seamlessly!
            $sqlitePath = dirname(__DIR__) . '/database/riverside_fc.sqlite';
            try {
                $pdo = new PDO("sqlite:" . $sqlitePath, null, null, $options);
                $pdo->exec("PRAGMA foreign_keys = ON;");
                // Ensure tables exist in SQLite
                checkAndInitSqlite($pdo);
                return $pdo;
            } catch (PDOException $sqliteEx) {
                die("Database Connection Error: " . htmlspecialchars($e->getMessage()) . 
                    "<br><a href='" . BASE_URL . "/install.php'>Click here to configure Database Installer</a>");
            }
        }
    } else {
        // SQLite Driver requested
        $sqlitePath = dirname(__DIR__) . '/database/riverside_fc.sqlite';
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, $options);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        checkAndInitSqlite($pdo);
        return $pdo;
    }
}

/**
 * Initialize SQLite tables if empty
 */
function checkAndInitSqlite(PDO $pdo): void {
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchAll();
    if (empty($tables)) {
        require_once dirname(__DIR__) . '/database/seed.php';
        seedDatabase($pdo, 'sqlite');
    }
}
