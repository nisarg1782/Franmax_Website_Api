<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database credentials
include "db.php";

// Query: Join with states, cities, and sectors
$sql = "
    SELECT 
        bpi.*, 
        s.name AS state_name,
        c.name AS city_name,
        sec.business_name AS business_name
    FROM business_enquiries bpi
    LEFT JOIN states s ON s.id = bpi.state_id
    LEFT JOIN cities c ON c.id = bpi.city_id
    LEFT JOIN sell_business_requests sec ON sec.uuid = bpi.business_id
    ORDER BY bpi.id DESC
";

$result = $conn->query($sql);
$inquiries = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $inquiries[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'inquiries' => $inquiries
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'inquiries' => []
    ]);
}

$conn->close();
?>
