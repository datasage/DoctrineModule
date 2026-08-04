<?php

declare(strict_types=1);

namespace DoctrineModule\Service;

use DoctrineModule\Options\Cache as CacheOptions;
use Memcached;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Redis;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

use function get_debug_type;
use function is_a;
use function is_string;
use function sprintf;

/**
 * Cache ServiceManager factory
 *
 * Builds the PSR-6 cache item pools exposed as "doctrine.cache.*".
 */
final class CacheFactory extends AbstractFactory
{
    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array|null $options = null,
    ): CacheItemPoolInterface {
        $options = $this->getOptions($container, 'cache');

        if (! $options instanceof CacheOptions) {
            throw new RuntimeException(sprintf(
                'Invalid options received, expected %s, got %s.',
                CacheOptions::class,
                $options::class,
            ));
        }

        $class = $options->getClass();

        if (! $class) {
            throw new RuntimeException('Cache must have a class name to instantiate');
        }

        $instance = $options->getInstance();

        if (is_string($instance) && $container->has($instance)) {
            $instance = $container->get($instance);
        }

        // The application may supply a fully built pool, either under the
        // configured instance key or under the adapter class name itself.
        if ($instance instanceof CacheItemPoolInterface) {
            return $instance;
        }

        if ($container->has($class)) {
            return $this->assertCacheItemPool($container->get($class), $class);
        }

        $namespace = $options->getNamespace();

        // Redis and Memcached are built on top of a connected client, which
        // only the application can provide.
        if (is_a($class, RedisAdapter::class, true) || is_a($class, MemcachedAdapter::class, true)) {
            if (! $instance instanceof Redis && ! $instance instanceof Memcached) {
                throw new RuntimeException(sprintf(
                    'Cache adapter "%s" requires the "instance" option to name a container service holding a '
                    . '%s client, %s given.',
                    $class,
                    is_a($class, RedisAdapter::class, true) ? Redis::class : Memcached::class,
                    get_debug_type($instance),
                ));
            }

            $cache = new $class($instance, $namespace);
        } elseif (is_a($class, FilesystemAdapter::class, true)) {
            $cache = new $class($namespace, 0, $options->getDirectory());
        } elseif (is_a($class, ApcuAdapter::class, true)) {
            $cache = new $class($namespace);
        } else {
            $cache = new $class();
        }

        return $this->assertCacheItemPool($cache, $class);
    }

    public function getOptionsClass(): string
    {
        return CacheOptions::class;
    }

    /** @throws RuntimeException */
    private function assertCacheItemPool(mixed $cache, string $class): CacheItemPoolInterface
    {
        if (! $cache instanceof CacheItemPoolInterface) {
            throw new RuntimeException(sprintf(
                'Cache "%s" must implement %s, %s given.',
                $class,
                CacheItemPoolInterface::class,
                get_debug_type($cache),
            ));
        }

        return $cache;
    }
}
