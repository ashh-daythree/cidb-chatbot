<?php

declare(strict_types=1);

namespace Cidb\Backend\Bootstrap;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function set(string $id, callable $factory, bool $shared = true): void
    {
        $this->bindings[$id] = [
            'factory' => $factory,
            'shared' => $shared,
        ];
    }

    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || array_key_exists($id, $this->bindings);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->bindings)) {
            if (class_exists($id)) {
                $value = $this->resolve($id);
                $this->instances[$id] = $value;

                return $value;
            }

            throw new RuntimeException(sprintf('Service "%s" is not bound in the container.', $id));
        }

        $binding = $this->bindings[$id];
        $value = ($binding['factory'])($this);

        if ($binding['shared'] === true) {
            $this->instances[$id] = $value;
        }

        return $value;
    }

    private function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Class "%s" does not exist.', $class));
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException(sprintf('Class "%s" is not instantiable.', $class));
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new RuntimeException(sprintf(
                'Unable to resolve parameter "$%s" for class "%s".',
                $parameter->getName(),
                $class
            ));
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
