<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db.php";

// Get brand_id from GET or default to 1
$brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 1;

// --- Monthly Inquiry Counts ---
$monthly = [];
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(inquiry_date, '%b') AS month,
        MONTH(inquiry_date) AS month_num,
        COUNT(*) AS count
    FROM investor_inquiries
    WHERE brand_id = ?
    GROUP BY MONTH(inquiry_date)
    ORDER BY month_num
");
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthly[] = $row;
}
$stmt->close();

// --- State-wise Inquiry Counts ---
$statewise = [];
$stmt = $conn->prepare("
    SELECT s.name AS state, COUNT(*) AS count
    FROM investor_inquiries ii
    JOIN states s ON s.id = ii.state_id
    WHERE ii.brand_id = ?
    GROUP BY ii.state_id
    ORDER BY count DESC
");
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $statewise[] = $row;
}
$stmt->close();

// --- Total Inquiries ---
$total = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM investor_inquiries WHERE brand_id = ?");
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total = (int)$row['total'];
}
$stmt->close();

// --- Remark-wise Inquiry Counts ---
$remarkwise = [];
$stmt = $conn->prepare("
    SELECT remark, COUNT(*) as count
    FROM investor_inquiries
    WHERE brand_id = ?
    GROUP BY remark
");
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $remarkwise[] = $row;
}
$stmt->close();

// --- Final JSON Response ---
echo json_encode([
    "total" => $total,
    "monthly" => $monthly,
    "statewise" => $statewise,
    "remarkwise" => $remarkwise
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
