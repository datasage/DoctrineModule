<?php

declare(strict_types=1);

namespace DoctrineModuleTest\Service;

use DoctrineModule\Service\CacheFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

use function sys_get_temp_dir;
use function uniqid;

/**
 * Test for {@see \DoctrineModule\Service\CacheFactory}
 */
class CacheFactoryTest extends BaseTestCase
{
    /** @param array<string, mixed> $cacheConfig */
    private function serviceManager(array $cacheConfig): ServiceManager
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', ['doctrine' => ['cache' => ['phpunit' => $cacheConfig]]]);

        return $serviceManager;
    }

    /** @covers \DoctrineModule\Service\CacheFactory::__invoke */
    public function testCreatesPsr6PoolFromAdapterClass(): void
    {
        $factory = new CacheFactory('phpunit');

        $cache = $factory->__invoke($this->serviceManager(['class' => ArrayAdapter::class]), 'doctrine.cache.phpunit');

        $this->assertInstanceOf(ArrayAdapter::class, $cache);
        $this->assertInstanceOf(CacheItemPoolInterface::class, $cache);
    }

    /** @covers \DoctrineModule\Service\CacheFactory::__invoke */
    public function testPassesDirectoryToFilesystemAdapter(): void
    {
        $directory = sys_get_temp_dir() . '/' . uniqid('doctrine-module-cache-', true);
        $factory   = new CacheFactory('phpunit');

        $cache = $factory->__invoke(
            $this->serviceManager([
                'class' => FilesystemAdapter::class,
                'namespace' => 'DoctrineModule',
                'directory' => $directory,
            ]),
            'doctrine.cache.phpunit',
        );

        $this->assertInstanceOf(FilesystemAdapter::class, $cache);

        // Doctrine sanitises class names into PSR-6 legal keys before hitting the
        // pool; make sure such a key survives a round trip through the adapter.
        $item = $cache->getItem('DoctrineModuleTest__Entity__Foo');
        $item->set('metadata');
        $cache->save($item);

        $this->assertSame('metadata', $cache->getItem('DoctrineModuleTest__Entity__Foo')->get());
    }

    /** @covers \DoctrineModule\Service\CacheFactory::__invoke */
    public function testUsesReadyMadePoolFromInstanceService(): void
    {
        $pool           = new ArrayAdapter();
        $serviceManager = $this->serviceManager([
            'class' => FilesystemAdapter::class,
            'instance' => 'my-cache-pool',
        ]);
        $serviceManager->setService('my-cache-pool', $pool);

        $cache = (new CacheFactory('phpunit'))->__invoke($serviceManager, 'doctrine.cache.phpunit');

        $this->assertSame($pool, $cache);
    }

    /** @covers \DoctrineModule\Service\CacheFactory::__invoke */
    public function testUsesPoolRegisteredUnderAdapterClassName(): void
    {
        $pool           = new ArrayAdapter();
        $serviceManager = $this->serviceManager(['class' => ArrayAdapter::class]);
        $serviceManager->setService(ArrayAdapter::class, $pool);

        $cache = (new CacheFactory('phpunit'))->__invoke($serviceManager, 'doctrine.cache.phpunit');

        $this->assertSame($pool, $cache);
    }

    /** @covers \DoctrineModule\Service\CacheFactory::__invoke */
    public function testThrowsWhenClientBackedAdapterHasNoInstance(): void
    {
        $factory = new CacheFactory('phpunit');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires the "instance" option');

        $factory->__invoke($this->serviceManager(['class' => RedisAdapter::class]), 'doctrine.cache.phpunit');
    }
}
