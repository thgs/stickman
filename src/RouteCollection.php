<?php

namespace Thgs\Stickman;

use SplObjectStorage;
use Thgs\Stickman\Dispatch\DispatchCall;

class RouteCollection
{
    /** array<DispatchCall, Route> */
    private SplObjectStorage $routes;

    public function __construct()
    {
        $this->routes = new SplObjectStorage();
    }

    public function addRoutes(array $routes, DispatchCall $call)
    {
        if (!isset($this->routes[$call])) {
            $this->routes[$call] = $routes;
            return;
        }

        $this->routes[$call] = array_merge($this->routes[$call], $routes);
    }

    public function getRoutes()
    {
        foreach ($this->routes as $dispatchCall) {
            yield $dispatchCall => $this->routes[$dispatchCall];
        }
    }
}
