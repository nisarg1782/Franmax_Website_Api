<?php
// Set headers for CORS and content type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Database connection details
include "db.php";

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Method not allowed. This API only accepts GET requests."]);
    exit();
}

// Prepare and execute the query to fetch all investors
$stmt = $conn->prepare("SELECT * FROM investor_registration ORDER BY id DESC");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepare statement failed: " . $conn->error]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$investors = [];
if ($result->num_rows > 0) {
    // Fetch all rows and store them in an array
    while ($row = $result->fetch_assoc()) {
        $investors[] = $row;
    }
    
    // Send a success response with the data
    http_response_code(200);
    echo json_encode(["success" => true, "data" => $investors]);
} else {
    // No investors found
    http_response_code(200);
    echo json_encode(["success" => true, "data" => [], "message" => "No investors found."]);
}

$stmt->close();
$conn->close();

?>
