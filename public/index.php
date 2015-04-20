<?php

error_reporting(E_ALL);

try {

    define('BASE_PATH', realpath('..') . '/');
    define('APP_PATH', BASE_PATH . 'app/');
    define('STORAGE_PATH', BASE_PATH . 'storage/');

    // 系统环境变量，参考laravel模式
    $systemEnv = [
        'local' => [
            'geekz-pc'
        ]
    ];

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

    $application->useImplicitView(false); // 禁用自动渲染

    echo $application->handle()->getContent();

} catch (\Exception $e) {
    //echo $e;
    //echo get_class($e), ": ", $e->getMessage(), "\n";
    //echo " File=", $e->getFile(), "\n";
    //echo " Line=", $e->getLine(), "\n";
    /*echo $e->getTraceAsString();*/
    //$di->getDb()->rollback();
    throw $e;
}
