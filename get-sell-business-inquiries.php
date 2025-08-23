<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database credentials
include "db.php"; // Assuming you have a separate file for DB credentials

// Query: Join with states, cities, and sectors
$sql = "
    SELECT 
        bpi.*, 
        s.name AS state_name,
        c.name AS city_name,
        mas.mas_cat_name AS sector_name
    FROM sell_business_requests bpi
    LEFT JOIN states s ON s.id = bpi.state_id
    LEFT JOIN cities c ON c.id = bpi.city_id
    LEFT JOIN master_category mas ON mas.mas_cat_id = bpi.sector_id
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
