<?php

declare(strict_types=1);

namespace DoctrineModuleTest;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\ModuleManager\Feature\ServiceProviderInterface;
use Laminas\ModuleManager\Listener\DefaultListenerAggregate;
use Laminas\ModuleManager\Listener\ListenerOptions;
use Laminas\ModuleManager\Listener\ServiceListener;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\ServiceManager\ServiceManager;

/**
 * Base test case to be used when a service manager instance is required
 *
 * The module manager is wired up directly here rather than through
 * laminas-mvc, which DoctrineModule does not depend on.
 */
class ServiceManagerFactory
{
    /** @return mixed[] */
    public static function getConfiguration(): array
    {
        return include __DIR__ . '/TestConfiguration.php';
    }

    /**
     * Retrieves a new ServiceManager instance
     *
     * @param mixed[]|null $configuration
     */
    public static function getServiceManager(array|null $configuration = null): ServiceManager
    {
        $configuration = $configuration ?: static::getConfiguration();

        $serviceManager = new ServiceManager();
        $serviceManager->setService('ApplicationConfig', $configuration);

        $sharedEvents = new SharedEventManager();
        $events       = new EventManager($sharedEvents);

        // laminas-mvc registers these, and CliFactory resolves "EventManager"
        // from the container. Listeners such as the ORM and ODM modules'
        // loadCli.post handlers attach to the shared manager during init(), so
        // it has to be the same instance the module manager uses. EventManager
        // is not shared, matching laminas-mvc's ServiceManagerConfig.
        $serviceManager->setService('SharedEventManager', $sharedEvents);
        $serviceManager->setFactory(
            'EventManager',
            static fn (): EventManager => new EventManager($sharedEvents),
        );
        $serviceManager->setShared('EventManager', false);

        $serviceListener = new ServiceListener($serviceManager);
        $serviceListener->addServiceManager(
            $serviceManager,
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig',
        );

        // Modules such as Laminas\Form register their own plugin managers by
        // pulling the "ServiceListener" service during init().
        $serviceManager->setService('ServiceListener', $serviceListener);

        $defaultListeners = new DefaultListenerAggregate(
            new ListenerOptions($configuration['module_listener_options'] ?? []),
        );
        $defaultListeners->attach($events);
        $serviceListener->attach($events);

        $moduleEvent = new ModuleEvent();
        $moduleEvent->setParam('ServiceManager', $serviceManager);

        $moduleManager = new ModuleManager($configuration['modules'], $events);
        $moduleManager->setEvent($moduleEvent);
        $moduleManager->loadModules();

        // laminas-mvc would normally expose the merged module configuration
        // as the "config" service; do the same here.
        $serviceManager->setService(
            'config',
            $moduleEvent->getConfigListener()->getMergedConfig(false),
        );

        return $serviceManager;
    }
}
