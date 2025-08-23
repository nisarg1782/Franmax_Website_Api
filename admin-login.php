<?php
// Set CORS headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    exit(0);
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection
include "db.php";

// Read raw JSON input from the request body
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if ($data === null || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Invalid JSON input or missing credentials."]);
    exit;
}

$email = trim($data['email']);
$inputPassword = $data['password'];

// ✅ CHANGE: Select the 'permissions' column from the database
$sql = "SELECT id, name, password, permissions FROM franmax_user WHERE email = ? and status='active'";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepared statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $hashedPassword = $user['password'];

    // Verify the hashed password
    if (password_verify($inputPassword, $hashedPassword)) {
        http_response_code(200); // OK

        // ✅ CHANGE: Decode the permissions JSON string into a PHP array
        $permissions = json_decode($user['permissions'], true);
        // Ensure permissions is an array, even if decoding fails or it's null
        if (!is_array($permissions)) {
            $permissions = [];
        }

        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "permissions" => $permissions // ✅ CHANGE: Send the decoded permissions array to the frontend
            ]
        ]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    }
} else {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
}

$stmt->close();
$conn->close();
?>
