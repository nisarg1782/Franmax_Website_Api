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
$required = ['name', 'mobile', 'email', 'password', 'state_id', 'city_id', 'mas_cat_id', 'user_name'];
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
$name = trim($data['name']);
$username = trim($data['user_name']);
$mobile = trim($data['mobile']);
$email = trim($data['email']);
$hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
$state_id = (int)$data['state_id'];
$city_id = (int)$data['city_id'];
$sector_id = (int)$data['mas_cat_id'];
$mode = 'offline';
$status = 'active';
$user_type = "brand";

// 6. Check for duplicate user_name or email
$check_sql = "SELECT id FROM registred_user WHERE user_name = ? OR email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ss", $username, $email);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "Username or Email already exists."]);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// 7. Insert into table using a prepared statement
$sql = "INSERT INTO registred_user (name, mobile, email, password, state_id, city_id, mas_cat_id, mode, status, user_name, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "SQL statement preparation failed: " . $conn->error]);
    $conn->close();
    exit;
}

// 'ssssiisss' defines the types of the 11 parameters:
// s = string, i = integer
$stmt->bind_param("ssssiiissss", $name, $mobile, $email, $hashed_password, $state_id, $city_id, $sector_id, $mode, $status, $username, $user_type);

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