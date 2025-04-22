<?php

/**
 * Class Database
 *
 * Implements a singleton pattern for database connection management in the ecoBuddy system.
 * This class ensures a single PDO connection instance is maintained throughout the application,
 * preventing multiple database connections and improving resource utilization.
 *
 * Features:
 * - Singleton pattern implementation
 * - SQLite database connection
 * - PDO exception handling
 * - Prevention of cloning and serialization
 */
class Database {
    /** @var Database|null Holds the single instance of the Database class */
    private static $instance = null;

    /** @var PDO The PDO database connection object */
    private $dbConnection;

    /**
     * Private constructor to prevent direct instantiation
     * Establishes the database connection using PDO
     *
     * Uses SQLite database located in the db directory
     * Sets PDO to throw exceptions on errors for better error handling
     *
     * @throws PDOException If database connection fails
     */
    private function __construct() {
        try {
            // Create new PDO connection to SQLite database
            // __DIR__ ensures correct path resolution regardless of calling script location
            $this->dbConnection = new PDO('sqlite:' . __DIR__ . '/../db/ecobuddy.sqlite');

            // Configure PDO to throw exceptions on errors
            $this->dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            // Log error and terminate script on connection failure
            // In production, should use proper error logging instead of echo
            echo "Database Connection Error: " . $e->getMessage();
            exit();
        }
    }

    /**
     * Gets the single instance of the Database class
     * Creates the instance if it doesn't exist
     *
     * @return Database The single Database instance
     */
    public static function getInstance() {
        // Create new instance if none exists
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gets the PDO database connection object
     *
     * @return PDO The active database connection
     */
    public function getdbConnection() {
        return $this->dbConnection;
    }

    /**
     * Prevents unserialize of instance
     *
     * @return void
     */
    public function __wakeup() {}

    /**
     * Prevents cloning of instance
     *
     * @return void
     */
    private function __clone() {}
}