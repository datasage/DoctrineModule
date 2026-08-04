<?php

declare(strict_types=1);

namespace DoctrineModule\Options;

use Laminas\Stdlib\AbstractOptions;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Cache options
 *
 * @template-extends AbstractOptions<mixed>
 */
final class Cache extends AbstractOptions
{
    /**
     * PSR-6 cache item pool class used to instantiate the cache.
     */
    protected string $class = ArrayAdapter::class;

    /**
     * Namespace to prefix all cache ids with.
     */
    protected string $namespace = '';

    /**
     * Directory for file-based caching
     */
    protected string|null $directory = null;

    /**
     * Key used to fetch a service from the container. The service is either a
     * ready-made PSR-6 cache item pool, which is then used as-is, or the client
     * the adapter is built on top of - a \Redis or \Memcached instance for the
     * Redis and Memcached adapters respectively.
     */
    protected string|null $instance = null;

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setInstance(string $instance): self
    {
        $this->instance = $instance;

        return $this;
    }

    public function getInstance(): string|null
    {
        return $this->instance;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    public function getDirectory(): string|null
    {
        return $this->directory;
    }
}
