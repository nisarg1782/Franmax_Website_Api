<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Allow CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

include "db.php";

// Get and validate JSON body
$input = json_decode(file_get_contents("php://input"), true);

$id = isset($input['id']) ? intval($input['id']) : 0;
$status = isset($input['status']) ? trim($input['status']) : '';
$remark = isset($input['remark']) ? trim($input['remark']) : '';
$comment = isset($input['comment']) ? trim($input['comment']) : '';

if ($id <= 0 || empty($status) || empty($remark) || empty($comment)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing or invalid fields"]);
    exit;
}

// Update inquiry
try {
    $stmt = $conn->prepare("
        UPDATE investor_inquiries
        SET status = ?, remark = ?, comment = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $status, $remark, $comment, $id);
    $stmt->execute();
    $stmt->close();

    // Get latest updated_at timestamp
    $fetchStmt = $conn->prepare("SELECT updated_at FROM investor_inquiries WHERE id = ?");
    $fetchStmt->bind_param("i", $id);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    $updatedRow = $result->fetch_assoc();
    $fetchStmt->close();

    echo json_encode([
        "success" => true,
        "updated_at" => $updatedRow ? $updatedRow['updated_at'] : null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Update failed", "error" => $e->getMessage()]);
}

$conn->close();
?>
