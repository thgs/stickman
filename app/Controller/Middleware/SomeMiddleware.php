<?php

namespace App\Controller\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;

use function Amp\call;

class SomeMiddleware implements Middleware
{
    public function handleRequest(Request $request, RequestHandler $next): Promise
    {
        return call(function () use ($request, $next) {
            $requestTime = microtime(true);
            $request->setHeader('x-request-start', $requestTime);
            
            $response = yield $next->handleRequest($request);
            $response->setHeader("x-request-time", microtime(true) - $requestTime);

            return $response;
        });
    }
}