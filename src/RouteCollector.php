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

// collects routes and adds/configures a given Router
class RouteCollector
{
    public function __construct(private Router $router, private Injector $injector, private Logger $logger)
    {
    }

    public function collectFrom($class)
    {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes();

            /** @var ReflectionAttribute $attribute */
            foreach ($attributes as $attribute) {
                $this->addRoute($method, $attribute);
            }
        }
    }

    private function addRoute(ReflectionMethod $method, ReflectionAttribute $attribute)
    {
        $routeArguments = $attribute->getArguments();

        $callable = $method->class . '::' . $method->name;
        $requestHandler = new CallableRequestHandler(
            function (Request $request) use ($callable) {
                try {
                    $arguments = [':request' => $request];
                    foreach ($request->getAttribute(Router::class) as $key => $value) {
                        // if passed without ':', it will try to instantiate the class given (!)
                        $arguments[':' . $key] = $value;
                    }

                    return $this->injector->execute($callable, $arguments);
                } catch (\Throwable $e) {
                    $this->logger->error(get_class($e) . ' - ' . $e->getMessage());
                    var_dump($e->getTraceAsString());
                }
            }
        );

        $this->router->addRoute($routeArguments['method'], $routeArguments['path'], $requestHandler);
    }

    public function getRouter()
    {
        return $this->router;
    }
}