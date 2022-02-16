<?php

namespace Thgs\Stickman;

use Monolog\Logger;
use ReflectionAttribute;
use ReflectionClass;
use Thgs\Stickman\Dispatch\DispatchCall;
use TypeError;

class RouteCollector
{
    private RouteCollection $collection;

    public function __construct(private Logger $logger)
    {
        $this->collection = new RouteCollection();
    }

    public function collectFromClass($class)
    {
        $reflection = new ReflectionClass($class);
        $classAttributes = $reflection->getAttributes(Route::class);
        $classRoutes = $this->getRouteAttributeInstances($classAttributes);
        if (!empty($classRoutes)) {
            $this->collection->addRoutes($classRoutes, new DispatchCall($class));
        }


        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);
            $methodRoutes = $this->getRouteAttributeInstances($attributes);

            if (!empty($methodRoutes)) {
                $this->collection->addRoutes($methodRoutes, new DispatchCall($class, $method->getName()));
            }
        }
        return $this;
    }

    private function getRouteAttributeInstances(array $attributes)
    {
        $instances = [];
        foreach ($attributes as $attribute) {
            if (!$attribute instanceof ReflectionAttribute) {
                throw new TypeError('Expecting ReflectionAttribute');
            }

            $instances[] = $attribute->newInstance();
        }

        return $instances;
    }

    public function getRouteCollection()
    {
        return $this->collection;
    }
}
