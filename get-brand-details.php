<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "db.php";

$brandId = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
if (!$brandId) {
  echo json_encode(["error" => "Missing brand_id"]);
  exit;
}

$sql = "
  SELECT
    b.*, s.name AS state_name, c.name AS city_name, cat.name AS category_name
  FROM brands b
  LEFT JOIN states s ON b.state_id = s.id
  LEFT JOIN cities c ON b.city_id = c.id
  LEFT JOIN categories cat ON b.category_id = cat.id
  WHERE b.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $brandId);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

echo json_encode($data);
