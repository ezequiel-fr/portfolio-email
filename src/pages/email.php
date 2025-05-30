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

    // Send the email
    send_mail(
        $_ENV['SAMPLE_RECEIVER_MAIL'] ?? $_ENV['CONTACT_MAIL'],
        $_ENV['SAMPLE_RECEIVER_NAME'] ?? $_ENV['CONTACT_NAME'],
        'Contact from portfolio',
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
    ]);
} catch (PHPMailer\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send email: '.$e->getMessage(),
    ]);

    $code = $e->getCode();
} catch (\Throwable $th) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: '.$th->getMessage(),
    ]);

    $code = $th->getCode();
}
