<?php
// Enable CORS (for development)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection
include 'db.php';

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Query active banners
$sql = "SELECT  banner_image FROM banner";
$result = $conn->query($sql);

$banners = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['image_url'] = "img/home_banner/" . $row['banner_image']; // Adjust path as needed
        $banners[] = $row;
    }
    echo json_encode($banners);
} else {
    echo json_encode([]);
}

$conn->close();
?>
