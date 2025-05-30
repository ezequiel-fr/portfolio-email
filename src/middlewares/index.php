<?php

// Load middlewares
$middlewares = [
    'auth' => require_once __DIR__ . '/auth.php',
];

$response = null;

// If a middleware is callable, replace it with the callable
foreach ($middlewares as $key => $middleware) {
    if (is_callable($middleware)) {
        $middlewares[$key] = $middleware($response);
    }
}

return [
    'middlewares' => $middlewares,
    'response' => $response,
];
