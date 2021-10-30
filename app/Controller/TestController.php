<?php

namespace App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use App\Controller\Middleware\SomeMiddleware;
use Generator;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\Route;

// The below Route will dispatch from __invoke
#[Route(method: "GET", path: "invokable/{name}")]
class TestController
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    #[Route(method: "GET", path: "some-action")]
    public function someAction(Request $request): Response|Generator
    {
        $this->logger->info('works here');

        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Finally, some .. action!');
    }

    #[Route(method: "POST", path: "some-post-action")]
    public function somePostAction(Request $request): Response|Generator
    {
        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Finally, some POST action!');
    }

    #[Route(method: "GET", path: "some-arg-action/{name}")]
    public function someArgAction(Request $request, $name): Response|Generator
    {
        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Name: ' . $name);
    }

    // @todo Amphp Router does not support array for method

    // @todo We could simply put the route in this method and do not have the extra stuff for class attributes
    public function __invoke(Request $request, $name): Response|Generator
    {
        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Name: ' . $name);
    }

    #[Route(method: "GET", path: "middleware/{name}", middleware: [SomeMiddleware::class])]
    public function someWithMiddleware(Request $request, $name): Response|Generator
    {
        $requestTime = $request->getHeader('x-request-start');

        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Name: ' . $name . ' @' . $requestTime);
    }
}
