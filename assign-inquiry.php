<?php
include "db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Debugging: if data is empty, log it
if (!$data) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON or no data received"]);
    exit();
}

if (!isset($data['inquiry_id']) || !isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(["message" => "Missing parameters"]);
    exit();
}

$inquiryId = intval($data['inquiry_id']);
$userId = intval($data['user_id']);

// ✅ Make sure your DB column is spelled correctly: assigned_to (not assined_to)
$sql = "UPDATE inhouse_brands SET assined_to=? WHERE id=?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["message" => "SQL Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $userId, $inquiryId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Inquiry assigned successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $stmt->error]);
}
