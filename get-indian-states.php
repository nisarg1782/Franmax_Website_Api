

<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include 'db.php';

$country_id = 101;

$stmt = $conn->prepare("SELECT id, name FROM states WHERE country_id = ? ORDER BY name");
$stmt->bind_param("i", $country_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
