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

// Fetch brands with joins
$sql = "
    SELECT 
        ru.*,
        mc.mas_cat_name AS sector_name,
        s.name AS state_name,
        c.name AS city_name
    FROM 
        registred_user AS ru
    LEFT JOIN 
        master_category AS mc ON ru.mas_cat_id = mc.mas_cat_id
    LEFT JOIN 
        states AS s ON ru.state_id = s.id
    LEFT JOIN 
        cities AS c ON ru.city_id = c.id
    WHERE 
        ru.user_type = 'brand'
    ORDER BY 
        ru.id DESC
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