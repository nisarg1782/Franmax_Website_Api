<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "db.php";

$sql = "
    SELECT 
        sb.uuid,
        sb.full_name,
        sb.email,
        sb.expected_amount,
        sb.description,
        sb.image,
        s.name AS state_name,
        c.name AS city_name
    FROM sell_business_requests sb
    LEFT JOIN states s ON sb.state_id = s.id
    LEFT JOIN cities c ON sb.city_id = c.id
    ORDER BY sb.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$cards = [];
while ($row = $result->fetch_assoc()) {
    $cards[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "cards" => $cards
], JSON_UNESCAPED_UNICODE);
?>
