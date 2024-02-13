<?php


class DBHandler {
    private $conn;

    public function __construct($host, $username, $password, $database) {
        $this->conn = new mysqli($host, $username, $password, $database);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function insertData($table, $data) {
        // Вставка данных в БД с использованием подготовленных запросов
        $columns = implode(", ", array_keys($data));
        $placeholders = str_repeat("?, ", count($data) - 1) . "?";
        $values = array_values($data);

        $insertQuery = "INSERT INTO $table ($columns) VALUES ($placeholders)
                        ON DUPLICATE KEY UPDATE " . $this->getUpdateString(array_keys($data));

        $stmt = $this->conn->prepare($insertQuery);

        if ($stmt === false) {
            die("Error preparing statement: " . $this->conn->error);
        }

        // Привязка параметров
        $types = $this->getBindTypes($values);
        $stmt->bind_param($types, ...$values);

        // Выполнение запроса
        if ($stmt->execute()) {
            $stmt->close();
            return true; // Успешная вставка
        } else {
            die("Error executing statement: " . $stmt->error);
        }
    }

    private function getUpdateString($columns) {
        $updateString = "";
        foreach ($columns as $column) {
            $updateString .= "$column = VALUES($column), ";
        }
        return rtrim($updateString, ", ");
    }

    private function getBindTypes($values) {
        $types = "";
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= "i"; // integer
            } elseif (is_float($value)) {
                $types .= "d"; // double
            } else {
                $types .= "s"; // string
            }
        }
        return $types;
    }

    public function closeConnection() {
        $this->conn->close();
    }
}

