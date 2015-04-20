<?php

use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Simple as ViewSimple;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as LoggerAdapter;
use Phalcon\Events\Manager;
use Phalcon\Events\Manager as EventsManager;
use Juice\Auth\Auth as Auth;

error_reporting(0);
if ($config->application->debug) {
    error_reporting(E_ALL);
}
/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

$di->set('config', $config, true);

/**
 * setting router
 */
$di->set('router', $router, true);

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);
    if ($config->application->staticBaseUri) {
        $url->setStaticBaseUri($config->application->staticBaseUri);
    }

    return $url;
}, true);

/**
 * Setting up the view component
 */
$di->set('view', function () use ($config) {

    $view = new ViewSimple();

    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines(array(
        '.php' => function ($view, $di) use ($config) {

            $volt = new VoltEngine($view, $di);

            $volt->setOptions(array(
                'compiledPath' => $config->application->cacheDir . 'volt/',
                'compiledSeparator' => '_',
                'compileAlways' => $config->application->debug,// 总是编译,本地调试开启
                'compileAlways' => true,
                'stat' => true,
            ));

            return $volt;
        },
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
        //'.html' => 'Phalcon\Mvc\View\Engine\Php'
    ));

    return $view;
}, true);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {

    $eventsManager = new EventsManager();

    $logger = new LoggerAdapter($config->logs->sqlLog);
    //Listen all the database events
    $eventsManager->attach('db', function($event, $db) use ($logger, $config) {
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

}, true);

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
 * Start the session the first time some component request the session service
 */
$di->set('session', function () {
    $session = new SessionAdapter();
    $session->start();

    return $session;
}, true);

/**
 * log
 */
$di->set('logger', function () use($config) {
    $logger = new LoggerAdapter($config->logs->default);

    return $logger;
});

/**
 * 设置登录验证
 */
$di->set('auth', function(){
    $auth = new Auth;
    return $auth;
}, true);

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
