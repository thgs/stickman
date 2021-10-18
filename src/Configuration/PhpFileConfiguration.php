<?php

namespace Thgs\Stickman\Configuration;

class PhpFileConfiguration
{
    public function __construct(private string $phpFile, private ?string $typeCheck = null)
    {
        if (!is_readable($phpFile)) {
            throw new \Exception('PHP file is not readable');
        }
    }

    public function get()
    {
        $value = require_once $this->phpFile;
        if (!$this->typeCheck($value)) {
            throw new \Exception("Configuration did not return an instance of $typeCheck");
        }

        return $value;
    }

    private function typeCheck($value): bool
    {
        if (!$this->typeCheck) {
            return true;
        }

        if ($this->typeCheck === 'int' || $this->typeCheck === 'integer') {
            return is_int($value);
        }

        if ($this->typeCheck === 'string') {
            return is_string($value);
        }

        if ($this->typeCheck === 'float') {
            return is_float($value);
        }

        if ($this->typeCheck === 'bool' || $this->typeCheck === 'boolean') {
            return is_bool($value);
        }

        if ($this->typeCheck === 'array') {
            return is_array($value);
        }

        if (false !== strpos($this->typeCheck, '[]')) {
            if (!is_iterable($value)) {
                return false;
            }

            $typeCheck = str_replace('[]', '', $this->typeCheck);
            foreach ($value as $v) {
                if (!$this->typeCheck($v)) {
                    return false;
                }
            }

            return true;
        }

        if (class_exists($this->typeCheck)) {
            return $value instanceof $this->typeCheck;
        }

        throw new \Exception('Unknown type passed to type check against');
    }
}