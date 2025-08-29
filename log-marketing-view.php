<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "db.php";


// Read the raw input

$input = file_get_contents("php://input");
file_put_contents('debug_log.txt', $input); // check this file

$data = json_decode($input, true);

// Validate decoding
if (!$data || !isset($data['inquiry_id'], $data['role_id'], $data['user_id'], $data['field'])) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit;
}

// Sanitize & assign
$inquiryId = intval($data['inquiry_id']);
$roleId = intval($data['role_id']);
$userId = intval($data['user_id']);
$field = $data['field'];
$description = "Clicked show button to view {$field}";

// Insert log
$stmt = $conn->prepare("INSERT INTO marketing_logs (inquiry_id, role_id, user_id, description) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $inquiryId, $roleId, $userId, $description);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Logging failed"]);
}
