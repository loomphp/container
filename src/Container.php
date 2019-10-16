<?php

declare(strict_types=1);

namespace Loom\Container;

use Exception as SplException;
use Psr\Container\ContainerInterface;
use function array_intersect_key;
use function array_merge;

class Container implements ContainerInterface
{
    protected $services = [];
    protected $factories = [];
    protected $aliases = [];
    protected $resolvedAliases = [];
    protected $isInitialized = false;

    public function __construct(array $parameters = [])
    {
        $this->add($parameters);
    }

    public function add(array $parameters): self
    {
        if (isset($parameters['services'])) {
            $this->services = $parameters['services'] + $this->services;
        }

        if (isset($parameters['invokables']) && ! empty($parameters['invokables'])) {
            $aliases = $this->createAliasesForInvokables($parameters['invokables']);
            $factories = $this->createFactoriesForInvokables($parameters['invokables']);

            if (! empty($aliases)) {
                $parameters['aliases'] = (isset($parameters['aliases']))
                    ? array_merge($parameters['aliases'], $aliases)
                    : $aliases;
            }

            $parameters['factories'] = (isset($parameters['factories']))
                ? array_merge($parameters['factories'], $factories)
                : $factories;
        }

        if (isset($parameters['factories'])) {
            $this->factories = $parameters['factories'] + $this->factories;
        }

        if (isset($parameters['aliases'])) {
            $this->addAliases($parameters['aliases']);
        } elseif (! $this->isInitialized && ! empty($this->aliases)) {
            $this->resolveAliases($this->aliases);
        }

        $this->isInitialized = true;

        return $this;
    }

    public function isInitialized()
    {
        return $this->isInitialized;
    }

    public function setService($id, $service): void
    {
        $this->add(['services' => [$id => $service]]);
    }

    public function setFactory($id, $factory): void
    {
        $this->add(['factories' => [$id => $factory]]);
    }

    public function setAlias($alias, $target): void
    {
        $this->add(['aliases' => [$alias => $target]]);
    }

    public function setInvokable($id, $class = null): void
    {
        $this->add(['invokables' => [$id => $class ?: $id]]);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws Exception\ContainerException Error while retrieving the entry.
     * @throws Exception\NotFoundException No entry was found for **this** identifier.
     */
    public function get($id)
    {
        $requested = $id;

        if (isset($this->services[$requested])) {
            return $this->services[$requested];
        }

        $id = isset($this->resolvedAliases[$id]) ? $this->resolvedAliases[$id] : $id;

        if ($requested !== $id && isset($this->services[$id])) {
            $this->services[$requested] = $this->services[$id];
            return $this->services[$id];
        }

        $object = $this->create($id);

        $this->services[$id] = $object;

        if ($requested !== $id) {
            $this->services[$requested] = $object;
        }

        return $object;
    }

    public function build($id, array $options = null)
    {
        $id = isset($this->resolvedAliases[$id]) ? $this->resolvedAliases[$id] : $id;
        return $this->create($id, $options);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        $id = isset($this->resolvedAliases[$id]) ? $this->resolvedAliases[$id] : $id;
        $found = isset($this->services[$id]) || isset($this->factories[$id]);

        if ($found) {
            return $found;
        }

        return false;
    }

    private function create($resolved, array $options = null)
    {
        try {
            $factory = $this->getFactory($resolved);
            $object = $factory($this, $resolved, $options);
        } catch (Exception\ContainerException $exception) {
            throw $exception;
        } catch (SplException $exception) {
            throw new Exception\NotCreatedException(sprintf(
                'Service with id "%s" could not be created. Reason: %s',
                $resolved,
                $exception->getMessage()
            ), (int)$exception->getCode(), $exception);
        }

        return $object;
    }

    private function getFactory($id): callable
    {
        $factory = isset($this->factories[$id]) ? $this->factories[$id] : null;

        $lazyLoaded = false;
        if (is_string($factory) && class_exists($factory)) {
            $factory = new $factory();
            $lazyLoaded = true;
        }

        if (is_callable($factory)) {
            if ($lazyLoaded) {
                $this->factories[$id] = $factory;
            }

            return $factory;
        }

        throw new Exception\NotFoundException(sprintf(
            'Unable to resolve service "%s" to a factory; are you certain you provided it during configuration?',
            $id
        ));
    }

    private function addAliases(array $aliases): void
    {
        if (! $this->isInitialized) {
            $this->aliases = $aliases + $this->aliases;
            $this->resolveAliases($this->aliases);

            return;
        }

        // Performance optimization. If there are no collisions, then we don't need to recompute loops
        $intersecting = $this->aliases && array_intersect_key($this->aliases, $aliases);
        $this->aliases = $this->aliases ? array_merge($this->aliases, $aliases) : $aliases;

        if ($intersecting) {
            $this->resolveAliases($this->aliases);

            return;
        }

        $this->resolveAliases($aliases);
        $this->resolveNewAliases($aliases);
    }

    private function resolveAliases(array $aliases): void
    {
        foreach ($aliases as $alias => $service) {
            $visited = [];
            $id = $alias;

            while (isset($this->aliases[$id])) {
                if (isset($visited[$id])) {
                    throw Exception\CyclicAliasException::fromAliasesMap($aliases);
                }

                $visited[$id] = true;
                $id = $this->aliases[$id];
            }

            $this->resolvedAliases[$alias] = $id;
        }
    }

    private function resolveNewAliases(array $aliases): void
    {
        foreach ($this->resolvedAliases as $id => $target) {
            if (isset($aliases[$target])) {
                $this->resolvedAliases[$id] = $this->resolvedAliases[$target];
            }
        }
    }

    private function createAliasesForInvokables(array $invokables): array
    {
        $aliases = [];
        foreach ($invokables as $id => $class) {
            if ($id === $class) {
                continue;
            }
            $aliases[$id] = $class;
        }
        return $aliases;
    }

    private function createFactoriesForInvokables(array $invokables): array
    {
        $factories = [];
        foreach ($invokables as $id => $class) {
            if ($id === $class) {
                $factories[$id] = Factory\InvokableFactory::class;
                continue;
            }

            $factories[$class] = Factory\InvokableFactory::class;
        }
        return $factories;
    }
}
