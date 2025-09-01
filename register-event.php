<?php
// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Razorpay\Api\Api;

require __DIR__ . '/vendor/autoload.php';
include "db.php";

// Load .env variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Sensitive data from environment variables
$key_id = $_ENV['RAZORPAY_KEY_ID'];
$key_secret = $_ENV['RAZORPAY_SECRET'];
$email_user = $_ENV['MAIL_USER'];
$email_pass = $_ENV['MAIL_PASS'];

// Function to send email
function sendRegistrationEmail($toEmail, $toName, $register_user_id, $registrationId, $payment_id)
{
    global $email_user, $email_pass;
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $email_user;
        $mail->Password = $email_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_user, 'Franmax India');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Event Registration Confirmed - Your Ticket Inside!';

        $message = "
        <html><body>
        <h2>Event Registration Confirmed</h2>
        <p>Hello $toName,</p>
        <p>Thank you for registering for our Franxpo Event! Your registration details are below:</p>
        <ul>
            <li><strong>Name:</strong> $toName</li>
            <li><strong>Email:</strong> $toEmail</li>
            <li><strong>Venue:</strong> Taj Skyline, Sindhubhavan Road Ahmedabad</li>
            <li><strong>Event Date:</strong> Sunday 14, September 2025</li>
            <li><strong>Registration ID:</strong> $register_user_id$registrationId</li>
            <li><strong>Payment ID:</strong> $payment_id</li>
            <li><strong>Date:</strong> " . date('Y-m-d') . "</li>
        </ul>
        <p><a href='https://www.google.com/maps/dir//Taj+Skyline+Ahmedabad/...'>Click Here For Venue Location</a></p>
        <p>Please keep this email for your records.</p>
        </body></html>";

        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Get raw input
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit();
}

// Extract input
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$state = intval($input['state'] ?? 0);
$city = intval($input['city'] ?? 0);
$source = trim($input['source'] ?? '');
$payment_id = trim($input['payment_id'] ?? '');

// Validate input
if (empty($name) || empty($email) || empty($phone) || !$state || !$city || empty($source)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit();
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Phone number must be 10 digits"]);
    exit();
}

// Verify payment using Razorpay API
// try {
//     $api = new Api($key_id, $key_secret);
//     $payment = $api->payment->fetch($payment_id);

//     if (!$payment || $payment->status !== 'captured') {
//         http_response_code(400);
//         echo json_encode(["success" => false, "message" => "Payment not verified"]);
//         exit();
//     }
// } catch (Exception $e) {
//     http_response_code(400);
//     echo json_encode(["success" => false, "message" => "Payment verification failed: " . $e->getMessage()]);
//     exit();
// }

// Generate user ID hash
$combined_input = $name . $email . $phone . $state . $city . $source;
$register_user_id = sha1($combined_input);

// Insert into database
$stmt = $conn->prepare("INSERT INTO event_registrations (name, email, phone, state_id, city_id, source, register_user_id, payment_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiisss", $name, $email, $phone, $state, $city, $source, $register_user_id, $payment_id);

if ($stmt->execute()) {
    $registration_id = $stmt->insert_id;
    sendRegistrationEmail($email, $name, $register_user_id, $registration_id, $payment_id);

    echo json_encode([
        "success" => true,
        "message" => "Registration successful. Ticket sent to your email. If not received, please contact Franmax India."
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to save registration"
    ]);
}

$stmt->close();
$conn->close();
?>
