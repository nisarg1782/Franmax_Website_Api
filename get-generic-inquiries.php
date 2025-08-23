<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include "db.php";

// --- Main Enquiries Table ---
$sqlEnquiries = "
    SELECT 
        e.id,
        e.full_name AS name,
        e.phone,
        e.email,
        e.message,
        s.name AS state_name,
        s.id AS state_id,
        c.name AS city_name,
        c.id AS city_id,
        e.created_at
    FROM enquiries AS e
    LEFT JOIN states AS s ON e.state_id = s.id
    LEFT JOIN cities AS c ON e.city_id = c.id
";

// --- Footer Inquiries Table ---
$sqlFooter = "
    SELECT 
        f.id,
        f.name,
        f.contact AS phone,
        f.email,
        f.message,
        s.name AS state_name,
        s.id AS state_id,
        c.name AS city_name,
        c.id AS city_id,
        f.created_at
    FROM footer_inquiries AS f
    LEFT JOIN cities AS c ON f.city_id = c.id
    LEFT JOIN states AS s ON f.state_id = s.id
    WHERE f.inquiry_type = 'franchise'
";

// --- Run Both Queries ---
$combined = [];

$resultEnquiries = $conn->query($sqlEnquiries);
if ($resultEnquiries && $resultEnquiries->num_rows > 0) {
    while ($row = $resultEnquiries->fetch_assoc()) {
        $combined[] = $row;
    }
}

$resultFooter = $conn->query($sqlFooter);
if ($resultFooter && $resultFooter->num_rows > 0) {
    while ($row = $resultFooter->fetch_assoc()) {
        $combined[] = $row;
    }
}

// --- Sort all inquiries by created_at DESC ---
usort($combined, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode([
    'success' => true,
    'data' => $combined
]);

$conn->close();
