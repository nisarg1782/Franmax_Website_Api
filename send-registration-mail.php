<?php
// This script uses the PHPMailer library to send an email with attachments.
// You must have PHPMailer installed. You can install it via Composer:
// composer require phpmailer/phpmailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
// This path assumes the script is in the same directory as the 'vendor' folder.
require 'vendor/autoload.php';

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
        // --- Server settings (Updated for Gmail) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';             // Gmail's SMTP server
        $mail->SMTPAuth   = true;

        // Use your Gmail address as the username
        $mail->Username   = 'nisargt1782@gmail.com';

        // IMPORTANT: Use an App Password here, NOT your regular Gmail password.
        // You can generate one from your Google Account settings.
        $mail->Password   = 'mxfddbrntybxwrap';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- Recipients ---
        // For security, the 'From' address must match the authenticated 'Username'.
        $mail->setFrom('nisargt1782@gmail.com', 'Franmax India');

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
