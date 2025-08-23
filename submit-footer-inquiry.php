<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");

// DB Connection
include "db.php";

// Get input
$data = json_decode(file_get_contents("php://input"), true);

// Sanitize input
$inquiry_type = trim($conn->real_escape_string($data['inquiry_type'] ?? ''));
$name = trim($conn->real_escape_string($data['name'] ?? ''));
$email = trim($conn->real_escape_string($data['email'] ?? ''));
$contact = trim($conn->real_escape_string($data['contact'] ?? ''));
$state_id = (int)($data['state_id'] ?? 0);
$city_id = (int)($data['city_id'] ?? 0);
$message = trim($conn->real_escape_string($data['message'] ?? ''));

// Server-side validation
$errors = [];

if (empty($name) || !preg_match("/^[a-zA-Z\s]+$/", $name)) {
    $errors[] = "Name must contain only letters and spaces.";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address.";
}
if (!preg_match("/^[6-9]\d{9}$/", $contact)) {
    $errors[] = "Invalid phone number.";
}
if (empty($inquiry_type)) {
    $errors[] = "Inquiry type is required.";
}
if ($state_id <= 0 || $city_id <= 0) {
    $errors[] = "Valid state and city are required.";
}

if (!empty($errors)) {
    echo json_encode(["success" => false, "error" => implode(" ", $errors)]);
    exit;
}

// Check for duplicate (email + contact + inquiry_type)
$checkQuery = "SELECT id FROM footer_inquiries 
               WHERE email = '$email' AND contact = '$contact' AND inquiry_type = '$inquiry_type' LIMIT 1";

$result = $conn->query($checkQuery);
if ($result && $result->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "error" => "You have already submitted an inquiry for this type."
    ]);
    exit;
}

// Insert new inquiry
$insert = "INSERT INTO footer_inquiries 
           (inquiry_type, name, email, contact, state_id, city_id, message, created_at)
           VALUES 
           ('$inquiry_type', '$name', '$email', '$contact', $state_id, $city_id, '$message', NOW())";

if ($conn->query($insert)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$conn->close();
