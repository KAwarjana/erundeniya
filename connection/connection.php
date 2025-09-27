<?php

class Database {
    public static $connection;
    
    // Database configuration
    private static $host = "localhost";
    private static $username = "root";
    private static $password = "Kawi@#$123";
    private static $database = "erundeniya";
    private static $port = "3306";

    public static function setUpConnection() {
        try {
            if (!isset(Database::$connection)) {
                // Enable mysqli error reporting
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                
                Database::$connection = new mysqli(
                    self::$host, 
                    self::$username, 
                    self::$password, 
                    self::$database, 
                    self::$port
                );
                
                // Set charset to utf8mb4
                Database::$connection->set_charset("utf8mb4");
                
                // Log successful connection
                error_log("Database connection established successfully");
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function iud($q) {
        try {
            Database::setUpConnection();
            $result = Database::$connection->query($q);
            if (!$result) {
                throw new Exception("Query failed: " . Database::$connection->error);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Database IUD error: " . $e->getMessage());
            throw $e;
        }
    }

    public static function search($q) {
        try {
            Database::setUpConnection();
            $resultset = Database::$connection->query($q);
            if (!$resultset) {
                throw new Exception("Query failed: " . Database::$connection->error);
            }
            return $resultset;
        } catch (Exception $e) {
            error_log("Database search error: " . $e->getMessage());
            throw $e;
        }
    }

}

?>