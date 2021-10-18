<?php

namespace Thgs\Stickman;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Router;
use Auryn\Injector;
use Monolog\Logger;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use TypeError;

// collects routes and adds/configures a given Router
class RouteCollector
{
    public function __construct(private Router $router, private Injector $injector, private Logger $logger)
    {
    }

    public function collectFrom($class)
    {
        $reflection = new ReflectionClass($class);

        $classRoutes = $reflection->getAttributes(Route::class);
        $this->addClassRoute($class, $classRoutes);

        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);

            /** @var ReflectionAttribute $attribute */
            foreach ($attributes as $attribute) {
                $this->addMethodRoute($method, $attribute);
            }
        }
    }

    private function getDispatcherCallback(string $toDispatch): callable
    {
        return function (Request $request) use ($toDispatch) {
            try {
                $arguments = [':request' => $request];
                foreach ($request->getAttribute(Router::class) as $key => $value) {
                    // if passed without ':', it will try to instantiate the class given (!)
                    $arguments[':' . $key] = $value;
                }

                return $this->injector->execute($toDispatch, $arguments);
            } catch (\Throwable $e) {
                $this->logger->error(get_class($e) . ' - ' . $e->getMessage());
                var_dump($e->getTraceAsString());
            }
        };
    }

    private function addClassRoute(string $class, array $attributes)
    {
        $callable = $this->getDispatcherCallback($class . '::__invoke');
        $requestHandler = new CallableRequestHandler($callable);

        foreach ($attributes as $attribute) {
            if (!$attribute instanceof ReflectionAttribute) {
                throw new TypeError('Expecting ReflectionAttribute');
            }

            $routeArguments = $attribute->getArguments();
            $middleware = [];
            foreach ($routeArguments['middleware'] as $class) {
                $middleware[] = $this->injector->make($class);
            }

            $this->router->addRoute($routeArguments['method'], $routeArguments['path'], $requestHandler, ...$middleware);
        }
    }

    private function addMethodRoute(ReflectionMethod $method, ReflectionAttribute $attribute)
    {
        $routeArguments = $attribute->getArguments();

        $callable = $this->getDispatcherCallback($method->class . '::' . $method->name);
        $requestHandler = new CallableRequestHandler($callable);

        $middleware = [];
        foreach ($routeArguments['middleware'] as $class) {
            $middleware[] = $this->injector->make($class);
        }

        $this->router->addRoute($routeArguments['method'], $routeArguments['path'], $requestHandler, ...$middleware);
    }

    public function getRouter()
    {
        return $this->router;
    }
}