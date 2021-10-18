<?php

namespace Thgs\Stickman;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Generator;

interface DispatcherInterface
{
    public function __invoke(Request $request): Response|Generator;
}