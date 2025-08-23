<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// include 'db.php'; // Assuming you have a separate file for DB credentials

// // Create connection
include "db.php";
$sql = "SELECT event_registrations.id,event_registrations.name,email,phone,s.name AS state_name,c.name AS city_name,registration_date,source,register_user_id FROM event_registrations
 LEFT JOIN cities AS c ON event_registrations.city_id = c.id
    LEFT JOIN states AS s ON event_registrations.state_id = s.id
";
$result = $conn->query($sql);

$faq = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faq[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'city_name' => $row['city_name'],
            'state_name' => $row['state_name'],
            'register_date' => $row["registration_date"],
            'source'=>$row["source"],
            'contact' => $row["phone"],
            'register_user_id' => $row["register_user_id"],

        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $faq
    ]);
} else {
    echo json_encode([
        'success' => true,
        'data' => []
    ]);
}

$conn->close();
