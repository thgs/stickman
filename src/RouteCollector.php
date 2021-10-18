<?php

namespace Thgs\Stickman;

use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Router;
use Auryn\Injector;
use Monolog\Logger;
use Psr\Container\ContainerInterface as Psr11Container;
use ReflectionAttribute;
use ReflectionClass;
use Thgs\Stickman\Dispatch\AurynDispatcher;
use Thgs\Stickman\Dispatch\DispatchCall;
use Thgs\Stickman\Dispatch\Psr11Dispatcher;
use TypeError;

class RouteCollector
{
    public function __construct(private Router $router, private Psr11Container|Injector $container, private Logger $logger)
    {
    }

    public function collectFrom($class)
    {
        $reflection = new ReflectionClass($class);
        $classAttributes = $reflection->getAttributes(Route::class);
        $classRoutes = $this->getRouteAttributeInstances($classAttributes);

        $dispatchCall = new DispatchCall($class);

        $this->addRoutes($dispatchCall, $classRoutes);

        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);
            $methodRoutes = $this->getRouteAttributeInstances($attributes);
            
            $dispatchCall = new DispatchCall($class, $method->getName());

            $this->addRoutes($dispatchCall, $methodRoutes);
        }
    }

    private function getRouteAttributeInstances(array $attributes)
    {
        $instances = [];
        foreach ($attributes as $attribute) {
            if (!$attribute instanceof ReflectionAttribute) {
                throw new TypeError('Expecting ReflectionAttribute');
            }

            $instances[] = $attribute->newInstance();
        }

        return $instances;
    }

    private function getDispatcherCallback(DispatchCall $dispatchCall): callable
    {
        if ($this->container instanceof Injector) {
            return new AurynDispatcher($this->container, $this->logger, $dispatchCall);
        }

        return new Psr11Dispatcher($this->container, $this->logger, $dispatchCall);
    }

    private function addRoutes(DispatchCall $dispatchCall, array $routes)
    {
        $callable = $this->getDispatcherCallback($dispatchCall);
        $requestHandler = new CallableRequestHandler($callable);

        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new TypeError('Expecting Route instance');
            }

            $middleware = [];
            foreach ($route->getMiddleware() as $middlewareClass) {
                $middleware[] = $this->containerMake($middlewareClass);
            }

            $this->logger->debug('Adding route: ' . $route->getMethod() . ' ' . $route->getPath() . ' -> ' . $dispatchCall->getCallable());
            $this->router->addRoute($route->getMethod(), $route->getPath(), $requestHandler, ...$middleware);
        }
    }

    private function containerMake(string $definition)
    {
        return $this->container instanceof Injector
            ? $this->container->make($definition)
            : $this->container->get($definition);
    }

    public function getRouter()
    {
        return $this->router;
    }
}