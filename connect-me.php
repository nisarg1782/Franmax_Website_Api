<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include DB connection
include_once "db.php"; // must define $conn = new mysqli(...)

// Read JSON payload
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Fallback for form-encoded data
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

if (!$data || !is_array($data)) {
    echo json_encode(["success" => false, "error" => "Invalid request data"]);
    exit;
}

// Extract and sanitize
$name    = trim($data["name"] ?? "");
$email   = trim($data["email"] ?? "");
$contact = trim($data["contact"] ?? "");
$stateId = intval($data["stateId"] ?? 0);
$cityId  = intval($data["cityId"] ?? 0);
$message = trim($data["message"] ?? "");
$brandId = intval($data["brandId"] ?? 0);

// Validation
$errors = [];
if ($name === "") $errors[] = "Name is required";
if ($email === "") $errors[] = "Email is required";
if ($contact === "") $errors[] = "Contact is required";
if ($stateId <= 0) $errors[] = "State is required";
if ($cityId <= 0) $errors[] = "City is required";
if ($message === "") $errors[] = "Message is required";

if (!empty($errors)) {
    echo json_encode(["success" => false, "error" => implode(", ", $errors)]);
    exit;
}

// Prepare SQL insert
$stmt = $conn->prepare("
    INSERT INTO investor_inquiries 
        (name, email, contact, state_id, city_id, message, brand_id, inquiry_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]);
    exit;
}

// Correct bind types: sss i i s i  → "sssiisi"
$stmt->bind_param(
    "sssiisi",
    $name,
    $email,
    $contact,
    $stateId,
    $cityId,
    $message,
    $brandId
);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
