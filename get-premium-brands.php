<?php

// top category brands
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// DB connection
include "db.php";

// SQL to fetch details from brands and registered_user
$brandsSql = "
    SELECT
      ru.id,
      ru.name,
      b.total_outlets,
      master_category.mas_cat_name AS sector
    FROM brands b
    LEFT JOIN registred_user ru
      ON ru.id = b.register_id
    LEFT JOIN master_category
      ON master_category.mas_cat_id = ru.mas_cat_id
    JOIN brand_plan_map bpm
      ON bpm.register_id = b.register_id
    WHERE bpm.plan_category_id=1
";

$brandsStmt = $conn->prepare($brandsSql);
$brandsStmt->execute();
$brandsResult = $brandsStmt->get_result();

$brands = [];

// Fetch initial brand data
while ($row = $brandsResult->fetch_assoc()) {
    $brand = [
        "id" => $row['id'],
        "name" => $row['name'],
        "total_outlets" => $row['total_outlets'] ?? 0,
        "sector" => $row["sector"],
        "min_investment" => null,
        "max_investment" => null,
        "min_area" => null,
        "max_area" => null,
        "single_unit_details" => null,
        "master_unit_details" => null
    ];
    $brands[] = $brand;
}

// Iterate through the fetched brands to find their unit details and calculate min/max values
foreach ($brands as &$brand) {
    $registerId = $brand['id'];
    
    // First, check the single_unit_details table
    $singleUnitSql = "SELECT * FROM single_unit_details WHERE register_id = ?";
    $singleUnitStmt = $conn->prepare($singleUnitSql);
    $singleUnitStmt->bind_param("i", $registerId);
    $singleUnitStmt->execute();
    $singleUnitResult = $singleUnitStmt->get_result();

    if ($singleUnitResult->num_rows > 0) {
        $details = $singleUnitResult->fetch_assoc();
        $brand['single_unit_details'] = $details;
        
        // Parse investment range
        $investmentRange = explode('-', $details['investment']);
        if (count($investmentRange) === 2) {
            $min = (int)$investmentRange[0];
            $max = (int)$investmentRange[1];
            $brand['min_investment'] = $min;
            $brand['max_investment'] = $max;
        }
        
        // Parse area range
        $areaRange = explode('-', $details['area_req']);
        if (count($areaRange) === 2) {
            $min = (int)$areaRange[0];
            $max = (int)$areaRange[1];
            $brand['min_area'] = $min;
            $brand['max_area'] = $max;
        }
    }
    
    // Then, check the master_unit_details table
    $masterUnitSql = "SELECT * FROM master_unit_details WHERE register_id = ?";
    $masterUnitStmt = $conn->prepare($masterUnitSql);
    $masterUnitStmt->bind_param("i", $registerId);
    $masterUnitStmt->execute();
    $masterUnitResult = $masterUnitStmt->get_result();
    
    if ($masterUnitResult->num_rows > 0) {
        $details = $masterUnitResult->fetch_assoc();
        $brand['master_unit_details'] = $details;
        
        // Parse investment range
        $investmentRange = explode('-', $details['investment']);
        if (count($investmentRange) === 2) {
            $min = (int)$investmentRange[0];
            $max = (int)$investmentRange[1];

            // Only update if the master unit details have a wider range
            if ($brand['min_investment'] === null || $min < $brand['min_investment']) {
                $brand['min_investment'] = $min;
            }
            if ($brand['max_investment'] === null || $max > $brand['max_investment']) {
                $brand['max_investment'] = $max;
            }
        }
        
        // Parse area range
        $areaRange = explode('-', $details['area_req']);
        if (count($areaRange) === 2) {
            $min = (int)$areaRange[0];
            $max = (int)$areaRange[1];
            
            // Only update if the master unit details have a wider range
            if ($brand['min_area'] === null || $min < $brand['min_area']) {
                $brand['min_area'] = $min;
            }
            if ($brand['max_area'] === null || $max > $brand['max_area']) {
                $brand['max_area'] = $max;
            }
        }
    }
}

echo json_encode([
    "success" => true,
    "brands" => $brands
]);

$conn->close();
?>
