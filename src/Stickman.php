<?php

namespace Thgs\Stickman;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Auryn\Injector;
use Monolog\Logger;

class Stickman
{
    public HttpServer $httpServer;

    public function __construct(
        ConfiguredContainerFactoryInterface|Injector $containerOrFactory,
        HandlersCollection $handlers,
        ServerCollection $servers,
    ) {
        # Logger - for now
        $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter);
        $logger = new Logger('server');
        $logger->pushHandler($logHandler);

        $injector = $containerOrFactory instanceof Injector 
            ? $containerOrFactory 
            : $containerOrFactory->getContainer();

        $router = new Router();
        $routeCollector = new RouteCollector($router, $logger, $injector);
        foreach ($handlers->collection as $class) {
            $routeCollector->collectFrom($class);
        }

        $this->httpServer = new HttpServer($servers->collection, $routeCollector->getRouter(), $logger);
    }
}