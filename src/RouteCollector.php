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
    public function __construct(private Router $router, private Logger $logger, private Injector $injector)
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
        $requestHandler = new CallableRequestHandler(function (Request $request) use ($callable) {
            return $this->injector->execute($callable, [':request' => $request]);
        });

        $this->router->addRoute($routeArguments['method'], $routeArguments['path'], $requestHandler);
    }

    public function getRouter()
    {
        return $this->router;
    }
}