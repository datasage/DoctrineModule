<?php

declare(strict_types=1);

namespace DoctrineModule;

use Laminas\Authentication\Storage\Session as LaminasSessionStorage;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Config provider for DoctrineORMModule config
 */
final class ConfigProvider
{
    /** @return array<non-empty-string, mixed[]> */
    public function __invoke(): array
    {
        return [
            'doctrine' => $this->getDoctrineConfig(),
            'doctrine_factories' => $this->getDoctrineFactoryConfig(),
            'dependencies' => $this->getDependencyConfig(),
            'validators' => $this->getValidatorConfig(),
        ];
    }

    /**
     * Return application-level dependency configuration
     *
     * @return array<non-empty-string, array<non-empty-string, class-string>>
     */
    public function getDependencyConfig(): array
    {
        return [
            'invokables' => ['DoctrineModule\Authentication\Storage\Session' => LaminasSessionStorage::class],
            'factories' => ['doctrine.cli' => Service\CliFactory::class],
            'abstract_factories' => ['DoctrineModule' => ServiceFactory\AbstractDoctrineServiceFactory::class],
        ];
    }

    /**
     * Default configuration for Doctrine module
     *
     * @return array<non-empty-string, mixed[]>
     */
    public function getDoctrineConfig(): array
    {
        return [
            'cache' => $this->getDoctrineCacheConfig(),

            //These authentication settings are a hack to tide things over until version 1.0
            //Normall doctrineModule should have no mention of odm or orm
            'authentication' => [
                //default authentication options should be set in either the odm or orm modules
                'odm_default' => [],
                'orm_default' => [],
            ],
            'authenticationadapter' => [
                'odm_default' => true,
                'orm_default' => true,
            ],
            'authenticationstorage' => [
                'odm_default' => true,
                'orm_default' => true,
            ],
            'authenticationservice' => [
                'odm_default' => true,
                'orm_default' => true,
            ],
        ];
    }

    /**
     * Factory mappings - used to define which factory to use to instantiate a particular doctrine service type
     *
     * @return array<non-empty-string, class-string>
     */
    public function getDoctrineFactoryConfig(): array
    {
        return [
            'cache' => Service\CacheFactory::class,
            'eventmanager' => Service\EventManagerFactory::class,
            'driver' => Service\DriverFactory::class,
            'authenticationadapter' => Service\Authentication\AdapterFactory::class,
            'authenticationstorage' => Service\Authentication\StorageFactory::class,
            'authenticationservice' => Service\Authentication\AuthenticationServiceFactory::class,
        ];
    }

    /** @return array<non-empty-string, mixed[]> */
    public function getValidatorConfig(): array
    {
        return [
            'aliases' => [
                'DoctrineNoObjectExists' => Validator\NoObjectExists::class,
                'DoctrineObjectExists' => Validator\ObjectExists::class,
                'DoctrineUniqueObject' => Validator\UniqueObject::class,
            ],
            'factories' => [
                Validator\NoObjectExists::class => Validator\Service\NoObjectExistsFactory::class,
                Validator\ObjectExists::class => Validator\Service\ObjectExistsFactory::class,
                Validator\UniqueObject::class => Validator\Service\UniqueObjectFactory::class,
            ],
        ];
    }

    /**
     * PSR-6 cache item pools, provided by symfony/cache.
     *
     * The memcached and redis adapters are built on top of a connected client,
     * so their "instance" option must name a container service holding a
     * \Memcached or \Redis instance respectively. Alternatively, "instance" may
     * name a service holding a ready-made PSR-6 pool, which is then used as-is.
     *
     * @return array<non-empty-string,array{class:class-string,instance?:string,namespace?:string,directory?:string}>
     */
    private function getDoctrineCacheConfig(): array
    {
        return [
            'apcu' => [
                'class' => ApcuAdapter::class,
                'namespace' => 'DoctrineModule',
            ],
            'array' => ['class' => ArrayAdapter::class],
            'filesystem' => [
                'class' => FilesystemAdapter::class,
                'namespace' => 'DoctrineModule',
                'directory' => 'data/DoctrineModule/cache',
            ],
            'memcached' => [
                'class' => MemcachedAdapter::class,
                'namespace' => 'DoctrineModule',
                'instance' => 'doctrinemodule.cache.memcached_client',
            ],
            'redis' => [
                'class' => RedisAdapter::class,
                'namespace' => 'DoctrineModule',
                'instance' => 'doctrinemodule.cache.redis_client',
            ],
        ];
    }
}
