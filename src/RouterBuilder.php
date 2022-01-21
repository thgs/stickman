<?php

namespace Thgs\Stickman;

use Amp\Http\Server\Router;
use Amp\Injector\Container;

class RouterBuilder
{
    private Router $router;

    public function __construct(int $cacheSize = Router::DEFAULT_CACHE_SIZE)
    {
        $this->router = new Router($cacheSize);
    }

    // not sure if we should allow handler OR class, we could just accept class? what if you want to pass a callable
    public function addRoute(string $method, string $route, string|callable $handlerOrClass, $middlewares)
    {
        $clone = clone $this;
        $clone->routes[] = [
            'method' => $method,
            'route' => $route,
            'handlerOrClass' => $handlerOrClass,
            'middlewares' => $middlewares
        ];

        return $clone;
    }

    public function build(Container $container)
    {
        foreach ($this->routes as $route) {
            $handler = $this->getHandler($route['handler'], $container);
            $this->router->addRoute($route['method'], $route['route'], $handler, ...$route['middlewares']);
        }

        return $this->router;
    }

    private function getHandler(string|callable $handlerOrClass, Container $container)
    {
        // @todo this stops functions from being passed as strings, as they are still callable but what if they have deps
        if (is_callable($handlerOrClass)) {
            return $handlerOrClass;
        }

        return $container->get($handlerOrClass);
    }
}
