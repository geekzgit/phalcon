<?php

error_reporting(E_ALL);

try {

    define('APP_PATH', realpath('..') . '/');

    /**
     * Read the configuration
     */
    $config = include __DIR__ . "/../app/config/config.php";

    /**
     * Read auto-loader
     */
    include __DIR__ . "/../app/config/loader.php";

    /**
     * read router
     */
    include __DIR__ . "/../app/config/router.php";

    /**
     * Read services
     */
    include __DIR__ . "/../app/config/services.php";


    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Application($di);

    echo $application->handle()->getContent();

} catch (\Exception $e) {
    echo $e->getMessage();
}
