<?php

namespace Thgs\Stickman;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Options;
use Amp\Http\Server\Router;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Auryn\Injector;
use Monolog\Logger;

class Stickman
{
    public const DEFAULT_LOGNAME = 'server';

    public HttpServer $httpServer;

    public function __construct(
        ConfiguredContainerFactoryInterface|Injector $containerOrFactory,
        HandlersCollection $handlers,
        ServerCollection $servers,
        Options $options = null,
        $logName = self::DEFAULT_LOGNAME
    ) {
        /** @var Injector $injector */
        $injector = $containerOrFactory instanceof Injector 
            ? $containerOrFactory 
            : $containerOrFactory->getContainer();

        $logger = $this->getLogger($logName);

        $router = new Router();
        $routeCollector = new RouteCollector($router, $injector, $logger);
        foreach ($handlers->collection as $class) {
            $routeCollector->collectFrom($class);
        }
        
        $this->httpServer = new HttpServer($servers->collection, $routeCollector->getRouter(), $logger, $options);

        $injector->share($this->httpServer);
    }

    private function getLogger(string $logName): Logger
    {
        $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter);
        $logger = new Logger($logName);
        $logger->pushHandler($logHandler);
        
        return $logger;
    }
}