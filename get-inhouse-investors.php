<?php
// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
    exit();
}

// Database connection
include "db.php";

// Capture optional user_id from GET
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Base SQL with joins
$sql = "SELECT
    i.id,
    i.name,
    i.contact,
    i.email,
    i.investment_budget,
    i.call_date,
    i.call_time,
    i.call_remark,
    i.meeting_date,
    i.meeting_time,
    i.meeting_remark,
    i.final_remark,
    i.stage,
    i.master_category,
    mc.mas_cat_name AS masterCategoryName,
    s.name AS stateName,
    c.name AS cityName
FROM
    inhouse_investors AS i
LEFT JOIN
    master_category AS mc ON i.master_category = mc.mas_cat_id
LEFT JOIN
    states AS s ON i.state = s.id
LEFT JOIN
    cities AS c ON i.city = c.id
";

// Filter by user_id if provided
if ($user_id > 0) {
    $sql .= " WHERE i.assined_to = $user_id";
}

$sql .= " ORDER BY i.id DESC";

$result = $conn->query($sql);

if ($result) {
    $investors = [];
    while ($row = $result->fetch_assoc()) {
        $investors[] = $row;
    }
    http_response_code(200);
    echo json_encode($investors);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Error fetching data: " . $conn->error]);
}

$conn->close();
?>
