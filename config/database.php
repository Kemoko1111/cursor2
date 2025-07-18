<?php
class Database {
    private $conn;

    public function getConnection() {
        $this->conn = null;

        // Fetch DATABASE_URL from the environment
        $databaseUrl = getenv("DATABASE_URL");

        if (!$databaseUrl) {
            throw new Exception("DATABASE_URL not set in environment.");
        }

        // Parse the database URL
        $dbParts = parse_url($databaseUrl);

        $host = $dbParts['host'];
        $dbName = ltrim($dbParts['path'], '/');
        $user = $dbParts['user'];
        $pass = $dbParts['pass'];
        $port = $dbParts['port'] ?? 3306;

        try {
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
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>
