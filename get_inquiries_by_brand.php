<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";
// Validate brand_id
$brandId = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
if ($brandId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid brand_id"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            ii.id,
            ii.name,
            ii.email,
            ii.contact AS phone,
            ii.state_id AS state,
            ii.city_id AS city,
            ii.message,
            DATE(ii.inquiry_date) AS date,
            ii.status,
            ii.remark,
            ii.comment,
            ii.updated_at
        FROM investor_inquiries ii
        WHERE ii.brand_id = ?
        ORDER BY ii.inquiry_date DESC
    ");
    $stmt->bind_param("i", $brandId);
    $stmt->execute();
    $result = $stmt->get_result();

    $inquiries = [];
    while ($row = $result->fetch_assoc()) {
        $inquiries[] = $row;
    }

    $stmt->close();
    echo json_encode($inquiries, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Query failed", "details" => $e->getMessage()]);
}

$conn->close();
?>
