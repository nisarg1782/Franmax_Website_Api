<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "db.php";
$sql = "
  SELECT p.id AS plan_id, p.name, p.price, p.time_duration, b.benefit
  FROM plan_categories p
  LEFT JOIN plan_benefits b ON p.id = b.plan_id
  where p.status = 1
  ORDER BY p.id ASC
";

$result = $conn->query($sql);
$plans = [];

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $plan_id = $row['plan_id'];

    if (!isset($plans[$plan_id])) {
      $plans[$plan_id] = [
        'name' => $row['name'],
        'price' => $row['price'],
        'time_duration' => $row['time_duration'],
        'benefits' => []
      ];
    }

    if ($row['benefit']) {
      $plans[$plan_id]['benefits'][] = $row['benefit'];
    }
  }

  echo json_encode(["success" => true, "plans" => array_values($plans)]);
} else {
  echo json_encode(["success" => false, "plans" => []]);
}

$conn->close();
?>
