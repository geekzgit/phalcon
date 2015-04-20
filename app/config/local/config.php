<?php
/**
 * 本地配置
 */
return new \Phalcon\Config([
    'database' => [
        'username'    => 'root',
        'password'    => 'root',
    ],
    'application' => [
        'debug' => true,
        'baseUri'        => '',
        'staticBaseUri'        => '',
    ],
]);
