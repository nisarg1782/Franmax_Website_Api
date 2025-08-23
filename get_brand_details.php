<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include "db.php";

// Validate product_id
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Product ID is missing."]);
    exit();
}

$categoryId = intval($_GET['product_id']);

// Fetch brands
$brandsSql = "
    SELECT
        ru.id AS register_id,
        ru.name,
        b.total_outlets,
        master_category.mas_cat_name AS sector,
        category.cat_name AS category_name,
        subcategory.subcat_name AS subcategory_name,
        bpm.plan_category_id
    FROM brands b
    LEFT JOIN registred_user ru ON ru.id = b.register_id
    LEFT JOIN master_category ON master_category.mas_cat_id = ru.mas_cat_id
    LEFT JOIN category ON category.cat_id = b.cat_id
    LEFT JOIN subcategory ON subcategory.subcat_id = b.sub_cat_id
    JOIN brand_plan_map bpm ON bpm.register_id = b.register_id
    WHERE bpm.register_id = ?
";

$stmt = $conn->prepare($brandsSql);
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();
$brands = $result->fetch_all(MYSQLI_ASSOC);

foreach ($brands as &$brand) {
    $registerId = $brand['register_id'];

    // --- Premium ---
    $brand['premium'] = in_array($brand['plan_category_id'], [1, 3, 4]);

    $brand['min_investment'] = null;
    $brand['max_investment'] = null;
    $brand['min_area'] = null;
    $brand['max_area'] = null;
    $brand['single_unit_details'] = null;
    $brand['master_unit_details'] = null;
    $brand['images'] = [];
    $brand['expansions'] = [];

    // --- SINGLE UNIT ---
    $singleUnitStmt = $conn->prepare("SELECT * FROM single_unit_details WHERE register_id = ?");
    $singleUnitStmt->bind_param("i", $registerId);
    $singleUnitStmt->execute();
    $singleUnitResult = $singleUnitStmt->get_result();
    $singleUnitDetails = $singleUnitResult->fetch_assoc();
    if ($singleUnitDetails) {
        $brand['single_unit_details'] = $singleUnitDetails;

        $investmentRange = explode('-', $singleUnitDetails['investment']);
        if (count($investmentRange) === 2) {
            $brand['min_investment'] = (int)$investmentRange[0];
            $brand['max_investment'] = (int)$investmentRange[1];
        }

        $areaRange = explode('-', $singleUnitDetails['area_req']);
        if (count($areaRange) === 2) {
            $brand['min_area'] = (int)$areaRange[0];
            $brand['max_area'] = (int)$areaRange[1];
        }
    }

    // --- MASTER UNIT ---
    $masterUnitStmt = $conn->prepare("SELECT * FROM master_unit_details WHERE register_id = ?");
    $masterUnitStmt->bind_param("i", $registerId);
    $masterUnitStmt->execute();
    $masterUnitResult = $masterUnitStmt->get_result();
    $masterUnitDetails = $masterUnitResult->fetch_all(MYSQLI_ASSOC);

    if (!empty($masterUnitDetails)) {
        $brand['master_unit_details'] = count($masterUnitDetails) === 1 ? $masterUnitDetails[0] : $masterUnitDetails;

        foreach ($masterUnitDetails as $details) {
            $investmentRange = explode('-', $details['investment']);
            if (count($investmentRange) === 2) {
                $min = (int)$investmentRange[0];
                $max = (int)$investmentRange[1];
                if ($brand['min_investment'] === null || $min < $brand['min_investment']) $brand['min_investment'] = $min;
                if ($brand['max_investment'] === null || $max > $brand['max_investment']) $brand['max_investment'] = $max;
            }

            $areaRange = explode('-', $details['area_req']);
            if (count($areaRange) === 2) {
                $min = (int)$areaRange[0];
                $max = (int)$areaRange[1];
                if ($brand['min_area'] === null || $min < $brand['min_area']) $brand['min_area'] = $min;
                if ($brand['max_area'] === null || $max > $brand['max_area']) $brand['max_area'] = $max;
            }
        }
    }

    // --- BRAND IMAGES ---
    $imagesStmt = $conn->prepare("SELECT * FROM brand_photos WHERE brand_id = ?");
    $imagesStmt->bind_param("i", $registerId);
    $imagesStmt->execute();
    $imagesResult = $imagesStmt->get_result();
    $brandImages = $imagesResult->fetch_all(MYSQLI_ASSOC);
    if (!empty($brandImages)) $brand['images'] = $brandImages;

    // --- BRAND EXPANSION ---
    $expStmt = $conn->prepare("SELECT * FROM brand_expansion WHERE register_id = ?");
    $expStmt->bind_param("i", $registerId);
    $expStmt->execute();
    $expResult = $expStmt->get_result();
    $expansions = $expResult->fetch_all(MYSQLI_ASSOC);

    foreach ($expansions as &$exp) {
        // States
        $stateIds = array_filter(array_map('trim', explode(',', $exp['state_id'])));
        $exp['state_names'] = [];
        if (!empty($stateIds)) {
            $placeholders = implode(',', array_fill(0, count($stateIds), '?'));
            $types = str_repeat('i', count($stateIds));
            $stateStmt = $conn->prepare("SELECT name FROM states WHERE id IN ($placeholders)");
            $stateStmt->bind_param($types, ...$stateIds);
            $stateStmt->execute();
            $stateResult = $stateStmt->get_result();
            while ($row = $stateResult->fetch_assoc()) {
                $exp['state_names'][] = $row['name'];
            }
        }

        // Cities
        $cityIds = array_filter(array_map('trim', explode(',', $exp['city_id'])));
        $exp['city_names'] = [];
        if (!empty($cityIds)) {
            $placeholders = implode(',', array_fill(0, count($cityIds), '?'));
            $types = str_repeat('i', count($cityIds));
            $cityStmt = $conn->prepare("SELECT name FROM cities WHERE id IN ($placeholders)");
            $cityStmt->bind_param($types, ...$cityIds);
            $cityStmt->execute();
            $cityResult = $cityStmt->get_result();
            while ($row = $cityResult->fetch_assoc()) {
                $exp['city_names'][] = $row['name'];
            }
        }
    }
    unset($exp);
    $brand['expansions'] = $expansions;
}
unset($brand);

if (!empty($brands)) {
    http_response_code(200);
    echo json_encode(["success" => true, "brands" => $brands], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "No brands found for this category."]);
}

$conn->close();
?>
