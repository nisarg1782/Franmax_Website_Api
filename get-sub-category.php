<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include 'db.php';

if (!isset($_GET['cat_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "cat_id is required"]);
    exit;
}

$cat_id = intval($_GET['cat_id']);

$sql = "SELECT subcat_id, subcat_name FROM subcategory WHERE cat_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cat_id);
$stmt->execute();

$result = $stmt->get_result();
$subcategories = [];

while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

echo json_encode($subcategories);

$conn->close();
