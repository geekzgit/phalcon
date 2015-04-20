#!/usr/bin/env php
<?php

use Phalcon\DI\FactoryDefault\CLI as CliDI,
     Phalcon\CLI\Console as ConsoleApp;

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as LoggerAdapter;
use Phalcon\Events\Manager as EventsManager;

set_time_limit(0);

define('VERSION', '1.0.0');

//使用CLI工厂类作为默认的服务容器
$di = new CliDI();

// 定义应用目录路径
define('BASE_PATH', realpath(dirname(__FILE__)) . '/');
define('APP_PATH', BASE_PATH . 'app/');
define('STORAGE_PATH', BASE_PATH . 'storage/');

// 系统环境变量，参考laravel模式
$systemEnv = [
    'local' => [
        'geekz-pc'
    ]
];

//加载配置文件（如果存在）
if(is_readable(APP_PATH . '/config/config.php')) {
    $config = include APP_PATH . '/config/config.php';
    $di->set('config', $config);
}

/**
 *
 * 注册类自动加载器
 */
require BASE_PATH . 'vendor/autoload.php';

$loader = new \Phalcon\Loader();
$loader->registerDirs(
    array(
        $config->autoload->modelsDir,
        $config->autoload->libraryDir,
        $config->autoload->pluginsDir,
        APP_PATH . 'commands/',
    )
);
$loader->register();

require $config->autoload->helperDir;

set_exception_handler(function($e)
{
    $di = Phalcon\DI::getDefault();
    $logger = $di->getLogger();
    $logger->error($e);
});

set_error_handler(function($errorCode, $errorMessage, $errorFile, $errorLine)
{
    $di = Phalcon\DI::getDefault();
    $logger = $di->getLogger();
    $logger->error($errorCode . "\n" . $errorMessage . "\n" . $errorFile . "\n" . $errorLine);

});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {

    $eventsManager = new EventsManager();

    $logger = new LoggerAdapter($config->logs->sqlLog);
    //Listen all the database events
    $eventsManager->attach('db', function($event, $db) use ($config, $logger) {
        if ($event->getType() == 'beforeQuery') {
            $sql = $db->getSQLStatement();
            if (
                stripos($sql, 'SELECT') === FALSE &&
                stripos($sql, 'DESCRIBE') === FALSE
            ) {
                $logger->log($sql, Logger::INFO);
            } elseif ($config->application->debug) {
                $logger->log($sql, Logger::INFO);
            }
        }
    });

     $db = new DbAdapter(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'charset' => empty($config->database->charset) ? 'utf8' : $config->database->charset
    ));
    $db->setEventsManager($eventsManager);
    return $db;

});
//}, true);

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () {
    \Phalcon\Mvc\Model::setup([
        'exceptionOnFailedSave' => true,
        'notNullValidations' => false,
    ]);
    return new MetaDataAdapter();
}, true);

/**
 * log
 */
$di->set('logger', function () use($config) {
    $logger = new LoggerAdapter($config->logs->commandLog);

    return $logger;
});

/**
 * 设置缓存
 */
$di->set('cache', function() {

    //Connect to redis
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    //Create a Data frontend and set a default lifetime to 1 hour
    $frontend = new Phalcon\Cache\Frontend\Data(array(
        'lifetime' => 3600
    ));

    //Create the cache passing the connection
    $cache = new Phalcon\Cache\Backend\Redis($frontend, array(
        'redis' => $redis
    ));
    return $cache;

}, true);

$di->set('queue', function() {
    //Connect to the queue
    $queue = new Phalcon\Queue\Beanstalk(array(
        'host' => '127.0.0.1'
    ));
    return $queue;
}, true);

// 创建console应用
$console = new ConsoleApp();
$console->setDI($di);

$di->setShared('console', $console);

/**
 * 处理console应用参数
 */
$arguments = array();
foreach($argv as $k => $arg) {
    if($k == 1) {
        $arguments['task'] = $arg;
    } elseif($k == 2) {
        $arguments['action'] = $arg;
    } elseif($k >= 3) {
       $arguments['params'][] = $arg;
    }
}

// 定义全局的参数， 设定当前任务及动作
define('CURRENT_TASK', (isset($argv[1]) ? $argv[1] : null));
define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

try {
    // 处理参数
    $console->handle($arguments);
}
catch (\Phalcon\Exception $e) {
    echo $e->getMessage();
    exit(255);
}
