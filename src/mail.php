<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer.
 * 
 * @param string $email The recipient's email address
 * @param string $name The recipient's name
 * @param string $subject The subject of the email
 * @param string $content The HTML content of the email
 * @param array|null $params Optional parameters to replace placeholders in the subject and content
 * @param string|null $altBody Optional plain text alternative body for the email
 * @param array|null $attachments Optional array of file paths to attach to the email
 */
function send_mail(
    string $email,
    string $name,
    string $subject,
    string $content,
    ?array $params = [],
    ?string $altBody = "",
    ?array $attachments = null
) {
    // Create new email
    $mail = new PHPMailer(true);

    if (isset($_ENV['SENDER_PASSWORD']) && !empty($_ENV['SENDER_PASSWORD'])) {
        // SMTP settings
        $mail->isSMTP();

        // Server settings
        $mail->Host        = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth    = boolval($_ENV['SMTP_AUTH']);
        $mail->Username    = $_ENV['SENDER_MAIL'];
        $mail->Password    = $_ENV['SENDER_PASSWORD'];
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = intval($_ENV['SMTP_PORT']);
    }

    // Recipients
    $mail->setFrom($_ENV['SENDER_MAIL'], $_ENV['SENDER_NAME']);
    $mail->addReplyTo($_ENV['CONTACT_MAIL'], $_ENV['CONTACT_NAME']);
    $mail->addAddress($email, $name);

    // replace placeholders in the string with right params
    $params = array_merge($params, ['name' => $name, 'email' => $email]);
    $map = array_map(fn($key) => sprintf('{{%s}}', $key), array_keys($params));
    $val = array_values($params);

    $subject = str_replace($map, $val, $subject);
    $content = str_replace($map, $val, $content);
    $altBody = str_replace($map, $val, $altBody);

    // Attachments
    if (is_array($attachments) && count($attachments) > 0) {
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            } else {
                throw new Exception("Attachment file does not exist: " . $attachment);
            }
        }
    }

    // content
    $mail->isHTML(true);

    $mail->Subject = $subject;
    $mail->Body = $content;
    $mail->AltBody = $altBody;

    // add some additional configuration
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->ContentType = 'text/html;charset=utf-8';
    $mail->XMailer = null;

    // send the email
    if (!$mail->send()) {
        throw "Mailer Error: ".$mail->ErrorInfo;
    } else {
        return true;
    }
}
