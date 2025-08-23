<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include 'db.php';

$sql = "SELECT * FROM master_category";
$result = $conn->query($sql);

$master_category = [];

while ($row = $result->fetch_assoc()) {
  $master_category[] = $row;
}

echo json_encode($master_category);
?>
