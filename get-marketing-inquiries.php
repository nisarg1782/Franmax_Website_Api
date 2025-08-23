<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "db.php";

$sql = "SELECT mf.id, mf.name, mf.email, mf.contact, mf.brand_name, mf.services, mf.created_at,
               s.name AS state_name, c.name AS city_name
        FROM marketing_inquiries mf
        LEFT JOIN states s ON mf.state_id = s.id
        LEFT JOIN cities c ON mf.city_id = c.id
        ORDER BY mf.id DESC";

$result = $conn->query($sql);
$data = [];

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }
  echo json_encode(["status" => "success", "inquiries" => $data]);
} else {
  echo json_encode(["status" => "error", "message" => "Query failed"]);
}
?>
