<?php
include "db.php";

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method not allowed
    echo json_encode(["success" => false, "message" => "Only POST allowed"]);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Required fields including masterCategory
$required = ['masterCategory', 'name', 'contact', 'email', 'state', 'city', 'stage'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing field: $field"]);
        exit;
    }
}

// Prepare variables
$master_category = intval($data['masterCategory']);
$name = $data['name'];
$contact = $data['contact'];
$email = $data['email'];
$state = intval($data['state']);
$city = intval($data['city']);
$investment_budget = $data['investmentBudget'] ?? null;
$call_date = !empty($data['callDate']) ? $data['callDate'] : null;
$call_time = !empty($data['callTime']) ? $data['callTime'] : null;
$call_remark = $data['callRemark'] ?? null;
$meeting_date = !empty($data['meetingDate']) ? $data['meetingDate'] : null;
$meeting_time = !empty($data['meetingTime']) ? $data['meetingTime'] : null;
$meeting_remark = $data['meetingRemark'] ?? null;
$final_remark = $data['finalRemark'] ?? null;
$stage = $data['stage'];

// Insert into database
$sql = "INSERT INTO inhouse_investors 
(master_category, name, contact, email, state, city, investment_budget, call_date, call_time, call_remark, meeting_date, meeting_time, meeting_remark, final_remark, stage)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "isssissssssssss",
    $master_category,
    $name,
    $contact,
    $email,
    $state,
    $city,
    $investment_budget,
    $call_date,
    $call_time,
    $call_remark,
    $meeting_date,
    $meeting_time,
    $meeting_remark,
    $final_remark,
    $stage
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Investor form saved successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}
?>
