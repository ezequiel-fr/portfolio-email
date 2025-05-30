<?php

use Symfony\Component\HttpFoundation\Response;

// Get the current request headers
$headers = $request->headers->all();

return function (&$response) {
    global $request, $headers;

    if ($request->getMethod() !== 'OPTIONS' && $request->getMethod() !== 'GET')
    {
        if (isset($headers['authorization']) && !empty($headers['authorization'])) {
            $token = $headers['authorization'][0];
            $decodedToken = base64_decode($token);

            // Check if the decoded token matches the expected value
            if ($decodedToken === $_ENV['APP_SECRET']) {
                // If the request is authorized, continue processing
                return true;
            }
        }

        // If the request is not an OPTIONS or GET request, return a 401 Unauthorized response
        $response = new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        $response->headers->set('Content-Type', 'application/json');

        $response->setContent(json_encode([
            'status' => 'error',
            'message' => 'Unauthorized request',
        ]));
    };

    return false;
};
