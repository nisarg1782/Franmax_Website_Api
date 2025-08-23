<?php

// Set the Content-Type header to application/json
header('Content-Type: application/json');

// Database credentials - REPLACE with your actual credentials
include "db.php";
// SQL query to fetch all data from the leasing_enquiries table
$sql = "SELECT 
            leasing_enquiries.id, 
            lease_properties.owner_name as owner_name,
            leasing_enquiries.name AS inquiry_person, 
            leasing_enquiries.email, 
            leasing_enquiries.number AS contact, 
            states.name AS state_name,
            cities.name AS city_name, 
            leasing_enquiries.message, 
            leasing_enquiries.created_at 
        FROM leasing_enquiries
        left join states ON states.id = leasing_enquiries.state_id
        left join cities ON cities.id = leasing_enquiries.city_id
        left join lease_properties ON lease_properties.property_key = leasing_enquiries.property_key
        ORDER BY created_at DESC" ;

$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    // Fetch all rows and add them to the data array
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Close the database connection
$conn->close();

// Return the data as a JSON response
echo json_encode(["status" => "success", "data" => $data]);

?>
