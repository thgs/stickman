<?php

namespace Thgs\Stickman;

use Auryn\Injector;

interface ConfiguredContainerFactoryInterface
{
    public function getContainer(): Injector;
}