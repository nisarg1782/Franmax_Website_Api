<?php
// Set headers for CORS and content type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST method beyond this point
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit();
}

// Database connection details
include "db.php";

// Get the POST data
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Check if the JSON data is valid and not empty
if ($data === null) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Invalid or empty JSON payload."]);
    exit();
}

// Validate required fields
if (!isset($data['id']) || !isset($data['name']) || !isset($data['mobile']) || !isset($data['email']) || !isset($data['state_id']) || !isset($data['city_id']) || !isset($data['status'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Required fields are missing."]);
    exit();
}

// Extract and sanitize data from the payload
$investorId = (int)$data['id'];
$investorName = $conn->real_escape_string($data['name']);
$investorMobile = $conn->real_escape_string($data['mobile']);
$investorEmail = $conn->real_escape_string($data['email']);
$stateId = (int)$data['state_id'];
$cityId = (int)$data['city_id'];
$status = $conn->real_escape_string($data['status']);
$username= $conn->real_escape_string($data['user_name']);

// --- Add unique check for email and mobile number ---
// Check for existing email or mobile number on other records
$checkSql = "SELECT id FROM registred_user WHERE (user_name = ?) AND id != ?";
$checkStmt = $conn->prepare($checkSql);

if (!$checkStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepare statement failed for uniqueness check: " . $conn->error]);
    exit();
}

$checkStmt->bind_param("si", $username, $investorId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "User Name is already in use by another investor."]);
    $checkStmt->close();
    $conn->close();
    exit();
}
$checkStmt->close();
// --- End of unique check ---


// Prepare the UPDATE statement using a prepared statement to prevent SQL injection
$sql = "UPDATE registred_user SET 
    name = ?, 
    mobile = ?, 
    email = ?, 
    state_id = ?, 
    city_id = ?, 
    status = ?,
    user_name= ?
    WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepare statement failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("sssiissi", $investorName, $investorMobile, $investorEmail, $stateId, $cityId, $status, $username,$investorId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Investor updated successfully."]);
    } else {
        http_response_code(200);
        echo json_encode(["success" => false, "message" => "No changes were made to the investor or investor not found."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to update investor: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
