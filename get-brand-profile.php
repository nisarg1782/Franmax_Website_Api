<?php
// Set headers for CORS and JSON content type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database connection details
include "db.php";

// Get the brand ID from the GET request, or use a default for testing
// To use a dynamic ID, uncomment the line below and remove the hardcoded one.
$brandId = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
// $brandId = 4; // For testing, hardcoded brand ID

if ($brandId == 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid brand ID provided."]);
    exit();
}

// Corrected SQL query using a LEFT JOIN to get state and city names.
// Aliases are used for clarity (ru, s, c) and column names are given unique aliases.
$sql = "
    SELECT
        ru.*,
        s.name AS state_name,
        c.name AS city_name
        , mc.mas_cat_name AS master_category_name
    FROM registred_user ru
    LEFT JOIN states s ON ru.state_id = s.id
    LEFT JOIN cities c ON ru.city_id = c.id
    left join master_category mc on ru.mas_cat_id = mc.mas_cat_id
    WHERE ru.id = ? AND ru.user_type = 'brand'
";

// Use a prepared statement to prevent SQL injection
$stmt = $conn->prepare($sql);

// Bind the brandId parameter to the SQL query
$stmt->bind_param("i", $brandId);
$stmt->execute();
$result = $stmt->get_result();
$brand = $result->fetch_assoc();

if ($brand) {
    // If data is found, return it as a JSON object
    echo json_encode($brand);
} else {
    // If no data is found, return a 404 Not Found error with a JSON message
    http_response_code(404);
    echo json_encode(["error" => "Brand with ID " . $brandId . " not found."]);
}

$stmt->close();
$conn->close();
?>
