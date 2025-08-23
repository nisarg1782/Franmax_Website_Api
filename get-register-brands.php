<?php
// CORS headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// DB connection
include "db.php";

// Fetch brands with joins (assuming lookup tables exist)
$sql = "
    SELECT 
        br.id,
        br.name,
        br.mobile,
        br.email,
        br.mode,
        br.status,
        br.created_at,
        s.name AS state_name,
        c.name AS city_name,
        mas.mas_cat_name AS sector_name
    FROM brand_registration br
    LEFT JOIN states s ON br.state_id = s.id
    LEFT JOIN cities c ON br.city_id = c.id
    LEFT JOIN master_category mas ON br.sector_id = mas.mas_cat_id
    ORDER BY br.created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $brands
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $conn->error
    ]);
}

$conn->close();
