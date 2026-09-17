<?php

namespace Model;

require_once __DIR__ . "/../Config/configuration.php";

use PDO;
use PDOException;

class Connection
{
    private static $stmt;

    public static function getInstance(): PDO
    {
        if (empty(self::$stmt)) {
            try {
                self::$stmt = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require", DB_USER, DB_PASSWORD, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_CASE => PDO::CASE_NATURAL
                ]);
            } catch (PDOException $error) {
                error_log($error->getMessage());
                http_response_code(500);
                die(json_encode($error->getMessage()));
            }
        }

        return self::$stmt;
    }
}