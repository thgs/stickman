<?php

namespace App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Generator;
use Psr\Log\LoggerInterface;
use Thgs\Stickman\Route;

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
}