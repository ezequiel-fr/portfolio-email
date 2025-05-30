<?php
    // Check if the environment is set to development
    if (($_ENV['APP_ENV'] ?? 'production') !== 'development') {
        header('Location: /');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <title>Document</title>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body>

    <h1>Hello, World!</h1>

    <pre>
        <code>
            <?php var_dump($_SERVER); var_dump($_ENV) ?>
        </code>
    </pre>

</body>
</html>