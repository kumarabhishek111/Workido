<?php
/**
 * Workido - Database Configuration & Connection Handler
 * Uses PHP PDO for secure, prepared statement operations.
 */

// Define Database Constants
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'workido_db');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection instance.
 * Automatically attempts to connect to MySQL.
 *
 * @return PDO|null
 */
function getDBConnection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If database does not exist, let's see if we can connect without dbname to check or auto-install
        if ($e->getCode() == 1049) { // Unknown database error
            // Could redirect to installer or return null
            error_log("Workido DB Warning: Database " . DB_NAME . " does not exist yet.");
            return null;
        }
        
        // Log error securely
        error_log("Workido DB Connection Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Helper to check if MySQL server and workido_db are accessible.
 */
function isDBConnected(): bool {
    return getDBConnection() !== null;
}
