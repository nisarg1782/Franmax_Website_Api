<?php
// This script is an API endpoint for investor registration.
// It receives a JSON payload, saves the data to the database,
// and sends a welcome email with an attachment.

// --- CORS Handling ---
// IMPORTANT: For production, replace 'http://localhost:3000' with your actual domain.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set the response content type to JSON
header('Content-Type: application/json');

// --- Ensure JSON Body ---
$rawInput = file_get_contents("php://input");

if (!$rawInput) {
    echo json_encode([
        "success" => false,
        "message" => "Empty request body",
        "raw_input" => ""
    ]);
    exit;
}

$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON",
        "raw_input" => $rawInput
    ]);
    exit;
}

// --- DB Connection ---
include "db.php";

// Sanitize and prepare data for insertion
$name       = trim($data['investor_name']);
$user_name = trim($data['user_name']);
$mobile     = trim($data['investor_mobile']);
$email      = trim($data['investor_email']);
$password   = password_hash($data['investor_password'], PASSWORD_BCRYPT);
$state_id   = (int)$data['state_id'];
$city_id    = (int)$data['city_id'];
$sector_id  = (int)$data['sector_id']; // New field from the React component
$user_type  = trim($data['user_type']); // New field from the React component
$status     = "active";
$mode       = "online";

// --- Check if Email or Mobile already exists ---
$check = $conn->prepare("SELECT id FROM registred_user WHERE user_name = ?");
$check->bind_param("s", $user_name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "User Name already exists"
    ]);
    $check->close();
    $conn->close();
    exit;
}

$check->close();

// --- Insert into DB ---
$stmt = $conn->prepare("INSERT INTO registred_user (name, user_name, mobile, email, password, state_id, city_id, mas_cat_id, status, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssiiiss", $name, $user_name, $mobile, $email, $password, $state_id, $city_id, $sector_id, $status, $user_type);

if ($stmt->execute()) {
    // --- IMPORTANT: Send the successful registration response immediately ---
    // The frontend can now display a success message (like a toast).
    echo json_encode([
        "success" => true,
        "message" => "Registered successfully"
    ]);

    // The browser will receive the JSON response, but the PHP script will continue to run.

    // This is the correct way to include the file containing the function.
    // The previous file name was slightly different.
    // require_once 'send-registration-mail.php';

    // $filesToAttach = ['dm.pdf']; // Specify the file(s) to attach
    // sendRegistrationEmail($email, $name, $filesToAttach);

} else {
    // If the database insert fails, send an error message
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>