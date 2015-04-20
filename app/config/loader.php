<?php

require BASE_PATH . 'vendor/autoload.php';

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    array(
        $config->autoload->controllersDir,
        $config->autoload->controllersDir . 'mobile/',
        $config->autoload->modelsDir,
        $config->autoload->libraryDir,
        $config->autoload->pluginsDir
    )
);

$loader->registerNamespaces(array(
    'Phalcon' => BASE_PATH . 'vendor/phalcon/incubator/Library/Phalcon/',
    'Juice\\Models' => $config->autoload->modelsDir,
    'Phalcon\\Utils' => $config->autoload->libraryDir . 'PrettyExceptions/Library/Phalcon/Utils/',
    'Juice\\Auth' => $config->autoload->libraryDir . 'Auth/',
));
//echo BASE_PATH . 'vendor/phalcon/incubator/Library/Phalcon/';

$loader->register();

require $config->autoload->helperDir;

require __DIR__ . '/exception.php';
require __DIR__ . '/filters.php';
