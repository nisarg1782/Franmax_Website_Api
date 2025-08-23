<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

try {
    $sql = "
        SELECT 
            ii.id,
            b.name AS brand_name,
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
        LEFT JOIN brands b ON b.id = ii.brand_id
        ORDER BY ii.inquiry_date DESC
    ";

    $stmt = $conn->prepare($sql);
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
