<?php
// This script uses the PHPMailer library to send an email with attachments.
// You must have PHPMailer installed. You can install it via Composer:
// composer require phpmailer/phpmailer

// Load DB bootstrap (also loads Composer autoload and .env via phpdotenv if available)
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends a welcome email with attachments to a newly registered user.
 *
 * @param string $toEmail The recipient's email address.
 * @param string $toName The recipient's name.
 * @param array $attachments An array of file paths for the attachments.
 * @return bool True on success, false on failure.
 */
function sendRegistrationEmail($toEmail, $toName, $attachments = []) {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // --- Server settings (from environment) ---
        $mail->isSMTP();
        $mail->SMTPAuth   = true;

        $smtpHost = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'sandbox.smtp.mailtrap.io';
        $smtpUser = $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: '';
        $smtpPass = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '';
        $smtpPort = (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 2525);
        $smtpSecure = strtolower($_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?: 'tls'); // tls|ssl|none

        $mail->Host = $smtpHost;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->Port = $smtpPort;
        if ($smtpSecure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtpSecure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // --- Recipients ---
        // Prefer explicit SMTP_FROM/SMTP_FROM_NAME; fallback to SMTP_USERNAME
        $fromEmail = $_ENV['SMTP_FROM'] ?? getenv('SMTP_FROM') ?: ($smtpUser ?: 'no-reply@example.com');
        $fromName  = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'Franmax India';
        $mail->setFrom($fromEmail, $fromName);

        // Setting the recipient from the function parameters
        $mail->addAddress($toEmail, $toName);

        // --- Content (Updated with new message and icons) ---
        $mail->isHTML(true);
        $mail->Subject = 'A Warm Welcome to the Franmax India!';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                <h1 style='color: #2c3e50;'>Welcome to Franmax India, {$toName}! ✨</h1>
                <p>We are absolutely thrilled to have you join our journey. Your registration is complete, and we are so excited to partner with you in building a successful future.</p>
                <p>At Franmax India, we believe in the boundless potential of our country and its entrepreneurs. Together, we're not just creating businesses; we're building a legacy of successful franchising that will make India proud on a global scale. 🚀</p>
                <p>We're ready to provide you with the tools, guidance, and support you need to turn your ambitions into reality. Let's make something incredible happen!</p>
                <p>With pride and excitement,</p>
                <p>The Franmax India Team<br>🤝🏆</p>
            </div>
        ";
        $mail->AltBody = "Welcome to the Franmax India Family, {$toName}!\n\nThank you for registering with us. We are excited to have you join our journey and work together to build a successful franchising future.\n\nWith pride and excitement,\nThe Franmax India Team";

        // --- Attachments ---
        foreach ($attachments as $attachmentPath) {
            if (file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath);
            } else {
                error_log("Attachment file not found: " . $attachmentPath);
            }
        }

        // Send the email
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the error for debugging purposes
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// --- Example Usage ---
// This block demonstrates how to call the function.
// In a real application, this would be triggered after a successful user registration.

// Define recipient details
// Using the user-provided recipient


// Specify the files to attach.
// Ensure these files exist on your server.


// Call the function
if (sendRegistrationEmail($recipientEmail, $recipientName, $filesToAttach)) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email. Check the server logs for details.";
}

?>
