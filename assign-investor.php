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
// Log raw input to a file
file_put_contents('debug_assign_investor.log', "--- " . date('Y-m-d H:i:s') . " --- Raw Input:\n" . $raw . "\n", FILE_APPEND);
// --- DEBUGGING END ---

$data = json_decode($raw, true);

if ($data === null || !is_array($data)) {
    http_response_code(400); // Bad Request
    // --- DEBUGGING START ---
    file_put_contents('debug_assign_investor.log', "Decoded Data (Failed):\n" . print_r($data, true) . "\n", FILE_APPEND);
    // --- DEBUGGING END ---
    echo json_encode(["success" => false, "message" => "Invalid JSON input or empty data."]);
    exit;
}

// --- DEBUGGING START ---
// Log decoded data to a file
file_put_contents('debug_assign_investor.log', "Decoded Data (Success):\n" . print_r($data, true) . "\n", FILE_APPEND);
// --- DEBUGGING END ---

// Required fields. We rely on state_id and city_id.
// Note: 'state' and 'city' string names are sent by React but not required for DB insert if only IDs are stored.
// However, the PHP script was expecting them in the $required array. I've adjusted this based on your SQL.
$required = ['name', 'phone', 'email', 'state_id', 'city_id', 'message', 'brand_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400); // Bad Request
        echo json_encode(["success" => false, "message" => "Missing or empty field: '$field'"]);
        exit;
    }
}

// Sanitize and prepare data
$name = $data['name'];
$phone = $data['phone'];
$email = $data['email'];
// Removed $state and $city variables as they are not used in the INSERT query
$state_id = (int)$data['state_id'];
$city_id = (int)$data['city_id'];
$message = $data['message'];
$brand_id = (int)$data['brand_id'];
$created_at = date('Y-m-d H:i:s');

// 1. Check for existing record using a prepared statement to prevent SQL injection
$checkSql = "
    SELECT id FROM investor_inquiries
    WHERE email = ? AND contact = ? AND brand_id = ?
    LIMIT 1
";
$stmtCheck = $conn->prepare($checkSql);
if (!$stmtCheck) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepared statement for check failed: " . $conn->error]);
    $conn->close();
    exit;
}
$stmtCheck->bind_param("ssi", $email, $phone, $brand_id);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "Investor already assigned to this brand"]);
    $stmtCheck->close();
    $conn->close();
    exit;
}
$stmtCheck->close();

// 2. Insert into DB using a prepared statement for security
// Your SQL query now has 8 placeholders.
$sql = "
    INSERT INTO investor_inquiries 
    (name, contact, email, state_id, city_id, message, brand_id, inquiry_date)
    VALUES 
    (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepared statement for insert failed: " . $conn->error]);
    $conn->close();
    exit;
}

// ✅ FIX: The bind_param string must match the 8 parameters in the SQL query.
// 'sssiisis' -> name(s), phone(s), email(s), state_id(i), city_id(i), message(s), brand_id(i), inquiry_date(s)
$stmt->bind_param("sssiisis", $name, $phone, $email, $state_id, $city_id, $message, $brand_id, $created_at);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode(["success" => true, "message" => "Investor assigned successfully"]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
