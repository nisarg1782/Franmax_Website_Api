<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include "db.php";
// Join lease_properties with states and cities to get state and city names
$sql = "
    SELECT
    lp.owner_name,
    lp.contact,
    lp.email,
     lp.property_key,
        lp.address,
        lp.message,
        lp.expected_rent,
        lp.sqft,
        lp.property_type,
        lp.floor_type,
        lp.image_path,
        lp.created_at,
        s.name AS state_name,
        c.name AS city_name
    FROM lease_properties lp
    LEFT JOIN states s ON lp.state_id = s.id
    LEFT JOIN cities c ON lp.city_id = c.id
    ORDER BY lp.id DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $properties]);
} else {
    echo json_encode(['status' => 'success', 'data' => []]);
}

$conn->close();
