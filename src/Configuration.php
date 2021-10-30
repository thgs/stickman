<?php

namespace Thgs\Stickman;

use Amp\Http\Server\Options;

class Configuration
{
    public const DEFAULT_LOGNAME = 'server';

    public function __construct(
        public HandlersCollection $handlers,
        public ServerCollection $servers,
        public ?Options $options = null,
        public $logName = self::DEFAULT_LOGNAME
    ) {
    }
}
