<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");

// Connect to DB
include "db.php";

// Get incoming JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Sanitize & extract
$name        = trim($data['name'] ?? '');
$email       = trim($data['email'] ?? '');
$number      = trim($data['number'] ?? '');
$state_id    = (int)($data['state_id'] ?? 0);
$city_id     = (int)($data['city_id'] ?? 0);
$message     = trim($data['message'] ?? '');
$business_id = trim($data['business_id'] ?? '');

// === VALIDATION SECTION ===

// Validate name (non-empty, letters/spaces only)
if ($name === '' || !preg_match('/^[a-zA-Z\s]+$/', $name)) {
  echo json_encode(["success" => false, "error" => "Name is invalid. Only letters and spaces allowed."]);
  exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(["success" => false, "error" => "Invalid email address."]);
  exit;
}

// Validate phone number
if (!preg_match('/^[6-9]\d{9}$/', $number)) {
  echo json_encode(["success" => false, "error" => "Invalid phone number. Must start with 6-9 and be 10 digits."]);
  exit;
}

// Validate state and city
if ($state_id <= 0 || $city_id <= 0) {
  echo json_encode(["success" => false, "error" => "State and City must be selected."]);
  exit;
}

// Validate business ID
if ($business_id === '') {
  echo json_encode(["success" => false, "error" => "Business ID is required."]);
  exit;
}

// Check for duplicate enquiry (same email + business)
$checkStmt = $conn->prepare("SELECT id FROM business_enquiries WHERE email = ? AND business_id = ? LIMIT 1");
$checkStmt->bind_param("ss", $email, $business_id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
  echo json_encode([
    "success" => false,
    "error" => "You have already submitted an enquiry for this business."
  ]);
  $checkStmt->close();
  $conn->close();
  exit;
}
$checkStmt->close();

// Escape strings for safe insertion
$name        = $conn->real_escape_string($name);
$email       = $conn->real_escape_string($email);
$number      = $conn->real_escape_string($number);
$message     = $conn->real_escape_string($message);
$business_id = $conn->real_escape_string($business_id);

// Insert new enquiry
$insertQuery = "
  INSERT INTO business_enquiries 
  (business_id, name, email, number, state_id, city_id, message, created_at)
  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
";

$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param(
  "ssssiis",
  $business_id,
  $name,
  $email,
  $number,
  $state_id,
  $city_id,
  $message
);

if ($insertStmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "error" => "Failed to save enquiry. Try again."]);
}

$insertStmt->close();
$conn->close();
