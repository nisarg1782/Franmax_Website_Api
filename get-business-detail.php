<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "db.php";

$uuid = $_GET['uuid'] ?? '';

$stmt = $conn->prepare("
    SELECT sbr.*, cities.name AS city_name 
    FROM sell_business_requests AS sbr
    LEFT JOIN cities ON cities.id = sbr.city_id
    WHERE sbr.uuid = ?
");
$stmt->bind_param("s", $uuid);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    $data['image'] = "http://localhost/react-api/uploads/" . $data['image'];
    echo json_encode(["success" => true, "business" => $data]);
} else {
    echo json_encode(["success" => false, "message" => "Not found"]);
}

$conn->close();
