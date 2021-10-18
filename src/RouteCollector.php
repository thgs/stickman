<?php

namespace Thgs\Stickman;

use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Router;
use Auryn\Injector;
use Monolog\Logger;
use Psr\Container\ContainerInterface as Psr11Container;
use ReflectionAttribute;
use ReflectionClass;
use Thgs\Stickman\Dispatch\DispatchCall;
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

    private function getDispatcher(DispatchCall $dispatchCall): Dispatcher
    {
        $instance = $this->containerMake($dispatchCall->getClass());

        return new Dispatcher($instance, $dispatchCall, $this->logger);
    }

    private function addRoutes(DispatchCall $dispatchCall, array $routes)
    {
        $dispatcher = $this->getDispatcher($dispatchCall);
        $requestHandler = new CallableRequestHandler($dispatcher);

        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new TypeError('Expecting Route instance');
            }

            $middleware = [];
            foreach ($route->getMiddleware() as $middlewareClass) {
                $middleware[] = $this->containerMake($middlewareClass);
            }

            $this->logger->debug('Add route: ' . $route->getMethod() . ' ' . $route->getPath() . ' -> ' . $dispatchCall->getCallable());

            $this->router->addRoute($route->getMethod(), $route->getPath(), $requestHandler, ...$middleware);
        }
    }

    private function containerMake(string $definition)
    {
        $this->logger->debug('Container making: ' . $definition);

        return $this->container instanceof Injector
            ? $this->container->make($definition)
            : $this->container->get($definition);
    }

    public function getRouter()
    {
        return $this->router;
    }
}