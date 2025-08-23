<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
  echo json_encode(["error" => "Invalid input"]);
  exit;
}

$sql = "
  UPDATE brands SET
    name = ?, description = ?, established_year = ?, website = ?,
    state_id = ?, city_id = ?, category_id = ?
  WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "ssssiiii",
  $data['name'], $data['description'], $data['established_year'], $data['website'],
  $data['state_id'], $data['city_id'], $data['category_id'], $data['id']
);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "error" => $stmt->error]);
}
