<?php

namespace Thgs\Stickman;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Auryn\Injector;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\Dispatch\DispatchCall;
use TypeError;

class Stickman
{
    public HttpServer $httpServer;

    public function __construct(
        private Injector $injector,
        Configuration $configuration,
    ) {
        $this->logger = $this->getLogger($configuration->logName);

        $router = new Router();
        $routeCollector = new RouteCollector($this->logger);
        foreach ($configuration->handlers->collection as $class) {
            $routeCollector->collectFrom($class);
        }

        $router = $this->configureRouter($router, $routeCollector->getRouteCollection());

        $this->httpServer = new HttpServer(
            $configuration->servers->collection,
            $router,
            $this->logger,
            $configuration->options
        );

        $this->injector->share($this->httpServer);

        $this->reportFinish($this->logger);
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

        $this->logger->debug("Stickman bootstrap end. Memory usage: $usage MB");
    }

    protected function configureRouter(Router $router, RouteCollection $collection): Router
    {
        foreach ($collection->getRoutes() as $dispatchCall => $routes) {
            $handler = $this->getHandler($dispatchCall);

            foreach ($routes as $route) {
                if (!$route instanceof Route) {
                    throw new TypeError('Expecting Route instance');
                }

                $middleware = [];
                foreach ($route->getMiddleware() as $middlewareClass) {
                    $middleware[] = $this->make($middlewareClass);
                }

                $this->logger->debug('Add route: ' . $route->getMethod() . ' ' . $route->getPath() . ' -> ' . $dispatchCall->getCallable());

                $router->addRoute($route->getMethod(), $route->getPath(), $handler, ...$middleware);
            }
        }

        return $router;
    }

    protected function make(string $definition)
    {
        $ret = $this->injector->make($definition);

        $this->logger->debug('Container making: ' . $definition . ' #' . spl_object_id($ret));

        return $ret;
    }

    protected function getHandler(DispatchCall $dispatchCall): StickmanHandler
    {
        $instance = $this->make($dispatchCall->getClass());

        return new StickmanHandler($instance, $dispatchCall->getMethod(), $this->logger);
    }
}
