<?php

namespace Thgs\Stickman;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Router;
use Amp\Injector\Container;

class RouterBuilder
{
    private $middlewares = [];
    private $prefix = '';

    public function middlewareGroup(Middleware ...$middlewares): self
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function clearMiddlewareGroup(): self
    {
        $this->middlewares = [];
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function clearPrefix(): self
    {
        $this->prefix = '';
        return $this;
    }

    public function addRoute(string $method, string $route, string|callable $handlerOrClass, Middleware ...$middlewares): self
    {
        $this->routes[] = [
            'method' => $method,
            'route' => $this->prefix . $route,
            'handlerOrClass' => $handlerOrClass,
            'middlewares' => array_merge($middlewares, $this->middlewares),
        ];

        return $this;
    }

    public function build(Container $container, int $cacheSize = Router::DEFAULT_CACHE_SIZE): Router
    {
        $router = new Router($cacheSize);

        foreach ($this->routes as $route) {
            $handler = $this->getHandler($route['handlerOrClass'], $container);
            $router->addRoute($route['method'], $route['route'], $handler, ...$route['middlewares']);
        }

        return $router;
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
