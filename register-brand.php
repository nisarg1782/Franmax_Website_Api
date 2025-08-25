<?php
// ✅ CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// ✅ Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "db.php";

// ✅ Read JSON data
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Log for debugging (optional)
file_put_contents("debug.log", print_r($data, true));

// ✅ Input validation
if (
    isset($data['brand_name']) &&
    isset($data['brand_email']) &&
    isset($data['brand_mobile']) &&
    isset($data['brand_password']) &&
    isset($data['state_id']) &&
    isset($data['city_id']) &&
    isset($data['category_id']) &&
    isset($data['user_type']) &&
    isset($data["user_name"])
) {
    $name = trim($data['brand_name']);
    $email = trim($data['brand_email']);
    $mobile = trim($data['brand_mobile']);
    $password = password_hash(trim($data['brand_password']), PASSWORD_BCRYPT);
    $state_id = (int)$data['state_id'];
    $city_id = (int)$data['city_id'];
    $category_id = (int)$data['category_id']; // Assuming this is sector_id
    $status = "active"; // Default status
    $user_type = trim($data['user_type']);
    $mode = "online"; // Default mode
    $user_name = trim($data["user_name"]);

    // ✅ Check for duplicate email or mobile in the correct table
    $check = $conn->prepare("SELECT id FROM registred_user WHERE user_name=?");
    $check->bind_param("s", $user_name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "UserName already exists."]);
        $check->close();
        $conn->close();
        exit();
    }
    $check->close();

    // ✅ Insert into the correct table with all fields
    $stmt = $conn->prepare("INSERT INTO registred_user (name,user_name,email, mobile, password, state_id, city_id, mas_cat_id, status, user_type, mode) VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        $conn->close();
        exit();
    }

    // ✅ Fix: Changed bind_param types to match the variables
    $stmt->bind_param("sssssiiisss", $name, $user_name, $email, $mobile, $password, $state_id, $city_id, $category_id, $status, $user_type, $mode);

    if ($stmt->execute()) {
        // Updated success message to be more generic
        echo json_encode(["success" => true, "message" => "User registered successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
}

$conn->close();
