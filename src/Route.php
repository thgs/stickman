<?php

namespace Thgs\Stickman;

use Attribute;

#[Attribute]
class Route
{
    public function __construct(public string $method = '', public string $path = '')
    {
    }

    // @todo do we have to use getArguments on reflection? cant we have an instance of this class?
    // @see ReflectionAttribute.newInstance
}