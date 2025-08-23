<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$mobile = trim($data['mobile'] ?? '');
$email = trim($data['email'] ?? '');
$state = trim($data['state'] ?? '');
$city = trim($data['city'] ?? '');

if ($name && $mobile && $email && $state && $city) {
  $stmt = $conn->prepare("INSERT INTO leasing_form (name, mobile, email, state_id, city_id) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sssii", $name, $mobile, $email, $state, $city);

  if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Form submitted successfully"]);
  } else {
    echo json_encode(["success" => false, "message" => "Error submitting form"]);
  }

  $stmt->close();
} else {
  echo json_encode(["success" => false, "message" => "All fields are required"]);
}

$conn->close();
?>
