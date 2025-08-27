<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ownerName = $_POST['ownerName'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $stateId = (int) ($_POST['stateId'] ?? 0);
    $cityId = (int) ($_POST['cityId'] ?? 0);
    $address = $_POST['address'] ?? '';
    $message = $_POST['message'] ?? '';
    $expectedRent = $_POST['expectedRent'] ?? '';
    $sqft = $_POST['sqft'] ?? '';
    $propertyType = $_POST['propertyType'] ?? '';
    $floorType = $_POST['floorType'] ?? '';
    $imagePath = '';

    // Generate a unique key
    $propertyKey = md5(strtolower(trim($ownerName . $contact . $email . $stateId . $cityId . $address)));

    // Check for duplicate
    $stmt = $conn->prepare("SELECT id FROM lease_properties WHERE property_key = ?");
    $stmt->bind_param("s", $propertyKey);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'exists', 'message' => 'Property already submitted.']);
        exit;
    }
    $stmt->close();

    // Handle image upload
    if (isset($_FILES['image'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = $fileName;
        }
    }

    // Insert record
    $stmt = $conn->prepare("INSERT INTO lease_properties 
        (owner_name, contact, email, state_id, city_id, address, message, expected_rent, sqft, property_type, floor_type, image_path, property_key) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssiissssssss", 
        $ownerName, $contact, $email, $stateId, $cityId, $address, $message, 
        $expectedRent, $sqft, $propertyType, $floorType, $imagePath, $propertyKey
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Property submitted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error saving data.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'invalid', 'message' => 'Invalid request method.']);
}
$conn->close();
?>
