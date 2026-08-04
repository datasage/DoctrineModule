<?php

declare(strict_types=1);

namespace DoctrineModuleTest\ServiceFactory;

use DoctrineModuleTest\Service\TestAsset\DummyCliCommand;
use DoctrineModuleTest\ServiceManagerFactory;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;

use function assert;
use function method_exists;

/**
 * Test that verifies that services are defined correctly
 */
class ModuleDefinedServicesTest extends TestCase
{
    protected ServiceLocatorInterface $serviceManager;

    protected function setUp(): void
    {
        $this->serviceManager = ServiceManagerFactory::getServiceManager();
    }

    /**
     * The CLI application is built by CliFactory, which pulls "EventManager"
     * from the container and triggers loadCli.post on it. The ORM and ODM
     * modules attach to that event on the shared manager during init(), so this
     * covers the whole path: the service resolving at all, and a shared
     * listener actually being reached.
     */
    public function testCliApplicationIsBuiltAndReachesSharedListeners(): void
    {
        $sharedEvents = $this->serviceManager->get('SharedEventManager');
        assert($sharedEvents instanceof SharedEventManagerInterface);

        $sharedEvents->attach(
            'doctrine',
            'loadCli.post',
            static function (EventInterface $event): void {
                $target = $event->getTarget();
                if (! $target instanceof Application) {
                    return;
                }

                if (method_exists($target, 'addCommand')) {
                    $target->addCommand(new DummyCliCommand());

                    return;
                }

                $target->add(new DummyCliCommand());
            },
        );

        $cli = $this->serviceManager->get('doctrine.cli');

        $this->assertInstanceOf(Application::class, $cli);
        $this->assertTrue($cli->has('app:dummy-command'));
    }

    /**
     * Verifies that the module defines the correct services
     *
     * @dataProvider getServicesThatShouldBeDefined
     */
    public function testModuleDefinedServices(string $serviceName, bool $defined): void
    {
        $this->assertSame($defined, $this->serviceManager->has($serviceName));
    }

    /**
     * Verifies that the module defines the correct services
     *
     * @dataProvider getServicesThatCanBeFetched
     */
    public function testModuleFetchedService(string $serviceName, string $expectedClass): void
    {
        $this->assertInstanceOf($expectedClass, $this->serviceManager->get($serviceName));
    }

    /**
     * Verifies that the module defines the correct services
     *
     * @dataProvider getServicesThatCannotBeFetched
     */
    public function testModuleInvalidService(string $serviceName): void
    {
        $this->expectException(ServiceNotFoundException::class);

        $this->serviceManager->get($serviceName);
    }

    /** @return mixed[][] */
    public static function getServicesThatShouldBeDefined(): array
    {
        return [
            ['doctrine.cache.array', true],
            ['doctrine.cache.apcu', true],
            ['doctrine.cache.filesystem', true],
            ['doctrine.cache.memcached', true],
            ['doctrine.cache.redis', true],
            ['doctrine.authenticationadapter.orm_default', true],
            ['doctrine.authenticationstorage.orm_default', true],
            ['doctrine.authenticationservice.orm_default', true],
            ['doctrine.authenticationadapter.odm_default', true],
            ['doctrine.authenticationstorage.odm_default', true],
            ['doctrine.authenticationservice.odm_default', true],
            ['foo', false],
            ['foo.bar', false],
            ['foo.bar.baz', false],
            ['doctrine', false],
            ['doctrine.foo', false],
            ['doctrine.foo.bar', false],
            ['doctrine.cache.bar', false],
        ];
    }

    /** @return string[][] */
    public static function getServicesThatCanBeFetched(): array
    {
        return [
            ['doctrine.cache.array', ArrayAdapter::class],
            ['doctrine.cache.filesystem', FilesystemAdapter::class],
        ];
    }

    /** @return string[][] */
    public static function getServicesThatCannotBeFetched(): array
    {
        return [
            ['foo'],
            ['foo.bar'],
            ['foo.bar.baz'],
            ['doctrine'],
            ['doctrine.foo'],
            ['doctrine.foo.bar'],
            ['doctrine.cache.bar'],
        ];
    }
}
