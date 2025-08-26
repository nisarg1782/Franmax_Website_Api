<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
      ru.id,
      b.register_id,
      ru.name,
      bp.photo_url as logo,
      b.total_outlets,
      master_category.mas_cat_name AS sector
    FROM brands b
    LEFT JOIN registred_user ru ON ru.id = b.register_id
    LEFT JOIN master_category ON master_category.mas_cat_id = ru.mas_cat_id
    JOIN brand_plan_map bpm ON bpm.register_id = b.register_id
    JOIN brand_photos bp ON bp.brand_id = b.register_id AND bp.photo_type = 'logo'
    WHERE bpm.plan_category_id=?
";

$brandsStmt = $conn->prepare($brandsSql);
$brandsStmt->bind_param("i", $categoryId);
$brandsStmt->execute();
$brandsResult = $brandsStmt->get_result();

$brands = [];

// Helper to parse investment or area ranges
$parseRange = function ($rangeString) {
    $rangeString = trim($rangeString);
    $clean = function ($val) {
        return (int)preg_replace('/[^\d]/', '', $val);
    };

    // Case 1: open-ended with '+'
    if (strpos($rangeString, '+') !== false) {
        return [
            'min' => $clean($rangeString),
            'max' => null
        ];
    }

    // Case 2: proper dash-separated range
    $parts = explode('-', $rangeString);
    if (count($parts) === 2) {
        return [
            'min' => $clean($parts[0]),
            'max' => $clean($parts[1])
        ];
    }

    // Case 3: single fixed value
    $val = $clean($rangeString);
    return [
        'min' => $val,
        'max' => $val
    ];
};

// Build brand list
while ($row = $brandsResult->fetch_assoc()) {
    $brand = [
        "id" => $row['id'],
        "register_id" => $row['register_id'],
        "logo" => $row['logo'],
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

    $registerId = $brand['id'];

    // --- Single Unit Details ---
    $stmtSingle = $conn->prepare("SELECT * FROM single_unit_details WHERE register_id = ?");
    $stmtSingle->bind_param("i", $registerId);
    $stmtSingle->execute();
    $singleResult = $stmtSingle->get_result();
    if ($singleUnit = $singleResult->fetch_assoc()) {
        $brand['single_unit_details'] = $singleUnit;

        if (!empty($singleUnit['investment'])) {
            $inv = $parseRange($singleUnit['investment']);
            $brand['min_investment'] = $inv['min'];
            $brand['max_investment'] = $inv['max'];
        }
        if (!empty($singleUnit['area_req'])) {
            $area = $parseRange($singleUnit['area_req']);
            $brand['min_area'] = $area['min'];
            $brand['max_area'] = $area['max'];
        }
    }
    $stmtSingle->close();

    // --- Master Unit Details ---
    $stmtMaster = $conn->prepare("SELECT * FROM master_unit_details WHERE register_id = ?");
    $stmtMaster->bind_param("i", $registerId);
    $stmtMaster->execute();
    $masterResult = $stmtMaster->get_result();
    $masterUnits = [];
    while ($rowM = $masterResult->fetch_assoc()) {
        $masterUnits[] = $rowM;
    }
    $stmtMaster->close();

    if (!empty($masterUnits)) {
        $brand['master_unit_details'] = count($masterUnits) === 1 ? $masterUnits[0] : $masterUnits;

        foreach ($masterUnits as $details) {
            if (!empty($details['investment'])) {
                $inv = $parseRange($details['investment']);
                if ($brand['min_investment'] === null || $inv['min'] < $brand['min_investment']) {
                    $brand['min_investment'] = $inv['min'];
                }
                if ($brand['max_investment'] === null || ($inv['max'] !== null && $inv['max'] > $brand['max_investment'])) {
                    $brand['max_investment'] = $inv['max'];
                }
                // If any unit has open-ended max, keep it open-ended
                if ($inv['max'] === null) {
                    $brand['max_investment'] = null;
                }
            }
            if (!empty($details['area_req'])) {
                $area = $parseRange($details['area_req']);
                if ($brand['min_area'] === null || $area['min'] < $brand['min_area']) {
                    $brand['min_area'] = $area['min'];
                }
                if ($brand['max_area'] === null || ($area['max'] !== null && $area['max'] > $brand['max_area'])) {
                    $brand['max_area'] = $area['max'];
                }
                if ($area['max'] === null) {
                    $brand['max_area'] = null;
                }
            }
        }
    }

    // --- Format investment display correctly ---
    if ($brand['max_investment'] === null && $brand['min_investment'] !== null) {
        // open-ended
        $brand['investment_display'] = $brand['min_investment'] . "-above";
    } elseif ($brand['min_investment'] !== null && $brand['max_investment'] !== null && $brand['min_investment'] !== $brand['max_investment']) {
        $brand['investment_display'] = $brand['min_investment'] . " - " . $brand['max_investment'];
    } elseif ($brand['min_investment'] !== null) {
        $brand['investment_display'] = (string)$brand['min_investment'];
    } else {
        $brand['investment_display'] = null;
    }

    $brands[] = $brand;
}

echo json_encode([
    "success" => true,
    "brands" => $brands
]);

$conn->close();
?>
