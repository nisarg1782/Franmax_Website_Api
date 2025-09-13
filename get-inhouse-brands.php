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

// 🔹 Capture user_id from GET (sent by React)
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Base SQL
$sql = "SELECT
    b.id,
    b.brandName,
    b.brandContact,
    b.ownerName,
    b.ownerContact,
    b.contactPersonName,
    b.contactPersonNumber,
    b.callDate,
    b.callTime,
    b.callRemark,
    b.meetingDate,
    b.meetingTime,
    b.meetingRemark,
    b.product,
    b.offerPrice,
    b.counterPrice,
    b.remark,
    b.assined_to,  -- include this column
    b.status,
    mc.mas_cat_name AS masterCategoryName,
    c.cat_name AS categoryName,
    sc.subcat_name AS subCategoryName,
    s.name AS stateName,
    ct.name AS cityName
FROM
    inhouse_brands AS b
LEFT JOIN
    master_category AS mc ON b.masterCategory = mc.mas_cat_id
LEFT JOIN
    category AS c ON b.category = c.cat_id
LEFT JOIN
    subcategory AS sc ON b.subCategory = sc.subcat_id
LEFT JOIN
    states AS s ON b.state = s.id
LEFT JOIN
    cities AS ct ON b.city = ct.id
";

// 🔹 Filter if user_id is provided
if ($user_id > 0) {
    $sql .= " WHERE b.assined_to = $user_id";
}

$sql .= " ORDER BY b.id DESC";

$result = $conn->query($sql);

if ($result) {
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row;
    }
    http_response_code(200);
    echo json_encode($brands);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Error fetching data: " . $conn->error]);
}

$conn->close();
?>
