<?php

namespace Thgs\Stickman;

use Attribute;

#[Attribute]
class Route
{
    public function __construct(public string $method = '', public string $path = '')
    {
    }
}