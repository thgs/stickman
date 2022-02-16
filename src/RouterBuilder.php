<?php

namespace Thgs\Stickman;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Router;
use Amp\Injector\Container;

class RouterBuilder
{
    private $middlewares = [];
    private $prefix = '';
    private $handler;

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

    public function setHandler(string|callable $handlerOrClass): self
    {
        $this->handler = $handlerOrClass;
        return $this;
    }

    // @todo if have setHandler then $handlerOrClass is ignored
    public function addRoute(string $method, string $uri, string|callable $handlerOrClass, string|callable ...$middlewares): self
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $this->prefix . $uri,
            'handlerOrClass' => $this->handler ?: $handlerOrClass,
            'middlewares' => array_merge($middlewares, $this->middlewares),
        ];

        return $this;
    }

    public function buildWith(Container $container, int $cacheSize = Router::DEFAULT_CACHE_SIZE): Router
    {
        $router = new Router($cacheSize);

        foreach ($this->routes as $route) {
            $handler = $this->make($route['handlerOrClass'], $container);
            $middlewares = $this->makeMiddlewares($container, ...$route['middlewares']);
            $router->addRoute($route['method'], $route['uri'], $handler, ...$middlewares);
        }

        return $router;
    }

    // add buildWithApplication

    private function make(string|callable $handlerOrClass, Container $container)
    {
        // @todo this stops functions from being passed as strings, as they are still callable but what if they have deps
        if (is_callable($handlerOrClass)) {
            return $handlerOrClass;
        }

        return $container->get($handlerOrClass);
    }

    private function makeMiddlewares(Container $container, string|callable ...$references)
    {
        $middlewares = [];
        foreach ($references as $reference) {
            $middlewares[] = $this->make($reference, $container);
        }
        return $middlewares;
    }
}
