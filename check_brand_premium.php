<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$brandId = isset($data['brand_id']) ? intval($data['brand_id']) : 1;

if (!$brandId) {
    echo json_encode(['success' => false, 'error' => 'Brand ID required']);
    exit();
}

$stmt = $conn->prepare("SELECT plan_category_id FROM brand_plan_map WHERE register_id = ?");
$stmt->bind_param("i", $brandId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Brand not found']);
    exit();
}

$row = $result->fetch_assoc();
$isPremium = ($row['plan_category_id'] == 1 || $row['plan_category_id'] == 3 || $row['plan_category_id'] == 4) ? true : false;

echo json_encode([
    'success' => true,
    'is_premium' => $isPremium
]);

$stmt->close();
$conn->close();
?>
