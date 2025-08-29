<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");


// DB Connection
include "db.php";
// Get JSON input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Validate fields
if (!isset($data['id']) || !isset($data['status']) || !isset($data['mode'])) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

$id = (int)$data['id'];
$status = $conn->real_escape_string(trim($data['status']));
$mode = $conn->real_escape_string(trim($data['mode']));

// Run Update
$sql = "UPDATE registred_user SET status = '$status', mode = '$mode' WHERE id = $id";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Brand updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed: " . $conn->error]);
}

$conn->close();
