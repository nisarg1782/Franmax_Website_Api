<?php
// Set the content type and CORS headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. Read and decode JSON input
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// 2. Check if JSON is valid and not empty
if (!$data) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Invalid or empty JSON data"]);
    exit;
}

// 3. Extract and validate fields
$required = ['name', 'mobile', 'email', 'password', 'state_id', 'city_id', 'mas_cat_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400); // Bad Request
        echo json_encode(["success" => false, "message" => "Field '$field' is required"]);
        exit;
    }
}

// 4. Connect to MySQL
include "db.php"; // Ensure this file sets up $conn as the MySQLi connection

// 5. Sanitize and prepare data
$name = $data['name'];
$mobile = $data['mobile'];
$email = $data['email'];
$hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
$state_id = (int)$data['state_id'];
$city_id = (int)$data['city_id'];
$sector_id = (int)$data['mas_cat_id']; // Map mas_cat_id to sector_id
$created_at = date('Y-m-d H:i:s');
$mode = 'offline';
$status = 'active';

// 6. Insert into table using a prepared statement
// This prevents SQL injection
$sql = "INSERT INTO brand_registration (name, mobile, email, password, state_id, city_id, sector_id, created_at, mode, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "SQL statement preparation failed: " . $conn->error]);
    $conn->close();
    exit;
}

// 'ssssiiisss' defines the types of the parameters:
// s = string, i = integer
$stmt->bind_param("ssssiiisss", $name, $mobile, $email, $hashed_password, $state_id, $city_id, $sector_id, $created_at, $mode, $status);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode(["success" => true, "message" => "Brand registered successfully"]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Error executing statement: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>