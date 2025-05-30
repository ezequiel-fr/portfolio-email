<?php

use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

// New route collection
$routes = new RouteCollection();

// Add routes
$routes->add('test', new SymfonyRoute('/test', [], [], [], '', [], ['GET']));

$routes->add('email', new SymfonyRoute('/email/{id}', [
    'id' => null,
], [], [], '', [], ['POST']));

// Output routes
return $routes;
