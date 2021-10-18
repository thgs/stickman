<?php

namespace Thgs\Stickman\Dispatch;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Generator;
use Thgs\Stickman\DispatchStrategy;

class DispatchCall
{
    public function __construct(private string $class, private string $method = '__invoke')
    {
    }

    /**
     * This does not return a true PHP callable
     * 
     * @return string
     */
    public function getCallable()
    {
        return $this->class . '::' . $this->method;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod()
    {
        return $this->method;
    }
}