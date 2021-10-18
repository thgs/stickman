<?php

namespace Thgs\Stickman\Dispatch;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Auryn\Injector;
use Generator;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\DispatcherInterface;

class AurynDispatcher implements DispatcherInterface
{
    public function __construct(private Injector $injector, private LoggerInterface $logger, private DispatchCall $dispatchCall)
    {
    }

    public function __invoke(Request $request): Response|Generator
    {
        try {
            $arguments = [':request' => $request];
            // if attribute Router::class is not set we get an exception here
            foreach ($request->getAttribute(Router::class) as $key => $value) {
                // if passed without ':', it will try to instantiate the class given (!)
                $arguments[':' . $key] = $value;
            }

            $toDispatch = $this->dispatchCall->getCallable();
            return $this->injector->execute($toDispatch, $arguments);
        } catch (\Throwable $e) {
            $this->logger->error(get_class($e) . ' - ' . $e->getMessage());
            throw $e;
        } 
    }
}