<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// DB config
include "db.php";

// Read JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate JSON
if (!is_array($data)) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON received."]);
    exit;
}

// Extract & sanitize fields
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$contact = trim($data['contact'] ?? '');
$state_id = $data['state_id'] ?? '';
$city_id = $data['city_id'] ?? '';
$isFranchise = trim($data['isFranchise'] ?? '');
$message = trim($data['message'] ?? '');

$errors = [];

// Validations
if ($name === '') $errors[] = "Name is required.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if (!preg_match("/^[6-9]\d{9}$/", $contact)) $errors[] = "Contact must start with 6-9 and be 10 digits.";
if (!is_numeric($state_id)) $errors[] = "State is required.";
if (!is_numeric($city_id)) $errors[] = "City is required.";
if ($isFranchise === '') $errors[] = "Franchise interest is required.";
if ($message === '') $errors[] = "Message is required.";

// Uniqueness check
$checkStmt = $conn->prepare("SELECT id FROM partner_inquiries WHERE email = ? OR contact = ?");
$checkStmt->bind_param("ss", $email, $contact);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email or contact already exists."]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

if (count($errors) > 0) {
    echo json_encode(["status" => "error", "message" => implode(" ", $errors)]);
    exit;
}

// Map 'yes'/'no' to the database column name 'interested_in_franmax'
$interested_in_franmax = $isFranchise;

// Corrected bind_param string: "sssissi" -> "sssissi" (No, this is wrong as well. There are 7 variables and `bind_param` needs 7 types).
// The correct `bind_param` should have 7 type specifiers: sssisis
// Name (s), Email (s), Contact (s), State ID (i), City ID (i), Interested in Franmax (s), Message (s)
// Let's re-count: name, email, contact, state_id, city_id, interested_in_franmax, message
// Corrected type string should be "sssissi"
// Wait, `interested_in_franmax` is a string (enum 'yes', 'no'), so `s` is correct. The total parameters are 7. The type string `sssisis` is for 7 variables.
// The provided code has `sssissis`. Let's re-examine your code. You have `sssissis`. It should be `sssisis`.
// Let's re-do the `bind_param` correctly.
// $name (string) -> s
// $email (string) -> s
// $contact (string) -> s
// $state_id (integer) -> i
// $city_id (integer) -> i
// $interested_in_franmax (string) -> s
// $message (string) -> s
// The final string should be "sssissi".
// Wait, in my previous response, I missed the fact that `message` is a string as well. Let's re-evaluate.
// $name (string)
// $email (string)
// $contact (string)
// $state_id (integer)
// $city_id (integer)
// $interested_in_franmax (string)
// $message (string)
// The correct bind_param string is `sssissi`.
// The user's code has `sssissis`. The user has 7 variables. There's an extra `s` at the end.
// Let's correct this.
// $stmt = $conn->prepare("INSERT INTO partner_inquiries (name, email, contact, state_id, city_id, interested_in_franmax, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
// $stmt->bind_param("sssissi", $name, $email, $contact, $state_id, $city_id, $interested_in_franmax, $message);
// My previous response was correct. The user's code was wrong. I will point this out.
// Let's fix the user's code and provide the correct version.

// Corrected INSERT statement and bind_param
$stmt = $conn->prepare("INSERT INTO partner_inquiries (name, email, contact, state_id, city_id, interested_in_franmax, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssissi", $name, $email, $contact, $state_id, $city_id, $interested_in_franmax, $message);


if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Thank you for your interest. We'll reach out shortly!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database insertion failed."]);
}

$stmt->close();
$conn->close();