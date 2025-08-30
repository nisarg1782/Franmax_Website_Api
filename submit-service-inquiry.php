<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// DB config
include "db.php";

// Read JSON input
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if ($data === null) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON received."]);
    exit;
}

// Validation
$errors = [];
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$contact = trim($data['contact'] ?? '');
$state_id = $data['state_id'] ?? '';
$city_id = $data['city_id'] ?? '';
$service_type = $data['service_type'] ?? '';

if ($name === '') {
    $errors[] = "Name is required.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required.";
}
if (!preg_match("/^[6-9][0-9]{9}$/", $contact)) {
    $errors[] = "Contact must start with 6, 7, 8, or 9 and be 10 digits.";
}
if (!is_numeric($state_id) || $state_id <= 0) {
    $errors[] = "Valid state is required.";
}
if (!is_numeric($city_id) || $city_id <= 0) {
    $errors[] = "Valid city is required.";
}
if ($service_type === '') {
    $errors[] = "Service type is required.";
}

if (count($errors) > 0) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "Validation failed", "errors" => $errors]);
    exit;
}

// Check for uniqueness of email and contact
$check = $conn->prepare("SELECT id FROM service_inquiry WHERE email = ? OR contact = ?");
$check->bind_param("ss", $email, $contact);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    http_response_code(409); // Conflict
    $errors[] = "Email or Contact already exists.";
    echo json_encode(["status" => "error", "message" => "Validation failed", "errors" => $errors]);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Get current date for inquiry_date
$inquiry_date = date('Y-m-d H:i:s');

// Insert data into service_inquiry table
$stmt = $conn->prepare("INSERT INTO service_inquiry (name, contact, email, state_id, city_id, service_type, inquiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("sssiiss", $name, $contact, $email, $state_id, $city_id, $service_type, $inquiry_date);

if ($stmt->execute()) {
    http_response_code(200); // Created
    echo json_encode(["status" => "success", "message" => "Form submitted successfully."]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["status" => "error", "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>