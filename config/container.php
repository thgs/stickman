<?php

use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Auryn\Injector;
use Monolog\Logger;

$injector = new Injector();

$injector->define(Logger::class, [':name' => 'server']);
$injector->prepare(Logger::class, function ($obj, $injector) {
    // change this to be full in the container
    $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter());

    $obj->pushHandler($logHandler);
});

return $injector;