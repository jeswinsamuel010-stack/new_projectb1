<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'construction_erp1');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    // Select database
    $conn->select_db(DB_NAME);
} else {
    die("Error creating database: " . $conn->error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Function to get database connection
function getDB() {
    global $conn;
    return $conn;
}

// Function to run prepared statement
function runQuery($sql, $types = "", $params = []) {
    $conn = getDB();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ["success" => false, "message" => "Prepare failed: " . $conn->error];
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $stmt->close();
        return ["success" => true, "data" => $result];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ["success" => false, "message" => $error];
    }
}

// Function to insert and get last ID
function insertAndGetId($sql, $types = "", $params = []) {
    $conn = getDB();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ["success" => false, "message" => "Prepare failed: " . $conn->error];
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
        $stmt->close();
        return ["success" => true, "id" => $last_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ["success" => false, "message" => $error];
    }
}

// Function to fetch all rows
function fetchAll($sql, $types = "", $params = []) {
    $conn = getDB();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    return $rows;
}

// Function to fetch single row
function fetchOne($sql, $types = "", $params = []) {
    $rows = fetchAll($sql, $types, $params);
    return !empty($rows) ? $rows[0] : null;
}