<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

// Helper function to validate inputs
function is_valid_email($email) {
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_valid_phone($phone) {
  return preg_match('/^[6-9][0-9]{9}$/', $phone);
}

function is_valid_name($name) {
  return preg_match('/^[a-zA-Z\s]+$/', $name);
}

// Required field check
if (
  !isset($data['full_name'], $data['phone'], $data['email'], $data['state_id'], $data['city_id']) ||
  empty(trim($data['full_name'])) ||
  empty(trim($data['phone'])) ||
  empty(trim($data['email'])) ||
  empty($data['state_id']) ||
  empty($data['city_id'])
) {
  echo json_encode(["success" => false, "message" => "Please fill all required fields"]);
  exit;
}

// Sanitize & validate input
$full_name = trim($data['full_name']);
$phone = trim($data['phone']);
$email = trim($data['email']);
$message = isset($data['message']) ? trim($data['message']) : '';
$state_id = intval($data['state_id']);
$city_id = intval($data['city_id']);

if (!is_valid_name($full_name)) {
  echo json_encode(["success" => false, "message" => "Name must only contain letters and spaces"]);
  exit;
}

if (!is_valid_phone($phone)) {
  echo json_encode(["success" => false, "message" => "Phone must start with 6/7/8/9 and be 10 digits"]);
  exit;
}

if (!is_valid_email($email)) {
  echo json_encode(["success" => false, "message" => "Invalid email format"]);
  exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM enquiries WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "Email already exists"]);
  $stmt->close();
  $conn->close();
  exit;
}
$stmt->close();

// Check if phone exists
$stmt = $conn->prepare("SELECT id FROM enquiries WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "Phone number already exists"]);
  $stmt->close();
  $conn->close();
  exit;
}
$stmt->close();

// Insert data safely using prepared statement
$stmt = $conn->prepare("INSERT INTO enquiries (full_name, phone, email, message, state_id, city_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssii", $full_name, $phone, $email, $message, $state_id, $city_id);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Enquiry submitted successfully!"]);
} else {
  echo json_encode(["success" => false, "message" => "Failed to save enquiry"]);
}

$stmt->close();
$conn->close();
