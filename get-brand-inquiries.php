<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include "db.php";

$sql = "SELECT 
            bi.id, 
            b.name AS brand_name, 
            bi.name AS inquiry_brand_name,
            bi.email, 
            bi.contact, 
            bi.message, 
            bi.inquiry_date,
            bi.state_id,
            bi.city_id,
            bi.status
        FROM 
            brand_inquiry AS bi
        JOIN 
            brands AS b ON bi.brand_id = b.id
        ORDER BY 
            bi.inquiry_date DESC";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit();
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    $inquiries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $inquiries[] = $row;
    }
    echo json_encode([
        'success' => true,
        'data' => $inquiries
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No inquiries found.'
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
