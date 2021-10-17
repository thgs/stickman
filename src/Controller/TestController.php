<?php

namespace Thgs\Stickman\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Generator;
use Monolog\Logger;
use Thgs\Stickman\Route;

class TestController
{
    public function __construct(private Logger $logger)
    {
    }

    #[Route(method: "GET", path: "some-action")]
    public function someAction(Request $request): Response|Generator
    {
        $this->logger->info('works here');
        
        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Finally, some .. action!'); 
    }
}