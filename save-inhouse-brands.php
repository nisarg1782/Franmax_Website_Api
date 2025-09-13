<?php

// Set headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed."));
    exit();
}

// Database connection details (replace with your actual credentials)
include "db.php";

// Get the posted data
$data = json_decode(file_get_contents("php://input"), true);

// Check if data is valid
if (
    !isset($data['masterCategory']) || !isset($data['category']) || !isset($data['subCategory']) ||
    !isset($data['state']) || !isset($data['city']) || !isset($data['brandName']) ||
    !isset($data['brandContact']) || !isset($data['ownerName']) || !isset($data['ownerContact']) ||
    !isset($data['contactPersonName']) || !isset($data['contactPersonNumber'])
) {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data. Required fields are missing."));
    exit();
}

// SQL query using prepared statements to prevent SQL injection
$sql = "INSERT INTO `inhouse_brands` (
    `masterCategory`, `category`, `subCategory`, `state`, `city`, `brandName`, 
    `brandContact`, `ownerName`, `ownerContact`, `contactPersonName`, `contactPersonNumber`, 
    `callDate`, `callTime`, `callRemark`, `meetingDate`, `meetingTime`, `meetingRemark`, 
    `product`, `offerPrice`, `counterPrice`, `remark`,`status`
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(array("message" => "Error preparing statement: " . $conn->error));
    exit();
}

// Corrected bind_param string: 5 integers, 14 strings, and 2 doubles
$type_string = "iiiii" . str_repeat("s", 14) . "dds";
$stmt->bind_param(
    $type_string,
    $data['masterCategory'],
    $data['category'],
    $data['subCategory'],
    $data['state'],
    $data['city'],
    $data['brandName'],
    $data['brandContact'],
    $data['ownerName'],
    $data['ownerContact'],
    $data['contactPersonName'],
    $data['contactPersonNumber'],
    $data['callDate'],
    $data['callTime'],
    $data['callRemark'],
    $data['meetingDate'],
    $data['meetingTime'],
    $data['meetingRemark'],
    $data['product'],
    $data['offerPrice'],
    $data['counterPrice'],
    $data['remark'],
    $data["stage"]
);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(array("message" => "Record created successfully."));
} else {
    http_response_code(500);
    echo json_encode(array("message" => "Error executing statement: " . $stmt->error));
}

$stmt->close();
$conn->close();
