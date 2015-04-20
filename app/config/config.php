<?php

function getSystemConfig($systemEnv) {
    $name = gethostname();
    $config = null;
    $environment = '';
    foreach ($systemEnv as $env => $hostnames) {
        foreach ($hostnames as $hostname) {
            if ($hostname == $name) {
                $environment = $env;
                $configPath = __DIR__ . '/' . $env . '/config.php';
                if (file_exists($configPath)) {
                    $config = include $configPath;
                }
                break;
            }
        }
    }
    $configDefault = new \Phalcon\Config([
        'database' => [
            'adapter'     => 'Mysql',
            'host'        => 'localhosts',
            'username'    => 'root',
            'password'    => 'root',
            'dbname'      => 'db',
            'charset'     => 'utf8',
        ],
        'application' => [
            'debug' => false,
            'baseUri'        => '',
            'staticBaseUri'        => '',
            //'staticBaseUri'        => '',
            'cacheDir'       => STORAGE_PATH . 'cache/',
            'viewsDir'       => APP_PATH . 'views/',
        ],
        'autoload' => [
            'controllersDir' => APP_PATH . 'controllers/',
            'modelsDir'      => APP_PATH . 'models/',
            'pluginsDir'     => APP_PATH . 'plugins/',
            'libraryDir'     => APP_PATH . 'libraries/',
            'helperDir'     => APP_PATH . 'helper/helper.functions.php',
        ],
        'logs' => [
            'default' => STORAGE_PATH . 'logs/default.log',
            'sqlLog' => STORAGE_PATH . 'logs/sql/log.log',
            'commandLog' => STORAGE_PATH . 'logs/command/log.log',
        ],
        'session' => [
        ],
    ]);
    if (! empty($config)) {
        $configDefault->merge($config);
    }
    $config = $configDefault;
    $config->env = $environment;
    return $config;
}
$config = getSystemConfig($systemEnv);
return $config;
