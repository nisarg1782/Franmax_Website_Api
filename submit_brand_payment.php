<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database connection
include "db.php";

// Read input JSON
$data = json_decode(file_get_contents("php://input"), true);

$payment_id        = isset($data['payment_id']) ? trim($data['payment_id']) : '';
$payment_date_time = isset($data['payment_date_time']) ? trim($data['payment_date_time']) : '';
$user_id           = isset($data['user_id']) ? intval($data['user_id']) : 0;
$plan_id           = isset($data['plan_id']) ? intval($data['plan_id']) : 0;
$amount            = isset($data['amount']) ? floatval($data['amount']) : 0.0;
$months            = isset($data['month']) ? intval($data['month']) : 0;

// Validate inputs
if (empty($payment_id) || empty($payment_date_time) || !$user_id || !$plan_id || !$amount || !$months) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required parameters."
    ]);
    exit;
}
// Determine default leads based on plan ID

// Calculate plan start and end dates
$startDate = date('Y-m-d');
$endDate   = date('Y-m-d', strtotime("+$months months", strtotime($startDate)));

// Prepare SQL with ON DUPLICATE KEY UPDATE
$sql = "INSERT INTO brand_plan_map 
        (register_id, plan_category_id, payment_id, payment_date_time, 
          last_update_at, amount, plan_start_date, plan_end_date)
        VALUES (?, ?, ?, ?,NOW(), ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            plan_category_id = VALUES(plan_category_id),
            payment_id = VALUES(payment_id),
            payment_date_time = VALUES(payment_date_time),
            last_update_at = NOW(),
            amount = VALUES(amount),
            plan_start_date = VALUES(plan_start_date),
            plan_end_date = VALUES(plan_end_date)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

// Bind parameters
$stmt->bind_param(
    "iissdss",
    $user_id,
    $plan_id,
    $payment_id,
    $payment_date_time,
    $amount,
    $startDate,
    $endDate
);

// Execute and respond
if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Payment recorded/updated successfully.",
        "payment_id" => $payment_id,
        "plan_start_date" => $startDate,
        "plan_end_date" => $endDate
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
