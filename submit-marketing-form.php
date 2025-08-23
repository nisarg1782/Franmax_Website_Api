<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// DB config
include "db.php";

// Read JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);
if (!is_array($data)) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON received."]);
    exit;
}

// Validation
$errors = [];
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$brand_name = trim($data['brand_name'] ?? '');
$contact = trim($data['contact'] ?? '');
$state_id = $data['state_id'] ?? '';
$city_id = $data['city_id'] ?? '';
$services = $data['services'] ?? [];

if ($name === '' || preg_match('/^\s*$/', $name)) $errors[] = "Name is required and cannot be only spaces.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if ($brand_name === '' || preg_match('/^\s*$/', $brand_name)) $errors[] = "Brand name is required and cannot be only spaces.";
if (!preg_match("/^[6-9][0-9]{9}$/", $contact)) $errors[] = "Contact must start with 6, 7, 8, or 9 and be 10 digits.";
if (!is_numeric($state_id)) $errors[] = "Valid state is required.";
if (!is_numeric($city_id)) $errors[] = "Valid city is required.";

// Check uniqueness
$check = $conn->prepare("SELECT id FROM marketing_inquiries WHERE email = ? OR contact = ? OR brand_name = ?");
$check->bind_param("sss", $email, $contact, $brand_name);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $errors[] = "Email, Contact, or Brand Name already exists.";
}
$check->close();

if (count($errors)) {
    echo json_encode(["status" => "error", "message" => "Validation failed", "errors" => $errors]);
    exit;
}

// Insert
$stmt = $conn->prepare("INSERT INTO marketing_inquiries (name, email, state_id, city_id, brand_name, contact, services) VALUES (?, ?, ?, ?, ?, ?, ?)");
$services_str = implode(",", $services);
$stmt->bind_param("ssiisss", $name, $email, $state_id, $city_id, $brand_name, $contact, $services_str);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Form submitted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Insert failed."]);
}

$stmt->close();
$conn->close();
