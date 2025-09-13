<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['investor_id']) || !isset($data['user_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$investorId = intval($data['investor_id']);
$userId = intval($data['user_id']);

// Update the investor assignment
$sql = "UPDATE inhouse_investors SET assined_to = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $investorId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Investor assigned successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to assign investor"]);
}

$stmt->close();
$conn->close();
?>
