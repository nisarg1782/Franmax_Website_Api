<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// DB connection
include "db.php";
// Get and decode input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Required field check
$requiredFields = ['property_key', 'name', 'email', 'number', 'state_id', 'city_id'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize
$property_key = $conn->real_escape_string(trim($data['property_key']));
$name = $conn->real_escape_string(trim($data['name']));
$email = $conn->real_escape_string(trim($data['email']));
$number = $conn->real_escape_string(trim($data['number']));
$state_id = (int)$data['state_id'];
$city_id = (int)$data['city_id'];
$message = isset($data['message']) ? $conn->real_escape_string(trim($data['message'])) : '';

// Validate
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}
if (!preg_match('/^[6-9]\d{9}$/', $number)) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number']);
    exit;
}

// Check for duplicates
$checkQuery = $conn->prepare("
    SELECT id FROM leasing_enquiries 
    WHERE property_key = ? AND (email = ? OR number = ?)
");
$checkQuery->bind_param("sss", $property_key, $email, $number);
$checkQuery->execute();
$checkQuery->store_result();

if ($checkQuery->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'You have already submitted an enquiry for this property']);
    $checkQuery->close();
    $conn->close();
    exit;
}
$checkQuery->close();

// Insert
$stmt = $conn->prepare("
    INSERT INTO leasing_enquiries (property_key, name, email, number, state_id, city_id, message)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssssiis", $property_key, $name, $email, $number, $state_id, $city_id, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit enquiry']);
}

$stmt->close();
$conn->close();
?>
