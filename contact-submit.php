<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

$data = json_decode(file_get_contents("php://input"));

if (
  isset($data->name) && isset($data->email) &&
  isset($data->contact) && isset($data->message)
) {
    include "db.php";

    $name = $conn->real_escape_string($data->name);
    $email = $conn->real_escape_string($data->email);
    $contact = $conn->real_escape_string($data->contact);
    $message = $conn->real_escape_string($data->message);
    $inquiry_date = date('Y-m-d H:i:s');

    // Check if the contact number already exists
    $checkSql = "SELECT id FROM contact_us WHERE contact = '$contact' LIMIT 1";
    $checkResult = $conn->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        echo json_encode(["success" => false, "error" => "Mobile number already exists."]);
        $conn->close();
        exit;
    }

    // Insert query
    $insertSql = "INSERT INTO contact_us (name, email, contact, message, inquiry_date)
                  VALUES ('$name', '$email', '$contact', '$message', '$inquiry_date')";

    if ($conn->query($insertSql)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to insert data."]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid input."]);
}
?>
