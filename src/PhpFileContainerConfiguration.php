<?php

namespace Thgs\Stickman;

use Auryn\Injector;

class PhpFileContainerConfiguration implements ConfiguredContainerFactoryInterface
{
    public function __construct(private string $path)
    {
        if (!is_readable($path)) {
            throw new \Exception('Path is not readable');
        }
    }

    public function getContainer(): Injector
    {
        $injector = require_once $this->path;
        if (!$injector instanceof Injector) {
            throw new \Exception('Configuration did not return an instance of Injector');
        }

        return $injector;
    }
}