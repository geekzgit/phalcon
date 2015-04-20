<?php

use \Phalcon\Utils;

set_exception_handler(function($e) use ($config)
{
    $di = Phalcon\DI::getDefault();
    $logger = $di->getLogger();
    $logger->error($e);
    if ($config->application->debug) {
        $p = new \Phalcon\Utils\PrettyExceptions();
        //Change the base uri for static resources
        //$p->setBaseUri('/');

        //Change the CSS theme (default, night or minimalist)
        $p->setTheme('night');
        return $p->handle($e);
    }
});

set_error_handler(function($errorCode, $errorMessage, $errorFile, $errorLine) use ($config)
{
    $di = Phalcon\DI::getDefault();
    $logger = $di->getLogger();
    $logger->error($errorCode . "\n" . $errorMessage . "\n" . $errorFile . "\n" . $errorLine);

    if ($config->application->debug) {
        $p = new \Phalcon\Utils\PrettyExceptions();
        //Change the CSS theme (default, night or minimalist)
        $p->setTheme('night');
        return $p->handleError($errorCode, $errorMessage, $errorFile, $errorLine);
    }
});
