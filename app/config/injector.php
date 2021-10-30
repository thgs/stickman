<?php

use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use App\Controller\TestController;
use Auryn\Injector;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

return (function () {
    $injector = new Injector();

    $injector->define(Logger::class, [':name' => 'controllerLog']);
    $injector->prepare(Logger::class, function ($obj, $injector) {
        $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter());
        $obj->pushHandler($logHandler);
    });

    $injector->alias(LoggerInterface::class, Logger::class);

    // Controllers
    $injector->share(TestController::class);

    return $injector;
})();
