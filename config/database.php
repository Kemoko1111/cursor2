<?php
class Database {
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Use constants from app.php
            $host = defined('DB_HOST') ? DB_HOST : 'sql103.infinityfree.com';
            $dbName = defined('DB_NAME') ? DB_NAME : 'if0_39537447_menteego_db';
            $user = defined('DB_USER') ? DB_USER : 'if0_39537447';
            $pass = defined('DB_PASS') ? DB_PASS : 'AeFe44u4EAs';
            $port = 3306;

            $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8";
            $this->conn = new PDO(
                $dsn,
                $user,
                $pass,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed: " . $exception->getMessage());
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>
