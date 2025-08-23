<?php
// CORS HEADERS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate
if (!isset($data['inquiry_id']) || !isset($data['brand_id'])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

// Extract
$inquiryId = (int)$data['inquiry_id'];
$brandId = (int)$data['brand_id'];
$description = isset($data['description']) ? $data['description'] : '';
$timestamp = date("Y-m-d H:i:s");

// DB connection
include "db.php";
// Insert log
$stmt = $conn->prepare("INSERT INTO contact_logs (inquiry_id, brand_id, viewed_at,description) VALUES (?, ?, ?,?)");
$stmt->bind_param("iiss", $inquiryId, $brandId, $timestamp,$description);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "logged" => true]);
} else {
    echo json_encode(["error" => "Failed to insert"]);
}
