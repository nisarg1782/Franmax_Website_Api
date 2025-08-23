<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (
    isset($data['name'], $data['number'], $data['email'], $data['brand'], $data['message'], $data['state_id'], $data['city_id'])
) {
    include "db.php";
    $stmt = $conn->prepare("INSERT INTO modalmaking_inquiries (name, number, email, brand_name, message, state_id, city_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssssi",
        $data['name'],
        $data['number'],
        $data['email'],
        $data['brand'],
        $data['message'],
        $data['state_id'],
        $data['city_id']
    );

    $success = $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(["success" => $success]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid data received"]);
}
