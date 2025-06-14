<?php

use PHPMailer\PHPMailer;

require __DIR__.'/../mail.php';

// Define the expected POST variables
const POST_VARS = ['name', 'email', 'subject', 'message'];
// Set response content type to JSON
$contentType = 'application/json';

try {
    // Get variables from the request (post data)
    foreach (POST_VARS as $var) {
        if (!isset($_POST[$var]) || empty($_POST[$var])) {
            throw new Error("Invalid request");
        }

        $_POST[$var] = trim(htmlspecialchars_decode($_POST[$var]));
    }

    // Send an acknowledgment email to the sender before sending the main email
    send_mail(
        $_POST['email'],
        $_POST['name'],
        'Merci pour votre message',
        file_get_contents(__DIR__.'/../../templates/acknowledgment.html'),
        [
            'subject' => $_POST['subject'],
            'message' => nl2br(substr($_POST['message'], 0, 300)) . (
                strlen($_POST['message']) > 300 ? '...' : ''
            )
        ],
    );

    // Send the email
    send_mail(
        $_ENV['SAMPLE_RECEIVER_MAIL'] ?? $_ENV['CONTACT_MAIL'],
        $_ENV['SAMPLE_RECEIVER_NAME'] ?? $_ENV['CONTACT_NAME'],
        'Nouveau message depuis le portfolio',
        file_get_contents(__DIR__.'/../../templates/email.html'),
        [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'subject' => $_POST['subject'],
            'message' => nl2br($_POST['message']),
        ],
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Email sent successfully!',
        'messageCode' => 'success.email_sent',
    ]);
} catch (PHPMailer\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send email: '.$e->getMessage(),
        'messageCode' => 'error.not_found',
    ]);

    $code = 500;
} catch (\Throwable $th) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: '.$th->getMessage(),
        'messageCode' => 'error.internal_server_error',
    ]);

    $code = 500;
}
