<?php
// --- CORS Handling ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Database connection details ---
// Make sure 'db.php' is correctly configured to connect to your database.
include "db.php";

// --- Read and decode JSON body ---
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Invalid JSON payload."]);
    exit;
}

// --- Validate required fields from the React form ---
if (!isset($data['name']) || !isset($data['mobile']) || !isset($data['email']) || !isset($data['password']) || !isset($data['state_id']) || !isset($data['city_id']) || !isset($data['status']) || !isset($data['user_name'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Required fields are missing."]);
    exit();
}

// --- Extract and sanitize data, using the correct keys from the React form ---
$name = trim($data['name']);
$user_name = trim($data['user_name']);
$mobile = trim($data['mobile']);
$email = trim($data['email']);
$password = password_hash($data['password'], PASSWORD_BCRYPT);
$state_id = (int)$data['state_id'];
$city_id = (int)$data['city_id'];
$status = trim($data['status']);
$mode = "offline";
$user_type = "investor";

// --- Check if User Name or Email already exists ---
// It's good practice to check for both user name and email uniqueness.
$check = $conn->prepare("SELECT id FROM registred_user WHERE user_name = ?");
$check->bind_param("s", $user_name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "User Name already exists."]);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// --- Insert into DB ---
// This is the corrected line. It has 10 placeholders for 10 columns.
$stmt = $conn->prepare("INSERT INTO registred_user (name, mobile, email, password, state_id, city_id, status, mode, user_name, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
// This is the corrected line. It binds 10 variables.
$stmt->bind_param("ssssiissss", $name, $mobile, $email, $password, $state_id, $city_id, $status, $mode, $user_name, $user_type);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode(["success" => true, "message" => "Investor added successfully."]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>