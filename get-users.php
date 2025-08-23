<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include "db.php";

$sql = "SELECT id, name,email,status,created_at  FROM  franmax_user";

$result = $conn->query($sql);

if ($result) {
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        $brands[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $brands
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No users found.'
    ]);
}

$conn->close();
