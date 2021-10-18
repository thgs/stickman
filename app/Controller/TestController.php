<?php

namespace App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Generator;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\Route;

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

    // Amphp Router does not support array for method

    public function __invoke(Request $request, $name): Response|Generator
    {
        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Name: ' . $name); 
    }
}