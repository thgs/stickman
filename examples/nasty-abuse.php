#!/usr/bin/env php
<?php

// see example code at the bottom first for the use case
// everything in InjectorDecorators is complete abuse/hack
namespace InjectorAbuse {
    require_once \dirname(__DIR__) . "/vendor/autoload.php";

    use Amp\Injector\Arguments;
    use Amp\Injector\Definition;
    use Amp\Injector\InjectionException;
    use Amp\Injector\Injector;
    use Amp\Injector\Meta\Argument;
    use Amp\Injector\Meta\Executable;
    use Amp\Injector\Meta\Reflection\ReflectionConstructorExecutable;
    use Amp\Injector\Meta\Type;
    use Amp\Injector\Provider;
    use Amp\Injector\Provider\FactoryProvider;
    use Amp\Injector\ProviderContext;

    use function Amp\Injector\arguments;
    use function Amp\Injector\automaticTypes;
    use function Amp\Injector\types;
    use function Amp\Injector\value;

    function object(string $class, ?Arguments $arguments = null)
    {
        $executable = new ReflectionConstructorExecutable($class);
        $arguments ??= arguments();

        // return new FactoryDefinition($executable, $arguments);
        return new InspectionFactoryDefinition($executable, $arguments);

    }

    final class InspectionFactoryDefinition implements Definition
    {
        private Executable $executable;
        private Arguments $arguments;

        public function __construct(Executable $executable, Arguments $arguments)
        {
            $this->executable = $executable;
            $this->arguments = $arguments;
        }

        public function getType(): ?Type
        {
            return $this->executable->getType();
        }

        public function getAttribute(string $attribute): ?object
        {
            return $this->executable->getAttribute($attribute);
        }

        public function build(Injector $injector): Provider
        {
            global $injectorWeavers;
            // using the nasty weaver directly
            // return $injector->getExecutableProvider($this->executable, $this->arguments);
            return InspectExecutableWeaver::build($this->executable, $this->arguments->with($injectorWeavers), $injector);
        }
    }

    class InspectionProvider implements Provider
    {
        private Executable $executable;
        private array $arguments;

        public function __construct(Executable $executable, Argument ...$arguments)
        {
            $this->executable = $executable;
            $this->arguments = $arguments;
        }

        /**
         * @throws NotFoundExceptionInterface
         * @throws ContainerExceptionInterface
         */
        public function get(ProviderContext $context): mixed
        {
            try {
                $args = [];

                foreach ($this->arguments as $argument) {
                    $args[] = $argument->getProvider()->get($context->withParameter($argument->getParameter()));
                }

                // turn off this
                // return ($this->executable)(...$args);
            } catch (\Throwable $e) {
                throw new InjectionException(
                    \sprintf('Could not execute %s: %s', $this->executable, $e->getMessage()),
                    $e
                );
            }
        }

        /**
         * @return Provider|null Unwrap decorated provider, or null if none.
         */
        public function unwrap(): ?Provider
        {
            return null;
        }

        /**
         * @return array An array of providers which should be initialized first.
         */
        public function getDependencies(): array
        {
            return $this->arguments;
        }
    }


    final class InspectExecutableWeaver
    {
        public static $missingDeps = [];

        /**
         * @throws InjectionException
         */
        public static function build(Executable $executable, Arguments $arguments, Injector $injector): Provider
        {
            // could jump in here too and use a different provider
            return new FactoryProvider($executable, ...self::buildArguments($executable, $arguments, $injector));
        }

        /**
         * @param Executable $executable
         * @param Arguments $arguments
         * @param Injector $injector
         * @return Argument[]
         *
         * @throws InjectionException
         */
        private static function buildArguments(Executable $executable, Arguments $arguments, Injector $injector): array
        {
            $parameters = $executable->getParameters();
            $count = \count($parameters);
            $variadic = null;
            $args = [];

            for ($index = 0; $index < $count; $index++) {
                $parameter = $parameters[$index];

                $definition = $arguments->getDefinition($parameter);
                // could jump in here too for default values as "half-missing"
                $definition ??= $parameter->isOptional() ? value($parameter->getDefaultValue()) : null;

                if ($definition === null) {
                    $type = $parameter->getType();
                    if ($type && $type->isNullable()) {
                        $definition = value(null);
                    }
                }

                // $definition ??= throw new InjectionException('Could not find a suitable definition for ' . $parameter);
                if (!$definition) {
                    // var_dump($parameter);
                    self::$missingDeps[] = $parameter;
                    continue;
                }

                $args[$index] = new Argument($parameter, $definition->build($injector));

                if ($parameter->isVariadic()) {
                    $variadic = $parameter;
                }
            }

            // TODO
            // if ($variadic) {
            //     $variadicArguments = $this->getVariadicArguments($count - 1, $variadic);
            //     foreach ($variadicArguments as $index => $argument) {
            //         $args[$index] = $argument;
            //     }
            // }

            return $args;
        }
    }
}

// see here first
namespace {
    require_once \dirname(__DIR__) . "/vendor/autoload.php";

    use Amp\Injector\Application;
    use Amp\Injector\Injector;
    use Amp\Injector\Meta\Reflection\ReflectionFunctionParameter;
    use InjectorAbuse\InspectExecutableWeaver;

    use function Amp\Injector\any;
    use function Amp\Injector\automaticTypes;
    use function Amp\Injector\definitions;
    use function Amp\Injector\types;
    use function InjectorAbuse\object;      // using the abused version

    class KnownDependency
    {
        public function __construct(string $someValueHere)
        {

        }

    }

    class UnknownDependency
    {

    }

    class TestHandler /*implements RequestHandler*/
    {
        private KnownDependency $dep;
        private $someConfigurationValue;

        public function __construct(KnownDependency $knownDependency, UnknownDependency $unknown, int $someConfigurationValue)
        {
            $this->dep = $knownDependency;
            $this->unknown = $unknown;
            $this->someConfigurationValue = $someConfigurationValue;
        }
    }

    function sillyConfigurationName(string $className)
    {
        $name = '';
        foreach (str_split($className) as $letter) {
            if (IntlChar::isupper($letter)) {
                $name .= '_' . $letter;
                continue;
            }
            $name .= $letter;
        }
        return trim(strtoupper($name), '_');
    }

    $definitions = definitions()
        ->with(object(KnownDependency::class))
        // intentionally not specifying arguments here
        ->with(object(TestHandler::class))
    ;

    // needed for the weaver to find the known dependency
    $injectorWeavers = any(types(), automaticTypes($definitions));

    // todo: decompose the calls here?
    $application = new Application(new Injector($injectorWeavers), $definitions);

    // var_dump(count(InspectExecutableWeaver::$missingDeps));
    // pretty print

    InspectExecutableWeaver::$missingDeps = array_unique(InspectExecutableWeaver::$missingDeps);

    /** @var ReflectionFunctionParameter $missing */
    foreach (InspectExecutableWeaver::$missingDeps as $missing) {
        $type = $missing->getType() ? implode(',', $missing->getType()->getTypes()) : 'unknown type';

        $possibleConfigurationName = sillyConfigurationName($missing->getName());
        $namespace = sillyConfigurationName($missing->getDeclaringClass());
        $fullName = $namespace ? $namespace . '_' . $possibleConfigurationName : $possibleConfigurationName;

        // could expand here if there is a way to tag interfaces with implementations, so we can provide choices
        $configurable = ['int' => true, 'string' => true, 'float' => true];
        if (!empty($configurable[$type])) {
            echo $missing->__toString()  . "\t\t" . $type . "\t\t" . $fullName . PHP_EOL;
        } else {
            echo $missing->__toString()  . "\t\t" . $type . "\t\t[NOT CONFIGURABLE]" . PHP_EOL;
        }

    }
}