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
$data = json_decode($raw, true);

if ($data === null || !is_array($data)) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Invalid JSON input or empty data."]);
    exit;
}

// Required fields for update
$required = ['id', 'name', 'email', 'status'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400); // Bad Request
        echo json_encode(["success" => false, "message" => "Missing or empty field: '$field'"]);
        exit;
    }
}

// Sanitize and prepare data
$id = (int)$data['id'];
$name = trim($data['name']);
$email = trim($data['email']);
$status = trim($data['status']);

// Validate status value
$allowedStatuses = ['active', 'not active'];
if (!in_array($status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid status value provided."]);
    exit;
}

// Check if the new email already exists for another user (excluding the current user being updated)
$checkEmailSql = "SELECT id FROM franmax_user WHERE email = ? AND id != ?";
$stmtCheck = $conn->prepare($checkEmailSql);
if (!$stmtCheck) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepared statement for email check failed: " . $conn->error]);
    $conn->close();
    exit;
}
$stmtCheck->bind_param("si", $email, $id); // s = string (email), i = integer (id)
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "Another user with this email already exists."]);
    $stmtCheck->close();
    $conn->close();
    exit;
}
$stmtCheck->close();


// Update 'franmax_user' table using a prepared statement
// Only updating name, email, and status
$sql = "UPDATE franmax_user SET name = ?, email = ?, status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepared statement for update failed: " . $conn->error]);
    $conn->close();
    exit;
}

// 'sssi' -> name(s), email(s), status(s), id(i)
$stmt->bind_param("sssi", $name, $email, $status, $id);

if ($stmt->execute()) {
    // Check if any rows were actually affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "User updated successfully!"]);
    } else {
        // If no rows affected, it might mean the data was the same, or ID not found
        echo json_encode(["success" => true, "message" => "User data is already up to date or user not found."]);
    }
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Failed to update user: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
