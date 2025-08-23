<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// DB connection
include "db.php";

// Premium plan category ID (1 = Premium from your `plan_categories`)
$premiumPlanId = 1;

// SQL: join brands, plan map, and single unit details
$sql = "
    SELECT 
        b.id,
        b.name,
        b.logo,
        sud.investment_required,
        sud.area,
        b.total_outlets
    FROM brands b
    JOIN brand_plan_map bpm ON bpm.brand_id = b.id
    LEFT JOIN single_unit_details sud ON sud.brand_id = b.id
    WHERE bpm.plan_category_id =2
";
$stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $premiumPlanId);
$stmt->execute();
$result = $stmt->get_result();

$brands = [];
while ($row = $result->fetch_assoc()) {
    $brands[] = [
        "id" => $row['id'],
        "name" => $row['name'],
        "logo_url" => "http://localhost/react-api/uploads/" . $row['logo'], // change to match your actual path
        "investment_required" => $row['investment_required'] ?? 'N/A',
        "area_required" => $row['area'] ?? 'N/A',
        "total_outlets" => $row['total_outlets'] ?? 0
    ];
}

echo json_encode([
    "success" => true,
    "brands" => $brands
]);

$conn->close();
