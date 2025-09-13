<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include DB connection
include "db.php";

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$id = intval($data['id']);
$fields = $data;
unset($fields['id']);

// Build update query
$setParts = [];
$params = [];
$types = "";

foreach ($fields as $key => $value) {
    $setParts[] = "$key = ?";
    $params[] = $value;
    $types .= "s"; // all fields treated as string
}

$sql = "UPDATE inhouse_investors SET " . implode(", ", $setParts) . " WHERE id = ?";
$params[] = $id;
$types .= "i";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Investor updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>
