<?php
/**
 * Database Configuration and Connection Class
 * Uses PDO for secure database operations
 */

class Database {
    // Database credentials
    private $host = "localhost";
    private $port = "3306";
    private $db_name = "westerne_db";
    private $username = "westerne_user";
    private $password = "westerneye2026";
    private $charset = "utf8mb4";
    public $conn;
    private $stmt;
    private $error;

    /**
     * Constructor - Auto connect
     */
    public function __construct() {
        $this->getConnection();
    }

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . 
                   ";port=" . $this->port .
                   ";dbname=" . $this->db_name . 
                   ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            $this->error = $exception->getMessage();
            error_log("Database Connection Error: " . $this->error);
            throw new Exception("Database connection failed: " . $this->error);
        }

        return $this->conn;
    }

    /**
     * Prepare a query
     * @param string $sql
     * @return Database
     */
    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
        return $this;
    }

    /**
     * Bind a value to prepared statement
     * @param string $param
     * @param mixed $value
     * @param int|null $type
     * @return Database
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
     * Bind multiple values at once
     * @param array $params Associative array of param => value
     * @return Database
     */
    public function bindMultiple($params = []) {
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $this->bind($param, $value);
            }
        }
        return $this;
    }

    /**
     * Execute the prepared statement
     * @return bool
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
     * Get single record as associative array
     * @return array|false
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get result set as array of associative arrays
     * @return array
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get row count
     * @return int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    /**
     * Get last inserted ID
     * @return string
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Get error message
     * @return string
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
        $this->stmt = null;
    }

    /**
     * Destructor - close connection
     */
    public function __destruct() {
        $this->closeConnection();
    }
}
?>