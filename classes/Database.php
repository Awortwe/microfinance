<?php
/**
 * Database Class
 * Handles all database operations using PDO
 */

class Database {
    private $host = "localhost";
    private $db_name = "microfinance_db";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    private $conn = null;
    private $stmt;
    private $error;
    private $inTransaction = false;
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->connect();
    }

    /**
     * Get Database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . $this->host . 
                   ";dbname=" . $this->db_name . 
                   ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT         => true
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Connection Error: " . $this->error);
            throw new Exception("Database connection failed");
        }
    }

    /**
     * Prepare query
     */
    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
        return $this;
    }

    /**
     * Bind values to prepared statement
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    /**
     * Bind multiple values
     */
    public function bindMultiple($params = []) {
        foreach ($params as $param => $value) {
            $this->bind($param, $value);
        }
        return $this;
    }

    /**
     * Execute prepared statement
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Query Execution Error: " . $this->error);
            throw new Exception("Query execution failed: " . $this->error);
        }
    }

    /**
     * Get single record
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    /**
     * Get multiple records
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Get row count
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->inTransaction = true;
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->inTransaction = false;
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->conn->rollback();
        }
        return false;
    }

    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->inTransaction;
    }

    /**
     * Get connection
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Close connection
     */
    public function close() {
        $this->conn = null;
    }

    /**
     * Get error
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}
?>