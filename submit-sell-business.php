<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Only POST method allowed"]);
    exit;
}

// DB connection
include "db.php";

// Helper to get POST
function getPost($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

// Generate UUID
function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Sanitize input
$businessName   = getPost('businessName');
$fullName       = getPost('name');
$email          = filter_var(getPost('email'), FILTER_VALIDATE_EMAIL);
$contact        = getPost('contact');
$stateId        = (int)getPost('stateId');
$cityId         = (int)getPost('cityId');
$sectorId       = (int)getPost('sectorId');
$expectedAmount = isset($_POST['expectedAmount']) ? (float)$_POST['expectedAmount'] : 0;
$address        = getPost('address');
$description    = getPost('description');

// Validation
$requiredFields = [$businessName, $fullName, $email, $contact, $stateId, $cityId, $sectorId, $address];
foreach ($requiredFields as $field) {
    if ($field === '' || $field === null) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "All required fields must be filled"]);
        exit;
    }
}
if (!$email) {
    echo json_encode(["success" => false, "message" => "Invalid email"]);
    exit;
}
if (!preg_match("/^[6-9]\d{9}$/", $contact)) {
    echo json_encode(["success" => false, "message" => "Invalid contact number"]);
    exit;
}
if (!preg_match("/^[a-zA-Z\s]+$/", $fullName)) {
    echo json_encode(["success" => false, "message" => "Full name contains invalid characters"]);
    exit;
}

// Check duplicate
$checkStmt = $conn->prepare("SELECT id FROM sell_business_requests WHERE business_name = ? AND sector_id = ?");
$checkStmt->bind_param("si", $businessName, $sectorId);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Business already submitted in this sector"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Handle image upload
$imageName = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmpPath = $_FILES['image']['tmp_name'];
    $fileName = basename($_FILES['image']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(["success" => false, "message" => "Only JPG, PNG, WEBP allowed"]);
        exit;
    }

    $newFileName = uniqid("img_", true) . '.' . $ext;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        echo json_encode(["success" => false, "message" => "Image upload failed"]);
        exit;
    }

    $imageName = $newFileName;
}

// Generate UUID for new business
$uuid = generateUuid();

// Insert data
$stmt = $conn->prepare("
    INSERT INTO sell_business_requests (
        uuid, business_name, full_name, email, contact,
        state_id, city_id, sector_id, expected_amount,
        full_address, description, image
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssiiidssss",
    $uuid,
    $businessName,
    $fullName,
    $email,
    $contact,
    $stateId,
    $cityId,
    $sectorId,
    $expectedAmount,
    $address,
    $description,
    $imageName
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Business submitted", "uuid" => $uuid]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
