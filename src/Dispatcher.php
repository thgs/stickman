<?php

namespace Thgs\Stickman;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Generator;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\Dispatch\DispatchCall;

class Dispatcher
{
    public function __construct(private $instance, private DispatchCall $dispatchCall, private LoggerInterface $logger)
    {
    }

    public function prepareArguments(Request $request): array
    {
        $arguments = $request->getAttribute(Router::class);
        $arguments['request'] = $request;
        return $arguments;
    }

    public function __invoke(Request $request): Response|Generator
    {
        try {
            $arguments = $this->prepareArguments($request);
            $method = $this->dispatchCall->getMethod();

            return $this->instance->{$method}(...$arguments);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}