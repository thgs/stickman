<?php

namespace Thgs\Stickman;

use Attribute;

#[Attribute]
class Route
{
    public function __construct(private string $method = '', private string $path = '', private array $middleware = [])
    {
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }
}