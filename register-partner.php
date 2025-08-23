<?php
// ✅ Set headers for CORS and JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// ✅ Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

// Database connection details
include "db.php";

// ✅ Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Validate all required fields using the new, consistent keys
if (
  isset($data['name']) &&
  isset($data['email']) &&
  isset($data['mobile']) &&
  isset($data['password']) &&
  isset($data['state_id']) &&
  isset($data['city_id']) &&
  isset($data['user_type'])
) {
  // ✅ Sanitize inputs
  $name = trim($data['name']);
  $email = trim($data['email']);
  $mobile = trim($data['mobile']);
  $password = password_hash(trim($data['password']), PASSWORD_BCRYPT);
  $state_id = (int)$data['state_id'];
  $city_id = (int)$data['city_id'];
  $user_type = trim($data['user_type']);
  $status = "active";
  $mode = "online";

  // ✅ Check if email or mobile already exists
  $check = $conn->prepare("SELECT id FROM registred_user WHERE email = ? OR mobile = ?");
  $check->bind_param("ss", $email, $mobile);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email or mobile number already registered."]);
    $check->close();
    $conn->close();
    exit();
  }
  $check->close();

  // ✅ Insert into DB
  $stmt = $conn->prepare("INSERT INTO registred_user (name, email, mobile, password, state_id, city_id, user_type, status, mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    $conn->close();
    exit();
  }
  
  // ✅ Fixed the bind_param string to "ssssiiss" for the 9 variables
  $stmt->bind_param("ssssiiss", $name, $email, $mobile, $password, $state_id, $city_id, $user_type, $status, $mode);

  if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Partner registered successfully"]);
  } else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
  }

  $stmt->close();
} else {
  echo json_encode(["success" => false, "message" => "Invalid input"]);
}

$conn->close();
?>
