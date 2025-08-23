<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Only POST allowed"]);
    exit;
}

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$required = ['name', 'email', 'contact', 'stateId', 'cityId', 'budget'];

foreach ($required as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing or empty field: $field"]);
        exit;
    }
}

// Sanitize inputs
$name     = trim($data['name']);
$email    = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
$contact  = trim($data['contact']);
$stateId  = (int)$data['stateId'];
$cityId   = (int)$data['cityId'];
$budget   = trim($data['budget']);
$brand    = isset($data['brand']) ? trim($data['brand']) : 'Open For All';
$sectorId = isset($data['sectorId']) ? $data['sectorId'] : "Open For All";

// Validations
if (!$email) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}
if (!preg_match("/^[6-9]\d{9}$/", $contact)) {
    echo json_encode(["success" => false, "message" => "Invalid contact number"]);
    exit;
}

// Insert
$stmt = $conn->prepare("
  INSERT INTO buy_business_requests (name, email, contact, state_id, city_id, budget, brand_name, sector_id)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("sssissss", $name, $email, $contact, $stateId, $cityId, $budget, $brand, $sectorId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Buy business request submitted successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
