<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// MySQLi connection
include "db.php";

$response = [
    'status' => 'error',
    'message' => 'Something went wrong',
    'data' => null
];

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['message'] = 'Invalid property ID';
    echo json_encode($response);
    exit;
}

$propertyKey = $_GET['id'];

// Prepared statement with MySQLi
$stmt = $conn->prepare("
    SELECT 
        lp.property_key,
        lp.image_path,
        lp.expected_rent,
        lp.sqft,
        lp.property_type,
        lp.floor_type,
        lp.message,
        c.name AS city_name,
        s.name AS state_name
    FROM lease_properties lp
    JOIN cities c ON lp.city_id = c.id
    JOIN states s ON c.state_id = s.id
    WHERE lp.property_key = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("s", $propertyKey); // assuming property_key is a string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $property = $result->fetch_assoc();
        $response['status'] = 'success';
        $response['message'] = 'Property found';
        $response['data'] = $property;
    } else {
        $response['message'] = 'Property not found';
    }

    $stmt->close();
} else {
    $response['message'] = 'Failed to prepare statement.';
}

$conn->close();

echo json_encode($response);
