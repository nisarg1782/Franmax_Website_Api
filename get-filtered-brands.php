<?php
header("Content-Type: application/json");
$mas_cat = $_GET['mas_cat'] ?? 0;
$cat = $_GET['cat'] ?? 0;
$sub = $_GET['sub'] ?? 0;

$conn = new mysqli("localhost", "root", "", "testproject");

$sql = "SELECT * FROM brands";
if($mas_cat) $sql .= " AND mas_cat_id='$mas_cat'";
if($cat) $sql .= " AND cat_id='$cat'";
if($sub) $sql .= " AND sub_cat_id='$sub'";

$result = $conn->query($sql);
$rows = [];
while($r = $result->fetch_assoc()) $rows[] = $r;

echo json_encode($rows);
?>
