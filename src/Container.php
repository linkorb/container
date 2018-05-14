<?php

namespace LinkORB\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface
{
    protected $keys = [];

    public function registerService($id)
    {
        $c = $this;
        $this->keys[$id] = function ($c) use ($id) {
            return $this->instantiate($id);
        };
    }

    public function set($id, $value)
    {
        $this->keys[$id] = $value;
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            throw new ContainerException("Undefined key: " . $id);
        }
        $value = $this->keys[$id];
        if (is_callable($value)) {
            $value = $value($this);
            $this->keys[$id] = $value;
        }
        return $value;
    }

    public function has($id)
    {
        return isset($this->keys[$id]);
    }

    public function instantiate($className)
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();
        $args = [];
        if ($constructor) {
            $parameters = $constructor->getParameters();
            $args = $this->resolveArguments($parameters, $this->keys);
        }

        $obj = $reflectionClass->newInstanceArgs($args);
        return $obj;
    }

    protected function resolveArguments(array $parameters, array $context)
    {
        $args = [];
        foreach ($parameters as $parameter) {
            // Resolve by variable name
            if (isset($context[$parameter->getName()])) {
                $args[$parameter->getName()] = $context[$parameter->getName()];
            }
            $class = $parameter->getClass();
            if ($class) {
                $className = (string)$class->getName();
                // Resolve by class
                if (isset($context[$className])) {
                    $args[$parameter->getName()] = $context[$className];
                }
            }

            if (!isset($args[$parameter->getName()])) {
                throw new ContainerException("Could not resolve argument: " . $parameter->getName());
            }
        }
        return $args;
    }


    public function invoke($obj, $methodName, $extra = [])
    {
        $reflectionClass = new ReflectionClass(get_class($obj));
        if (!$reflectionClass->hasMethod($methodName)) {
            throw new ContainerException(
                "Method `" . $methodName . "` does not exist on class `" . $reflectionClass->getName() . "`"
            );
        }
        $reflectionMethod = $reflectionClass->getMethod($methodName);

        $context = array_merge($this->keys, $extra);
        $arguments = $this->resolveArguments($reflectionMethod->getParameters(), $context);
        $result = $reflectionMethod->invokeArgs($obj, $arguments);
        return $result;
    }
}
