<?php
// Create the router
$router = new \Phalcon\Mvc\Router();

//Setting a specific default
$router->setDefaultController('index');
$router->setDefaultAction('index');

//Using an array
$router->setDefaults(array(
    'controller' => 'index',
    'action' => 'index'
));

//Define a route
$router->add(
    "/system/import-sql",
    array(
        "controller" => "system",
        "action"     => "importSql",
    )
);

$router->handle();
