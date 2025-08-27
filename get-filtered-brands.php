<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$mas_cat = isset($_GET['mas_cat']) ? (int)$_GET['mas_cat'] : 0;
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sub = isset($_GET['sub']) ? (int)$_GET['sub'] : 0;

include "db.php";
// Base query
$brandsSql = "
    SELECT
        ru.id AS register_id,
        ru.name,
        bp.photo_url as logo,
        b.total_outlets,
        master_category.mas_cat_name AS sector
    FROM brands b
    LEFT JOIN registred_user ru ON ru.id = b.register_id
    LEFT JOIN master_category ON master_category.mas_cat_id = ru.mas_cat_id
    JOIN brand_plan_map bpm ON bpm.register_id = b.register_id
    JOIN brand_photos bp ON bp.brand_id = b.register_id AND bp.photo_type = 'logo'
    WHERE 1=1
";

// Append conditions only if params exist
if ($mas_cat > 0) {
    $brandsSql .= " AND ru.mas_cat_id = $mas_cat";
}
if ($cat > 0) {
    $brandsSql .= " AND b.cat_id = $cat";
}
if ($sub > 0) {
    $brandsSql .= " AND b.sub_cat_id = $sub";
}

$result = $conn->query($brandsSql);

$brands = [];
if ($result) {
    while ($brand = $result->fetch_assoc()) {
        $registerId = $brand['register_id'];

        // Initialize fields
        $brand['min_investment'] = null;
        $brand['max_investment'] = null;
        $brand['min_area'] = null;
        $brand['max_area'] = null;
        $brand['single_unit_details'] = null;
        $brand['master_unit_details'] = [];

        // --- Single unit details ---
        $stmt = $conn->prepare("SELECT * FROM single_unit_details WHERE register_id = ?");
        $stmt->bind_param("i", $registerId);
        $stmt->execute();
        $singleResult = $stmt->get_result();
        $singleUnitDetails = $singleResult->fetch_assoc();
        $stmt->close();

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

        // --- Master unit details ---
        $stmt = $conn->prepare("SELECT * FROM master_unit_details WHERE register_id = ?");
        $stmt->bind_param("i", $registerId);
        $stmt->execute();
        $masterResult = $stmt->get_result();
        $masterUnitDetails = [];
        while ($row = $masterResult->fetch_assoc()) {
            $masterUnitDetails[] = $row;
        }
        $stmt->close();

        if (!empty($masterUnitDetails)) {
            $brand['master_unit_details'] = $masterUnitDetails;

            foreach ($masterUnitDetails as $details) {
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
}

if (!empty($brands)) {
    http_response_code(200);
    echo json_encode(["success" => true, "brands" => $brands], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "No brands found for this filter."]);
}

$conn->close();
?>
