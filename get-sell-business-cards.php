<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// DB connection
include "db.php";

// Updated query with UUID and JOIN for city
$sql = "
    SELECT 
        sbr.uuid,
        sbr.image, 
        sbr.expected_amount,
        cities.name AS city_name
    FROM 
        sell_business_requests AS sbr
    LEFT JOIN 
        cities ON cities.id = sbr.city_id
    ORDER BY sbr.id DESC
";

$result = $conn->query($sql);
$cards = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['image'] = "http://localhost/react-api/uploads/" . $row['image'];
        $cards[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "cards" => $cards
]);

$conn->close();
