<?php

namespace Thgs\Stickman;

use Amp\Failure;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Promise;
use Generator;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\Dispatch\DispatchCall;
use Throwable;

use function Amp\call;

class StickmanHandler implements RequestHandler
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

    public function handleRequest(Request $request): Promise
    {
        $arguments = $this->prepareArguments($request);
        $method = $this->dispatchCall->getMethod();

        $promise = call(function () use ($method, $arguments) {
            return $this->instance->{$method}(...$arguments);
        });

        // @todo this probably could be done better.
        $promise->onResolve(function (Throwable $error = null) {
            if ($error) {
                $this->logger->error($error->getMessage());
            }
        });

        return $promise;
    }
}
