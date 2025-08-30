<?php
// Set headers to allow cross-origin requests and specify JSON content type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include PHPMailer and its exceptions
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader for PHPMailer
require 'vendor/autoload.php';

// Function to send the registration ticket email using PHPMailer
function sendRegistrationEmail($toEmail, $toName, $register_user_id, $registrationId,$payment_id)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // Use your Gmail address as the username
        $mail->Username   = 'franmaxindia@gmail.com';
        // IMPORTANT: Use an App Password here, NOT your regular Gmail password.
        $mail->Password   = 'fpwrraeodhdciqik';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('franmaxindia@gmail.com', 'Franmax India');
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Event Registration Confirmed - Your Ticket Inside!';

        // HTML email body (the "ticket")
        $message = "
        <html>
        <head>
            <title>Event Registration Confirmed</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .ticket-container { max-width: 600px; margin: auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden; }
                .ticket-header { background-color: #060644; color: white; padding: 20px; text-align: center; }
                .ticket-body { padding: 20px; }
                .ticket-body h3 { color: #333; }
                .ticket-details { margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
                .detail-row { margin-bottom: 10px; }
                .detail-row span { font-weight: bold; }
                .ticket-footer { background-color: #f4f4f4; padding: 15px; text-align: center; font-size: 0.8em; color: #777; }
            </style>
        </head>
        <body>
            <div class='ticket-container'>
                <div class='ticket-header'>
                    <h1>Event Registration Confirmed</h1>
                </div>
                <div class='ticket-body'>
                    <p>Hello $toName,</p>
                    <p>Thank you for registering for our Franxpo Event! We are excited to see you there. Your registration details are below. This email serves as your official ticket.</p>
                    <h3>Your Ticket Details:</h3>
                    <div class='ticket-details'>
                        <div class='detail-row'><span>Name:</span> $toName</div>
                        <div class='detail-row'><span>Email:</span> $toEmail</div>
                         <div class='detail-row'><span>Venue:</span> Taj Skyline, Sindhubhavan Road Ahmedabad</div>
                          <div class='detail-row'><span>Event Date:</span> Sunday 14,September 2025</div>
                          <a href='https://www.google.com/maps/dir//Taj+Skyline+Ahmedabad,+Sankalp+Square+III,+Opp.+Saket+3,+Sindhubhavan+Road,+nr.+Neelkanth+Green,+Shilaj,+Gujarat+380059/@23.0441336,72.3998757,12z/data=!4m8!4m7!1m0!1m5!1m1!1s0x395e9b6318e8da91:0x864bb42461cc953f!2m2!1d72.4822773!2d23.0441549?entry=ttu&g_ep=EgoyMDI1MDgxNy4wIKXMDSoASAFQAw%3D%3D'>Click Here For Venue Location</a>
                          <br>
                        <div class='detail-row'><span>Registration ID:</span> $register_user_id$registrationId</div>
`                        <div class='detail-row'><span>Payment ID:</span> $payment_id</div>
                        <div class='detail-row'><span>Date of Registration:</span> " . date('Y-m-d') . "</div>
                    </div>
                </div>
                <div class='ticket-footer'>
                    Please keep this email for your records. If you have any questions, feel free to contact us.
                </div>
            </div>
        </body>
        </html>";
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// DB connection
include "db.php";

// Get raw input data
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit();
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$state = intval($input['state'] ?? 0);
$city = intval($input['city'] ?? 0);
$source = trim($input["source"]);
$payment_id = trim($input["payment_id"] ?? '');
$combined_input = $name . $email . $phone . $state . $city . $source;

// Generate a unique hash from the combined string.
// This creates a unique key based on the specific combination of input values.
$register_user_id = sha1($combined_input);
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
// Insert new record
$stmt = $conn->prepare("INSERT INTO event_registrations (name, email, phone, state_id, city_id, source,register_user_id,payment_id) VALUES (?, ?, ?, ?, ?, ?,?,?)");
$stmt->bind_param("sssiisss", $name, $email, $phone, $state, $city, $source, $register_user_id, $payment_id);

if ($stmt->execute()) {
    $registration_id = $stmt->insert_id;

    // Send the email using the PHPMailer function
    $mail_sent = sendRegistrationEmail($email, $name, $register_user_id, $registration_id,$payment_id);

    echo json_encode([
        "success" => true,
        "message" => "Registration successful. Ticket sent to your email.if you dont receive email within 5-10 minutes please contact franmaxindia"

    ]);
} else {
    // Registration failed
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to save registration"
    ]);
}

$stmt->close();
$conn->close();
