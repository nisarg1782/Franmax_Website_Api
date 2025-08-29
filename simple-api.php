<?php

// --- 1. API Headers ---
// This allows cross-origin requests and specifies the response format as JSON.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// --- 2. Database Connection Details ---
// The 'db.php' file should contain your database connection logic.
// For example:
// $conn = new mysqli("localhost", "your_username", "your_password", "your_database");
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
include "db.php";

// --- 3. Get and Decode the Incoming JSON Data ---
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Check if data was received and decoded correctly
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON received or no data provided.']);
    exit();
}

// --- 4. Map Data to Table Columns ---
// This step carefully maps the incoming JSON keys to your table columns.
// We are using the null coalescing operator '??' to safely handle cases where a key might be missing.
$register_id = $data['id'] ?? null;
$description = $data['description'] ?? null;
$company_name = $data['name'] ?? null;
$brand_email = $data['email'] ?? null;
$phone = $data['mobile'] ?? null;
$bd_manager_name = $data['bd_manager_name'] ?? null;
$bd_manager_email = $data['bd_manager_email'] ?? null;
$bd_manager_contact = $data['bd_manager_contact'] ?? null;
$address = $data['address'] ?? null;
$total_outlets = $data['total_outlets'] ?? '0-10'; // Using a default from ENUM
$franchise_owned_outlets = $data['franchise_owned_outlets'] ?? '-- Select --';
$company_owned_outlets = $data['company_owned_outlets'] ?? '-- Select --';
$marketing_materials_available = $data['marketing_materials_provided'] ?? 'No';
$is_term_renewable = $data['is_agreement_renewable'] ?? 'No';
$has_operating_manuals = $data['operations_manual_provided'] ?? 'No';
$field_assistance_available = $data['field_assistance_available'] ?? 'No';
$head_office_assistance = $data['head_office_assistance'] ?? 'No';
$it_systems_included = $data['it_systems_included'] ?? 'No';
$franchise_years = $data["franchise_years"] ?? '-- Select --';
$cat_id = (int)($data['cat_id']);
$sub_cat_id = (int)($data['sub_cat_id']);
$franchise_fee = $data["franchise_fee"] ?? 0;
$modal_id = $data["franchise_model"] ?? null;
$commenced_operations = $data["commenced_operations_year"] ?? null;
$expansion_start = $data["expansion_started_year"] ?? null;

// --- 5. Insert/Update Data in Related Tables ---

// brand_expansion table
if ($register_id !== null) {
    $state_ids = $data["expansion_state_ids"] ?? null;
    $city_ids = $data["expansion_city_ids"] ?? null;
    $expansion_sql = "INSERT INTO brand_expansion(register_id, state_id, city_id) 
                      VALUES(?, ?, ?)
                      ON DUPLICATE KEY UPDATE 
                      state_id = VALUES(state_id), city_id = VALUES(city_id)";

    $expansion_stmt = $conn->prepare($expansion_sql);
    if ($expansion_stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error preparing statement for brand expansion: ' . $conn->error]);
        exit();
    }
    $expansion_stmt->bind_param("iss", $register_id, $state_ids, $city_ids);
    if (!$expansion_stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error executing statement for brand expansion: ' . $expansion_stmt->error]);
        exit();
    }
    $expansion_stmt->close();
}

// single_unit_details table
if (!empty($data["single_required_area"]) && !empty($data["single_investment_range"]) && !empty($data["single_expected_payback_period"]) && !empty($data["single_expected_roi"])) {
    $single_unit_sql = "INSERT INTO single_unit_details(register_id, area_req, investment, payback, roi) 
                        VALUES(?,?,?,?,?) 
                        ON DUPLICATE KEY UPDATE 
                        area_req = VALUES(area_req), 
                        investment = VALUES(investment), 
                        payback = VALUES(payback), 
                        roi = VALUES(roi)";

    $single_unit_stmt = $conn->prepare($single_unit_sql);
    $single_unit_stmt->bind_param(
        "issss",
        $register_id,
        $data["single_required_area"],
        $data["single_investment_range"],
        $data["single_expected_payback_period"],
        $data["single_expected_roi"]
    );
    if (!$single_unit_stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error executing statement for single unit details: ' . $single_unit_stmt->error]);
        exit();
    }
    $single_unit_stmt->close();
}

// master_unit_details table (country-wise)
if (!empty($data["master_required_area_country"]) && !empty($data["master_investment_range_country"]) && !empty($data["master_expected_payback_period_country"]) && isset($data["master_expected_roi_country"])) {
    $master_sql = "INSERT INTO `master_unit_details` (`register_id`, `area_req`, `investment`, `roi`, `payback`, `type`) 
                   VALUES (?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE
                   area_req = VALUES(area_req),
                   investment = VALUES(investment),
                   roi = VALUES(roi),
                   payback = VALUES(payback),
                   type = VALUES(type)";

    $master_stmt = $conn->prepare($master_sql);
    if (!$master_stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare statement failed for master unit details (country): ' . $conn->error]);
        exit();
    }
    $master_type = 'country_wise';
    $master_stmt->bind_param(
        "isssis",
        $register_id,
        $data["master_required_area_country"],
        $data["master_investment_range_country"],
        $data["master_expected_roi_country"],
        $data["master_expected_payback_period_country"],
        $master_type
    );
    if (!$master_stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error executing statement for master unit details (country): ' . $master_stmt->error]);
        exit();
    }
    $master_stmt->close();
}

// master_unit_details table (state-wise)
if (!empty($data["master_required_area_state"]) && !empty($data["master_investment_range_state"]) && !empty($data["master_expected_payback_period_state"]) && isset($data["master_expected_roi_state"])) {
    $master_sql = "INSERT INTO `master_unit_details` (`register_id`, `area_req`, `investment`, `roi`, `payback`, `type`) 
                   VALUES (?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE
                   area_req = VALUES(area_req),
                   investment = VALUES(investment),
                   roi = VALUES(roi),
                   payback = VALUES(payback),
                   type = VALUES(type)";

    $master_stmt = $conn->prepare($master_sql);
    if (!$master_stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare statement failed for master unit details (state): ' . $conn->error]);
        exit();
    }
    $master_type = 'state_wise';
    $master_stmt->bind_param(
        "isssis",
        $register_id,
        $data["master_required_area_state"],
        $data["master_investment_range_state"],
        $data["master_expected_roi_state"],
        $data["master_expected_payback_period_state"],
        $master_type
    );
    if (!$master_stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error executing statement for master unit details (state): ' . $master_stmt->error]);
        exit();
    }
    $master_stmt->close();
}

// master_unit_details table (city-wise)
if (!empty($data["master_required_area_city"]) && !empty($data["master_investment_range_city"]) && !empty($data["master_expected_payback_period_city"]) && isset($data["master_expected_roi_city"])) {
    $master_sql = "INSERT INTO `master_unit_details` (`register_id`, `area_req`, `investment`, `roi`, `payback`, `type`) 
                   VALUES (?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE
                   area_req = VALUES(area_req),
                   investment = VALUES(investment),
                   roi = VALUES(roi),
                   payback = VALUES(payback),
                   type = VALUES(type)";

    $master_stmt = $conn->prepare($master_sql);
    if (!$master_stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare statement failed for master unit details (city): ' . $conn->error]);
        exit();
    }
    $master_type = 'city_wise';
    $master_stmt->bind_param(
        "isssis",
        $register_id,
        $data["master_required_area_city"],
        $data["master_investment_range_city"],
        $data["master_expected_roi_city"],
        $data["master_expected_payback_period_city"],
        $master_type
    );
    if (!$master_stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error executing statement for master unit details (city): ' . $master_stmt->error]);
        exit();
    }
    $master_stmt->close();
}

// brand_plan_map table
if ($register_id !== null) {
    $plan_sql = "INSERT INTO brand_plan_map(register_id, plan_category_id) 
                 VALUES(?, ?)
                 ON DUPLICATE KEY UPDATE 
                 register_id = VALUES(register_id)";
    $plan_category = 1;

    $plan_stmt = $conn->prepare($plan_sql);
    if ($plan_stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error preparing statement for brand plan: ' . $conn->error]);
        exit();
    }
    $plan_stmt->bind_param("ii", $register_id, $plan_category);
    if (!$plan_stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error executing statement for brand plan: ' . $plan_stmt->error]);
        exit();
    }
    $plan_stmt->close();
}

// --- 6. Main Brands Table Insert/Update ---
$sql = "INSERT INTO brands (
    register_id, description, company_name, brand_email, phone, bd_manager_name, 
    bd_manager_email, bd_manager_contact, address, total_outlets, franchise_owned_outlets, 
    company_owned_outlets, marketing_materials_available, is_term_renewable, 
    has_operating_manuals, field_assistance_available, head_office_assistance, 
    it_systems_included, franchise_years, modal_id,cat_id,sub_cat_id,franchise_fee,commenced_operations,expansion_start
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    company_name = VALUES(company_name),
    brand_email = VALUES(brand_email),
    phone = VALUES(phone),
    bd_manager_name = VALUES(bd_manager_name),
    bd_manager_email = VALUES(bd_manager_email),
    bd_manager_contact = VALUES(bd_manager_contact),
    address = VALUES(address),
    total_outlets = VALUES(total_outlets),
    franchise_owned_outlets = VALUES(franchise_owned_outlets),
    company_owned_outlets = VALUES(company_owned_outlets),
    marketing_materials_available = VALUES(marketing_materials_available),
    is_term_renewable = VALUES(is_term_renewable),
    has_operating_manuals = VALUES(has_operating_manuals),
    field_assistance_available = VALUES(field_assistance_available),
    head_office_assistance = VALUES(head_office_assistance),
    it_systems_included = VALUES(it_systems_included),
    franchise_years = VALUES(franchise_years),
    modal_id = VALUES(modal_id),
    cat_id=VALUES(cat_id),
    sub_cat_id=VALUES(sub_cat_id),
    franchise_fee=VALUES(franchise_fee),
    commenced_operations=VALUES(commenced_operations),
    expansion_start=VALUES(expansion_start)";


$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error preparing main brands statement: ' . $conn->error]);
    exit();
}

// Corrected bind_param call
$stmt->bind_param(
    "issssssssssssssssssiiisss",
    $register_id,
    $description,
    $company_name,
    $brand_email,
    $phone,
    $bd_manager_name,
    $bd_manager_email,
    $bd_manager_contact,
    $address,
    $total_outlets,
    $franchise_owned_outlets,
    $company_owned_outlets,
    $marketing_materials_available,
    $is_term_renewable,
    $has_operating_manuals,
    $field_assistance_available,
    $head_office_assistance,
    $it_systems_included,
    $franchise_years,
    $modal_id,
    $cat_id,
    $sub_cat_id, // Correct variable name
    $franchise_fee,
    $commenced_operations,
    $expansion_start
);

// --- 7. Execute and Respond ---
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Brands data inserted/updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error executing main brands statement: ' . $stmt->error]);
}

// --- 8. Close the Connections ---
$stmt->close();
$conn->close();


if ($register_id !== null) {
    // The "ON DUPLICATE KEY UPDATE" clause here handles your request.
    // It will insert a new record if the register_id doesn't exist,
    // otherwise it will update the state_id and city_id for that existing record.
    $plan_sql = "INSERT INTO brand_plan_map(register_id, plan_category_id) VALUES(?, ?)
                      ON DUPLICATE KEY UPDATE register_id = VALUES(register_id)";
    $plan_category = 1;

    $plan_stmt = $conn->prepare($plan_sql);
    if ($plan_stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error preparing statement for brand plan : ' . $conn->error]);
        exit();
    }

    // Bind parameters for the prepared statement
    $plan_stmt->bind_param("ii", $register_id, $plan_category);

    // Execute the statement
    $plan_stmt->execute();
    $plan_stmt->close();
}
if ($stmt->execute()) {
    // Optionally, you can add success messages here
    echo json_encode(['status' => 'success', 'message' => 'Brands data inserted/updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error executing statement: ' . $stmt->error]);
}

$stmt->close();


// --- 6. Execute and Respond ---
// if ($stmt->execute()) {
//     echo json_encode(['status' => 'success', 'message' => 'New record created successfully.']);
// } else {
//     echo json_encode(['status' => 'error', 'message' => 'Error executing statement: ' . $stmt->error]);
// }

// --- 7. Close the Connections ---
// $stmt->close();
$conn->close();
