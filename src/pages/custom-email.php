<?php

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__.'/../mail.php';

// Define the expected POST variables
const POST_VARS = ['name', 'email', 'subject', 'content', 'text'];
const REQUIRED_POST_VARS = ['name', 'email', 'subject', 'content'];

const ALLOWED_FILE_TYPES = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'pdf' => 'application/pdf',
];

const FILE_SIZE_LIMIT = 10 * 1024 * 1024; // 10 MB
const FILE_UPLOAD_DIR = __DIR__.'/../../tmp/';

// Set response content type to JSON
$contentType = 'application/json';

try {
    // Validate required variables
    foreach (REQUIRED_POST_VARS as $var) {
        if (!isset($_POST[$var]) || empty($_POST[$var])) {
            throw new Error("Invalid request");
        }
    }

    // Transform all POST variables
    foreach (POST_VARS as $var) {
        if (isset($_POST[$var])) {
            $_POST[$var] = trim(htmlspecialchars_decode($_POST[$var]));
            error_log("POST variable: $var = ".$_POST[$var]);
        }
    }

    // Validate attachments if provided
    $attachments = [];
    error_log(json_encode($_FILES));

    if (isset($_FILES['attachments']) && !empty($_FILES['attachments'])) {
        foreach ($_FILES['attachments']['name'] as $index => $name) {
            // Check for upload errors
            if ($_FILES['attachments']['error'][$index] !== UPLOAD_ERR_OK) continue;

            // Get file details
            $tmpName = $_FILES['attachments']['tmp_name'][$index];
            $size = $_FILES['attachments']['size'][$index];

            // Use finfo to get the MIME type of the file
            // (DO NOT TRUST $_FILES['attachments']['type'])
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type = $finfo->file($tmpName);

            // Validate file type
            if (!array_key_exists(pathinfo($name, PATHINFO_EXTENSION), ALLOWED_FILE_TYPES))
                continue;

            // Validate file size
            if ($size > FILE_SIZE_LIMIT) continue;

            // Generate a unique filename to avoid overwriting
            $uniqueName = uniqid('upload_', true) . '.' . pathinfo($name, PATHINFO_EXTENSION);
            $destination = FILE_UPLOAD_DIR . $uniqueName;

            // Move the uploaded file
            if (!move_uploaded_file($tmpName, $destination)) continue;

            // Store attachment details
            $attachments[] = [
                'filename' => $destination,
                'cid' => uniqid('cid_', true),
                'name' => $name,
                'type' => $type,
            ];
        }
    }

    $emailSent = send_mail(
        $_POST['email'],
        $_POST['name'],
        $_POST['subject'],
        $_POST['content'],
        [],
        $_POST['text'],
        $attachments
    );

    // Send success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Email sent successfully!',
        'messageCode' => 'success.email_sent',
    ]);
} catch (\PHPMailerException $e) {
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
