<?php

/* Include the application bootstrap */
include_once __DIR__.'/app/bootstrap.php';

use Services\Factory;
use Library\IO\Response;

$responder = new Response($app);
$router    = new \Routing\Router($app, $responder);
$router->route(Factory::create('container')->getRouteMapping());

$app->run();
