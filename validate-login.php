<?php
// ✅ CORS & Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// ✅ Handle OPTIONS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Database Connection
include "db.php";

// ✅ Read Raw JSON Body
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// ✅ Debugging (optional)
// file_put_contents("debug.log", $raw.PHP_EOL, FILE_APPEND);

// ✅ Validate JSON & Fields
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
    exit();
}

if (empty($data['email']) || empty($data['password'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit();
}

$email = trim($data['email']);
$password = trim($data['password']);

// ✅ Check User in DB
$stmt = $conn->prepare("SELECT id, name,email,password, user_type FROM registred_user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // ✅ Verify Password
    if (password_verify($password, $user['password'])) {
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "user_type" => $user['user_type'],
                "email"=>$user["email"]
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
}

$stmt->close();
$conn->close();
?>
