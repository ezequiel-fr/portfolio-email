<?php

// Initialize the logging system
$logDir = __DIR__ . '/../logs';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/app.log';
$errorFile = $logDir . '/error.log';

$logStream = fopen($logFile, 'a');
$errorStream = fopen($errorFile, 'a');

if (!$logStream || !$errorStream) {
    die("Could not open log files for writing.");
}

/** Function to encode a message
 * If the message is an array, it will be encoded as a JSON string.
 * Otherwise, it will return the message as is.
 */
function decodeMessage($message) {
    // Decode the message if it's a JSON string
    if (is_string($message) && is_array(json_decode($message, true))) {
        return json_decode($message, true);
    }

    return $message;
}

// Function to log messages
function logMessage($message, $level = 'INFO') {
    global $logStream;

    $timestamp = date('d/m/Y H:i:s');
    fwrite($logStream, "[$timestamp] [$level] $message\n");
}

// Function to log errors
function logError($message, $level = 'ERROR') {
    global $errorStream;

    $timestamp = date('d/m/Y H:i:s');
    fwrite($errorStream, "[$timestamp] [$level] $message\n");
}

// Function to close log streams
function closeLogs() {
    global $logStream, $errorStream;

    if ($logStream) fclose($logStream);
    if ($errorStream) fclose($errorStream);
}

/* Register events */
// Register shutdown function to close log streams
register_shutdown_function('closeLogs');

// Register error handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $errorMessage = "Error [$errno]: $errstr in $errfile on line $errline";
    logError($errorMessage);
});

// Register exception handler
set_exception_handler(function ($exception) {
    $errorMessage = "Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    logError($errorMessage);
});

// Register shutdown function to handle fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        $errorMessage = "Fatal error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        logError($errorMessage);
    }
});
