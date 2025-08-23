<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database Connection
include 'db.php';

// Check if mas_cat_id is provided
if (!isset($_GET['mas_cat_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "mas_cat_id is required"]);
    exit;
}

$mas_cat_id = intval($_GET['mas_cat_id']); // Sanitize input

// Fetch subcategories from DB
$sql = "SELECT cat_id, cat_name FROM category WHERE mas_cat_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mas_cat_id);
$stmt->execute();

$result = $stmt->get_result();
$subcategories = [];

while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

echo json_encode($subcategories);

$conn->close();
