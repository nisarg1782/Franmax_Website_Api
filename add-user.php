<?php
// Set CORS headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    exit(0);
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection
include "db.php";

// Read and decode JSON input
$raw = file_get_contents("php://input");

// --- DEBUGGING START ---
// Log raw input to a file. Check 'debug_add_user.log' in the same directory as this PHP file.
// file_put_contents('debug_add_user.log', "--- " . date('Y-m-d H:i:s') . " --- Raw Input:\n" . $raw . "\n", FILE_APPEND);
// --- DEBUGGING END ---

$data = json_decode($raw, true);

if ($data === null || !is_array($data)) {
    http_response_code(400); // Bad Request
    // --- DEBUGGING START ---
    // file_put_contents('debug_add_user.log', "Decoded Data (Failed):\n" . print_r($data, true) . "\n", FILE_APPEND);
    // --- DEBUGGING END ---
    echo json_encode(["success" => false, "message" => "Invalid JSON input or empty data."]);
    exit;
}

// --- DEBUGGING START ---
// Log decoded data to a file
// file_put_contents('debug_add_user.log', "Decoded Data (Success):\n" . print_r($data, true) . "\n", FILE_APPEND);
// --- DEBUGGING END ---

// Required fields - now including 'permissions'
// Permissions are optional, so we allow an empty array for them.
$required = ['name', 'email', 'password', 'status', 'permissions'];
foreach ($required as $field) {
    // For 'permissions', we allow it to be an empty array, but it must be set.
    // For other fields, it must not be empty.
    if (!isset($data[$field]) || ($field !== 'permissions' && $data[$field] === '')) {
        http_response_code(400); // Bad Request
        echo json_encode(["success" => false, "message" => "Missing or empty field: '$field'"]);
        exit;
    }
}

// Sanitize and prepare data
$name = trim($data['name']);
$email = trim($data['email']);
$password = password_hash($data['password'], PASSWORD_BCRYPT); // Hash the password
$status = trim($data['status']);
$created_at = date('Y-m-d H:i:s'); // Current timestamp for creation

// ✅ CHANGE: Get permissions array and encode it to JSON string
$permissionsArray = $data['permissions'];
// Ensure it's an array before encoding, and encode it. If it's not an array, default to empty JSON array.
$permissionsJson = json_encode(is_array($permissionsArray) ? $permissionsArray : []);

// Validate status value
$allowedStatuses = ['active', 'not active'];
if (!in_array($status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid status value provided."]);
    exit;
}

// Check if email already exists
$checkEmailSql = "SELECT id FROM franmax_user WHERE email = ?";
$stmtCheck = $conn->prepare($checkEmailSql);
if (!$stmtCheck) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepared statement for email check failed: " . $conn->error]);
    $conn->close();
    exit;
}
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "User with this email already exists."]);
    $stmtCheck->close();
    $conn->close();
    exit;
}
$stmtCheck->close();

// ✅ CHANGE: Insert into 'franmax_user' table including the new 'permissions' column
$sql = "INSERT INTO franmax_user (name, email, password, status, created_at, permissions) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepared statement for insert failed: " . $conn->error]);
    $conn->close();
    exit;
}

// ✅ CHANGE: 'ssssss' -> name(s), email(s), password(s), status(s), created_at(s), permissions(s)
$stmt->bind_param("ssssss", $name, $email, $password, $status, $created_at, $permissionsJson);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode(["success" => true, "message" => "User added successfully!"]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Failed to add user: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
