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

// ✅ Validate JSON & Fields
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
    exit();
}

// Update: Check for 'username' instead of 'email'
if (empty($data['user_name']) || empty($data['password'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit();
}

// Update: Use 'username' from the payload
$username = trim($data['user_name']);
$password = trim($data['password']);

// ✅ Check User in DB
// Update: Search by 'user_name' in the database
$stmt = $conn->prepare("SELECT id, name, user_name, password, user_type,email FROM registred_user WHERE user_name = ? AND status = 'active'");
$stmt->bind_param("s", $username);
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
                // Update: Return the user's username
                "username" => $user["user_name"],
                "email" => $user["email"] // Email is not available in this context
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid username or password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid username or password if your credentials are correct please contact admin to activate your account."]);
}

$stmt->close();
$conn->close();
?>