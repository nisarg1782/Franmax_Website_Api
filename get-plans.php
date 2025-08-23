<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "db.php";

$sql = "SELECT name, price, time_duration FROM plan_categories where status=1 ORDER BY FIELD(time_duration, 'Monthly', 'Quarterly', 'Yearly', 'Lifetime')";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $plans = [];
    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }
    echo json_encode(["success" => true, "plans" => $plans]);
} else {
    echo json_encode(["success" => false, "plans" => []]);
}

$conn->close();
