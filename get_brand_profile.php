<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

$brandId = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;

if ($brandId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid brand ID"]);
    exit;
}

// Helper function to fetch multiple rows
function fetchAll($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

// Helper function to fetch single row
function fetchOne($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

try {
    // --- Get brand basic info ---
    $stmt = $conn->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->bind_param("i", $brandId);
    $brand = fetchOne($stmt);
    $stmt->close();

    if (!$brand) {
        http_response_code(404);
        echo json_encode(["error" => "Brand not found"]);
        exit;
    }

    // --- Franchise types ---
    $stmt = $conn->prepare("
        SELECT ft.name 
        FROM brand_franchise_type bft 
        JOIN franchise_types ft ON ft.id = bft.franchise_type_id 
        WHERE bft.brand_id = ?
    ");
    $stmt->bind_param("i", $brandId);
    $franchiseTypes = [];
    $result = $stmt->execute() ? $stmt->get_result() : false;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $franchiseTypes[] = $row['name'];
        }
    }
    $stmt->close();

    // --- Business models ---
    $stmt = $conn->prepare("SELECT model FROM brand_models WHERE brand_id = ?");
    $stmt->bind_param("i", $brandId);
    $models = [];
    $result = $stmt->execute() ? $stmt->get_result() : false;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $models[] = $row['model'];
        }
    }
    $stmt->close();

    // --- Plan categories ---
    $stmt = $conn->prepare("SELECT category FROM brand_plan_categories WHERE brand_id = ?");
    $stmt->bind_param("i", $brandId);
    $planCategories = [];
    $result = $stmt->execute() ? $stmt->get_result() : false;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $planCategories[] = $row['category'];
        }
    }
    $stmt->close();

    // --- Single unit details ---
    $stmt = $conn->prepare("
        SELECT investment_required, roi, payback_period, area_required AS area 
        FROM single_unit_details 
        WHERE brand_id = ?
    ");
    $stmt->bind_param("i", $brandId);
    $singleUnit = fetchOne($stmt);
    $stmt->close();
    if (!$singleUnit) {
        $singleUnit = [
            "investment_required" => null,
            "roi" => null,
            "payback_period" => null,
            "area" => null
        ];
    }

    // --- Master unit details ---
    $stmt = $conn->prepare("
        SELECT level, investment_required, roi, payback_period, area_required AS area 
        FROM master_unit_details 
        WHERE brand_id = ? 
        ORDER BY FIELD(level, 'country', 'state', 'city')
    ");
    $stmt->bind_param("i", $brandId);
    $masterUnits = fetchAll($stmt);
    $stmt->close();

    // --- Build final response ---
    $response = [
        "id" => $brand["id"],
        "name" => $brand["name"],
        "description" => $brand["description"],
        "established_year" => $brand["established_year"],
        "website" => $brand["website"],
        "logo" => $brand["logo"],
        "company_name" => $brand["company_name"],
        "search_key" => $brand["search_key"],
        "brand_email" => $brand["brand_email"],
        "phone" => $brand["phone"],
        "bd_manager_name" => $brand["bd_manager_name"],
        "bd_manager_email" => $brand["bd_manager_email"],
        "bd_manager_contact" => $brand["bd_manager_contact"],
        "address" => $brand["address"],
        "total_outlets" => $brand["total_outlets"],
        "franchise_owned_outlets" => $brand["franchise_owned_outlets"],
        "company_owned_outlets" => $brand["company_owned_outlets"],
        "marketing_materials_available" => $brand["marketing_materials_available"],
        "franchise_years" => $brand["franchise_years"],
        "is_term_renewable" => $brand["is_term_renewable"],
        "has_operating_manuals" => $brand["has_operating_manuals"],
        "training_location" => $brand["training_location"],
        "field_assistance_available" => $brand["field_assistance_available"],
        "head_office_assistance" => $brand["head_office_assistance"],
        "it_systems_included" => $brand["it_systems_included"],

        "franchise_types" => $franchiseTypes,
        "modals" => $models,
        "plan_categories" => $planCategories,

        "single_unit" => $singleUnit,
        "master_units" => $masterUnits,

        "all_franchise_types" => ["Unit", "Single Unit", "Master", "Multi-unit", "Area Developer", "Regional", "Exclusive"],
        "all_modals" => ["FOFO", "FOCO"],
        "all_plan_categories" => ["Premium", "Standard", "Low Budget"]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Query failed: " . $e->getMessage()]);
}

$conn->close();
?>
