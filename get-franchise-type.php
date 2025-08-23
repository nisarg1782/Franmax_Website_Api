<?php
// ✅ Set headers for CORS and JSON output
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

// ✅ Handle OPTIONS request (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Database connection variables
include "db.php";

// ✅ Fetch data from the table
$sql = "SELECT id, name FROM franchise_types ORDER BY id ASC";
$result = $conn->query($sql);

$franchise_type = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $franchise_type[] = $row;
    }
    echo json_encode([
        "success" => true,
        "data" => $franchise_type
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No records found"
    ]);
}

// ✅ Close connection
$conn->close();
?>
