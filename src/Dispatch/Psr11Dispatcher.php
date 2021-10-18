<?php

namespace Thgs\Stickman\Dispatch;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Generator;
use Psr\Container\ContainerInterface as Psr11Container;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\DispatcherInterface;

class Psr11Dispatcher implements DispatcherInterface
{
    public function __construct(private Psr11Container $container, private LoggerInterface $logger, private DispatchCall $dispatchCall)
    {        
    }

    public function __invoke(Request $request): Response|Generator
    {
        try {
            $arguments = $request->getAttribute(Router::class);
            $arguments['request'] = $request;

            $instance = $this->container->get($this->dispatchCall->getClass());

            $method = $this->dispatchCall->getMethod();
            return $instance->{$method}(...$arguments);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}