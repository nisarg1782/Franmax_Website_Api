<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET"); // Use GET method for fetching data
header("Content-Type: application/json");

// Define database connection details

// $base_url = 'http://localhost/react-api/uploads/';

// Database connection
include "db.php";

// Get the brand_id from the URL query parameter
if (!isset($_GET['brand_id']) || !is_numeric($_GET['brand_id'])) {
    echo json_encode(["success" => false, "message" => "Missing or invalid brand_id."]);
    exit;
}


// Gets brand_id from the URL, or defaults to 1 if not provided.
$brand_id = (int)($_GET['brand_id'] ?? 1); 


// SQL to fetch photo_url and photo_type for the given brand
$sql = "SELECT photo_url, photo_type FROM brand_photos WHERE brand_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();

$images = [
    "logo" => null,
    "primaryImage" => null,
    "listingImage" => null,
    "detailImage1" => null,
    "detailImage2" => null
];

while ($row = $result->fetch_assoc()) {
    // Dynamically map the photo_url to the correct key using photo_type
    // Add the full URL for the frontend
    $images[$row['photo_type']] = $row['photo_url'];
}
$stmt->close();
$conn->close();

echo json_encode(["success" => true, "images" => $images]);

?>