<?php

// --- 1. API Headers ---
// This allows cross-origin requests and specifies the response format as JSON.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// --- 2. Main Try...Catch Block for Global Error Handling ---
try {
    // --- 3. Database Connection Details ---
    // The 'db.php' file should contain your database connection logic.
    // For example:
    // $conn = new mysqli("localhost", "your_username", "your_password", "your_database");
    // It's recommended to handle the connection within the try block.
    include "db.php";

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // --- 4. Get and Decode the Incoming JSON Data ---
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);

    // Check if data was received and decoded correctly
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        throw new Exception('Invalid JSON received or no data provided.');
    }

    // --- 5. Map Data to Table Columns ---
    $register_id = $data['id'] ?? null;
    $description = $data['description'] ?? null;
    $company_name = $data['name'] ?? null;
    $brand_email = $data['email'] ?? null;
    $phone = $data['mobile'] ?? null;
    $bd_manager_name = $data['bd_manager_name'] ?? null;
    $bd_manager_email = $data['bd_manager_email'] ?? null;
    $bd_manager_contact = $data['bd_manager_contact'] ?? null;
    $address = $data['address'] ?? null;
    $total_outlets = $data['total_outlets'] ?? '0-10';
    $franchise_owned_outlets = $data['franchise_owned_outlets'] ?? '-- Select --';
    $company_owned_outlets = $data['company_owned_outlets'] ?? '-- Select --';
    $marketing_materials_available = $data['marketing_materials_provided'] ?? 'No';
    $is_term_renewable = $data['is_agreement_renewable'] ?? 'No';
    $has_operating_manuals = $data['operations_manual_provided'] ?? 'No';
    $field_assistance_available = $data['field_assistance_available'] ?? 'No';
    $head_office_assistance = $data['head_office_assistance'] ?? 'No';
    $it_systems_included = $data['it_systems_included'] ?? 'No';
    $franchise_years = $data["franchise_years"] ?? '-- Select --';
    $cat_id = (int)($data['cat_id'] ?? 0);
    $sub_cat_id = (int)($data['sub_cat_id'] ?? 0);
    $franchise_fee = $data["franchise_fee"] ?? 0;
    $modal_id = $data["franchise_model"] ?? null;
    $commenced_operations = $data["commenced_operations_year"] ?? null;
    $expansion_start = $data["expansion_started_year"] ?? null;

    // --- 6. Insert/Update Data in Related Tables ---

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
            throw new Exception('Error preparing statement for brand expansion: ' . $conn->error);
        }
        $expansion_stmt->bind_param("iss", $register_id, $state_ids, $city_ids);
        if (!$expansion_stmt->execute()) {
            throw new Exception('Error executing statement for brand expansion: ' . $expansion_stmt->error);
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
        if ($single_unit_stmt === false) {
            throw new Exception('Error preparing statement for single unit details: ' . $conn->error);
        }
        $single_unit_stmt->bind_param(
            "issss",
            $register_id,
            $data["single_required_area"],
            $data["single_investment_range"],
            $data["single_expected_payback_period"],
            $data["single_expected_roi"]
        );
        if (!$single_unit_stmt->execute()) {
            throw new Exception('Error executing statement for single unit details: ' . $single_unit_stmt->error);
        }
        $single_unit_stmt->close();
    }

    // Master unit details for country, state, and city
    $master_types = ['country' => 'country_wise', 'state' => 'state_wise', 'city' => 'city_wise'];
    foreach ($master_types as $prefix => $type) {
        if (!empty($data["master_required_area_{$prefix}"]) && !empty($data["master_investment_range_{$prefix}"]) && !empty($data["master_expected_payback_period_{$prefix}"]) && isset($data["master_expected_roi_{$prefix}"])) {
            $master_sql = "INSERT INTO `master_unit_details` (`register_id`, `area_req`, `investment`, `roi`, `payback`, `type`) 
                           VALUES (?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE
                           area_req = VALUES(area_req),
                           investment = VALUES(investment),
                           roi = VALUES(roi),
                           payback = VALUES(payback),
                           type = VALUES(type)";

            $master_stmt = $conn->prepare($master_sql);
            if ($master_stmt === false) {
                throw new Exception("Prepare statement failed for master unit details ({$prefix}): " . $conn->error);
            }
            $master_stmt->bind_param(
                "isssis",
                $register_id,
                $data["master_required_area_{$prefix}"],
                $data["master_investment_range_{$prefix}"],
                $data["master_expected_roi_{$prefix}"],
                $data["master_expected_payback_period_{$prefix}"],
                $type
            );
            if (!$master_stmt->execute()) {
                throw new Exception("Error executing statement for master unit details ({$prefix}): " . $master_stmt->error);
            }
            $master_stmt->close();
        }
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
            throw new Exception('Error preparing statement for brand plan: ' . $conn->error);
        }
        $plan_stmt->bind_param("ii", $register_id, $plan_category);
        if (!$plan_stmt->execute()) {
            throw new Exception('Error executing statement for brand plan: ' . $plan_stmt->error);
        }
        $plan_stmt->close();
    }

    // --- 7. Main Brands Table Insert/Update ---
    $sql = "INSERT INTO brands (
        register_id, description, company_name, brand_email, phone, bd_manager_name, 
        bd_manager_email, bd_manager_contact, address, total_outlets, franchise_owned_outlets, 
        company_owned_outlets, marketing_materials_available, is_term_renewable, 
        has_operating_manuals, field_assistance_available, head_office_assistance, 
        it_systems_included, franchise_years, modal_id, cat_id, sub_cat_id, franchise_fee, commenced_operations, expansion_start
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        cat_id = VALUES(cat_id),
        sub_cat_id = VALUES(sub_cat_id),
        franchise_fee = VALUES(franchise_fee),
        commenced_operations = VALUES(commenced_operations),
        expansion_start = VALUES(expansion_start)";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error preparing main brands statement: ' . $conn->error);
    }

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
        $sub_cat_id,
        $franchise_fee,
        $commenced_operations,
        $expansion_start
    );

    // --- 8. Execute and Respond ---
    if (!$stmt->execute()) {
        throw new Exception('Error executing main brands statement: ' . $stmt->error);
    }

    echo json_encode(['status' => 'success', 'message' => 'Brands data inserted/updated successfully']);

} catch (Exception $e) {
    // --- 9. Catch and Respond with an Error ---
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    // --- 10. Close the Connections ---
    // This `finally` block ensures the connection is closed even if an error occurs.
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>