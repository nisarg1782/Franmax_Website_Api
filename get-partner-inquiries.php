<?php
// Set the content type to application/json
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// --- Database Configuration ---
// IMPORTANT: Replace these with your actual database credentials.
include "db.php";

// --- SQL Query ---
// Assuming the table name is 'franchise_inquiries' based on the schema provided.
$sql = "SELECT partner_inquiries.id, partner_inquiries.name, interested_in_franmax, email, contact, states.name AS state_name, cities.name AS city_name, message, created_at FROM partner_inquiries
left join states on partner_inquiries.state_id = states.id
left join cities on partner_inquiries.city_id = cities.id
ORDER BY created_at DESC";

$result = $conn->query($sql);

$data = [];

// --- Process Results ---
if ($result && $result->num_rows > 0) {
    // Fetch all rows and store them in an array
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    // Return the data as a JSON object
    echo json_encode(["data" => $data]);
} else {
    // If no records found, return an empty array
    echo json_encode(["data" => []]);
}

// Close the database connection
$conn->close();
?>
