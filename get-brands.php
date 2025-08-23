<?php
// Enable CORS and set content type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// DB connection
include "db.php";

// Check connection
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["error" => "Database connection failed"]);
  exit;
}

// Query brands
$sql = "SELECT name, no_franchise_outlet, investment_requirements, outlets FROM brands";
$result = $conn->query($sql);

// Build response
$brands = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $brands[] = $row;
  }
}

// Return JSON
echo json_encode($brands);
$conn->close();
?>
