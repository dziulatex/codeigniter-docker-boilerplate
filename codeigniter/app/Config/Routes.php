<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    // Roller coaster endpoints
    $routes->post('coasters', 'CoasterController::create');
    $routes->put('coasters/(:segment)', 'CoasterController::update/$1');

    // Wagon endpoints
    $routes->post('coasters/(:segment)/wagons', 'CoasterController::addWagon/$1');
    $routes->delete('coasters/(:segment)/wagons/(:segment)', 'CoasterController::removeWagon/$1/$2');
});
