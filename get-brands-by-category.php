<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database credentials
include "db.php";

// Validate category_id
if (!isset($_GET['category_id']) || empty($_GET['category_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Category ID is missing."]);
    exit();
}

$categoryId = intval($_GET['category_id']);

// Fetch brands
$brandsSql = "
    SELECT
        ru.id AS register_id,
        ru.name,
        b.total_outlets,
        bp.photo_url AS logo,
        master_category.mas_cat_name AS sector
    FROM brands b
    LEFT JOIN registred_user ru ON ru.id = b.register_id
    LEFT JOIN master_category ON master_category.mas_cat_id = ru.mas_cat_id
    JOIN brand_plan_map bpm ON bpm.register_id = b.register_id
    LEFT JOIN brand_photos bp ON bp.brand_id = b.register_id AND bp.photo_type = 'logo'
    WHERE bpm.plan_category_id = ?
";

$stmt = $conn->prepare($brandsSql);
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();

$brands = [];
while ($brand = $result->fetch_assoc()) {
    $registerId = $brand['register_id'];

    // Initialize fields
    $brand['min_investment'] = null;
    $brand['max_investment'] = null;
    $brand['min_area'] = null;
    $brand['max_area'] = null;
    $brand['single_unit_details'] = null;
    $brand['master_unit_details'] = null;

    // --- Single Unit Details ---
    $stmtSingle = $conn->prepare("SELECT * FROM single_unit_details WHERE register_id = ?");
    $stmtSingle->bind_param("i", $registerId);
    $stmtSingle->execute();
    $singleResult = $stmtSingle->get_result();
    $singleUnit = $singleResult->fetch_assoc();
    $stmtSingle->close();

    if ($singleUnit) {
        $brand['single_unit_details'] = $singleUnit;

        $investmentRange = explode('-', $singleUnit['investment']);
        if (count($investmentRange) === 2) {
            $brand['min_investment'] = (int)$investmentRange[0];
            $brand['max_investment'] = (int)$investmentRange[1];
        }

        $areaRange = explode('-', $singleUnit['area_req']);
        if (count($areaRange) === 2) {
            $brand['min_area'] = (int)$areaRange[0];
            $brand['max_area'] = (int)$areaRange[1];
        }
    }

    // --- Master Unit Details ---
    $stmtMaster = $conn->prepare("SELECT * FROM master_unit_details WHERE register_id = ?");
    $stmtMaster->bind_param("i", $registerId);
    $stmtMaster->execute();
    $masterResult = $stmtMaster->get_result();
    $masterUnits = [];
    while ($row = $masterResult->fetch_assoc()) {
        $masterUnits[] = $row;
    }
    $stmtMaster->close();

    if (!empty($masterUnits)) {
        $brand['master_unit_details'] = count($masterUnits) === 1 ? $masterUnits[0] : $masterUnits;

        foreach ($masterUnits as $details) {
            // Investment
            $investmentRange = explode('-', $details['investment']);
            if (count($investmentRange) === 2) {
                $min = (int)$investmentRange[0];
                $max = (int)$investmentRange[1];
                if ($brand['min_investment'] === null || $min < $brand['min_investment']) $brand['min_investment'] = $min;
                if ($brand['max_investment'] === null || $max > $brand['max_investment']) $brand['max_investment'] = $max;
            }

            // Area
            $areaRange = explode('-', $details['area_req']);
            if (count($areaRange) === 2) {
                $min = (int)$areaRange[0];
                $max = (int)$areaRange[1];
                if ($brand['min_area'] === null || $min < $brand['min_area']) $brand['min_area'] = $min;
                if ($brand['max_area'] === null || $max > $brand['max_area']) $brand['max_area'] = $max;
            }
        }
    }

    $brands[] = $brand;
}

if (!empty($brands)) {
    http_response_code(200);
    echo json_encode(["success" => true, "brands" => $brands], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "No brands found for this category."]);
}

$conn->close();
?>
