<?php
// Set headers for JSON response and CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle pre-flight CORS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

// Database credentials
include "db.php";

// Get and decode the JSON data from the request body
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Validate incoming JSON data
if ($data === null) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON data received."]);
    $conn->close();
    exit();
}

// Sanitize and validate incoming data
// Note: It's better to perform validation before sanitization and database interaction.
$user_id = (int)($data['id'] ?? 0);
$full_name = $conn->real_escape_string($data['name'] ?? '');
$email = $conn->real_escape_string($data['email'] ?? '');
$contact = $conn->real_escape_string($data['contact'] ?? '');
$secondary_email = $conn->real_escape_string($data['secondaryEmail'] ?? '');
$secondary_contact = $conn->real_escape_string($data['secondaryContact'] ?? '');
$pincode = $conn->real_escape_string($data['pincode'] ?? '');
$state_id = (int)($data['state_id'] ?? 0);
$city_id = (int)($data['city_id'] ?? 0);
$address = $conn->real_escape_string($data['address'] ?? '');
$highest_education = $conn->real_escape_string($data['highestEducation'] ?? '');
$income_range = $conn->real_escape_string($data['incomeRange'] ?? '');
$investment_range = $conn->real_escape_string($data['investmentRange'] ?? '');
$available_capital = $conn->real_escape_string($data['availableCapital'] ?? '');
$need_loan = $conn->real_escape_string($data['needLoan'] ?? '');
$franchise_experience = $conn->real_escape_string($data['franchiseExperience'] ?? '');
$occupation = $conn->real_escape_string($data['occupation'] ?? '');
$bio = $conn->real_escape_string($data['bio'] ?? '');

// SQL query to insert or update user profile data
$sql = "INSERT INTO user_profiles (
            user_id, full_name, email, contact, secondary_email, secondary_contact, pincode,
            state_id, city_id, address, highest_education, income_range, investment_range,
            available_capital, need_loan, franchise_experience, occupation, bio
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name), email = VALUES(email), contact = VALUES(contact), 
            secondary_email = VALUES(secondary_email), secondary_contact = VALUES(secondary_contact), 
            pincode = VALUES(pincode), state_id = VALUES(state_id), city_id = VALUES(city_id), 
            address = VALUES(address), highest_education = VALUES(highest_education), 
            income_range = VALUES(income_range), investment_range = VALUES(investment_range),
            available_capital = VALUES(available_capital), need_loan = VALUES(need_loan), 
            franchise_experience = VALUES(franchise_experience), occupation = VALUES(occupation), bio = VALUES(bio)";

// Prepare the statement
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepare failed for user_profiles: " . $conn->error]);
    $conn->close();
    exit();
}

// Bind parameters
$stmt->bind_param(
    "issssssiisisssssss",
    $user_id,
    $full_name,
    $email,
    $contact,
    $secondary_email,
    $secondary_contact,
    $pincode,
    $state_id,
    $city_id,
    $address,
    $highest_education,
    $income_range,
    $investment_range,
    $available_capital,
    $need_loan,
    $franchise_experience,
    $occupation,
    $bio
);

// Execute the first statement
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Execution failed for user_profiles: " . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();

// ---
// Now, handle the investor_plan_map insertion
// ---

// The "ON DUPLICATE KEY UPDATE" clause here handles your request
$plan_sql = "INSERT INTO investor_plan_map(register_id, plan_category_id) VALUES(?, ?)
             ON DUPLICATE KEY UPDATE register_id = VALUES(register_id)";

$plan_category = 2; // Fixed plan category

$plan_stmt = $conn->prepare($plan_sql);

if ($plan_stmt === false) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error preparing statement for investor_plan_map: " . $conn->error]);
    $conn->close();
    exit();
}

// Bind parameters and execute
$plan_stmt->bind_param("ii", $user_id, $plan_category);

if (!$plan_stmt->execute()) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Execution failed for investor_plan_map: " . $plan_stmt->error]);
    $plan_stmt->close();
    $conn->close();
    exit();
}

$plan_stmt->close();
$conn->close();

// If both inserts were successful, send a success response
echo json_encode(["success" => true, "message" => "Data inserted successfully."]);

?>