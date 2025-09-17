<?php
// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
    exit();
}

// DB connection
include "db.php";

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Must have `id` for update
if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(["message" => "Missing record id."]);
    exit();
}

// Allowed fields
$allowed = [
    "subCategory" => "s",
    "category" => "s",
    "masterCategory" => "s",
    "callDate"     => "s",
    "callTime"     => "s",
    "callRemark"   => "s",
    "meetingDate"  => "s",
    "meetingTime"  => "s",
    "meetingRemark" => "s",
    "product"      => "s",
    "offerPrice"   => "d",
    "counterPrice" => "d",
    "remark"       => "s",
    "status" => "s"
];

// Build query dynamically
$setParts = [];
$types    = "";
$values   = [];

foreach ($allowed as $field => $type) {
    if (isset($data[$field])) {
        $setParts[] = "`$field`=?";
        $types     .= $type;
        if ($type === "d") {
            $values[] = (float)$data[$field];
        } else {
            $values[] = $data[$field];
        }
    }
}

if (empty($setParts)) {
    http_response_code(400);
    echo json_encode(["message" => "No fields to update."]);
    exit();
}

$sql = "UPDATE `inhouse_brands` SET " . implode(", ", $setParts) . " WHERE `id`=?";
$types .= "i";
$values[] = (int)$data['id'];

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["message" => "Error preparing statement: " . $conn->error]);
    exit();
}

// Bind dynamically
$stmt->bind_param($types, ...$values);

// Execute
if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(["message" => "Record updated successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Error updating record: " . $stmt->error]);
}

$stmt->close();
$conn->close();
