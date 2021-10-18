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
use Psr\Container\ContainerInterface as Psr11Container;
use Psr\Log\LoggerInterface;

class Stickman
{
    public const DEFAULT_LOGNAME = 'server';

    public HttpServer $httpServer;

    public function __construct(
        Psr11Container|Injector $container,
        HandlersCollection $handlers,
        ServerCollection $servers,
        Options $options = null,
        $logName = self::DEFAULT_LOGNAME
    ) {
        $logger = $this->getLogger($logName);

        $router = new Router();
        $routeCollector = new RouteCollector($router, $container, $logger);
        foreach ($handlers->collection as $class) {
            $routeCollector->collectFrom($class);
        }
        
        $this->httpServer = new HttpServer($servers->collection, $routeCollector->getRouter(), $logger, $options);

        if ($container instanceof Injector) {
            $container->share($this->httpServer);
        }

        $this->reportFinish($logger);
    }

    private function getLogger(string $logName): Logger
    {
        $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter);
        $logger = new Logger($logName);
        $logger->pushHandler($logHandler);
        
        return $logger;
    }

    private function reportFinish(LoggerInterface $logger)
    {
        $usage = number_format(memory_get_peak_usage(true) / 1024 / 1024, 0);

        $logger->debug("Stickman bootstrap end. Memory usage: $usage MB");
    }
}