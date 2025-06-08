<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/logs.php';

/* Load environment variables */
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

/* Symfony Router */
// Get the current request
$request = Request::createFromGlobals();

// Load middlewares
$middlewares = require __DIR__ . '/middlewares/index.php';
// If a response was set, stop the code here
if (isset($middlewares['response']) && $middlewares['response'] instanceof Response) {
    $middlewares['response']->send();
    exit;
}

// Load routes
$routes = require __DIR__ . '/routes.php';

// Create a request context
$context = new RequestContext();
$context->fromRequest($request);

// Set up URL Matcher
$urlMatcher = new UrlMatcher($routes, $context);
$pathInfo = $request->getPathInfo();

// Strip '/api' prefix
if (str_starts_with($pathInfo, '/api')) {
    $pathInfo = substr($pathInfo, 4) ?: '/';
}

// Initialize response
$response = new Response();

// Set up CORS headers
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

if ($request->isMethod('OPTIONS')) {
    // Handle preflight requests
    $response->setStatusCode(Response::HTTP_OK);
    $response->send();

    exit;
}

try {
    // Extract route variables
    $routeVariables = $urlMatcher->match($pathInfo);
    extract($routeVariables);

    // Start output buffering
    ob_start();

    // Include the corresponding page based on the route
    include sprintf(__DIR__ . '/pages/%s.php', $_route);

    // Set response status code if specified
    if (isset($code) && !empty($code)) {
        $response->setStatusCode($code);
    } else {
        $response->setStatusCode(Response::HTTP_OK);
    }

    // Set response type if specified
    if (isset($contentType) && !empty($contentType)) {
        $response->headers->set('Content-Type', $contentType);
    } else {
        // Default to the HTTP_ACCEPT header if not specified
        $acceptHeader = isset($_SERVER["HTTP_ACCEPT"])
            ? explode(',', $_SERVER["HTTP_ACCEPT"])[0]
            : '*/*';
        $response->headers->set('Content-Type', $acceptHeader);
    }

    // Get the content from the output buffer
    $content = ob_get_clean();
    $response->setContent($content);

} catch (ResourceNotFoundException $e) {
    // Handle 404 Not Found
    $response->setStatusCode(Response::HTTP_NOT_FOUND);
    $response->setContent('Page not found');
} catch (MethodNotAllowedException $e) {
    // Handle 405 Method Not Allowed
    $response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
    $response->setContent('Method not allowed');
} catch (\Exception $e) {
    // Handle other exceptions
    $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    $response->setContent('An error occurred: ' . $e->getMessage());
}

// Send the response
$response->send();

// Log the request and response
logMessage(
    sprintf(
        "%s %s - %d",
        $request->getMethod(),
        $request->getPathInfo(),
        $response->getStatusCode()
    )
);
