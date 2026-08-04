<?php

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\ModuleManager\Feature\ServiceProviderInterface;
use Laminas\ModuleManager\Listener\DefaultListenerAggregate;
use Laminas\ModuleManager\Listener\ListenerOptions;
use Laminas\ModuleManager\Listener\ServiceListener;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;

ini_set('display_errors', true);
chdir(__DIR__);

$previousDir = '.';

while (!file_exists('config/application.config.php')) {
    $dir = dirname(getcwd());

    if ($previousDir === $dir) {
        throw new RuntimeException(
            'Unable to locate "config/application.config.php": ' .
            'is DoctrineModule in a subdir of your application skeleton?'
        );
    }

    $previousDir = $dir;
    chdir($dir);
}


if (is_readable('init_autoloader.php')) {
    include_once 'init_autoloader.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
    include_once __DIR__ . '/../../../autoload.php';
} else {
    throw new RuntimeException('Error: vendor/autoload.php could not be found. Did you run php composer.phar install?');
}

$appConfig = include 'config/application.config.php';
if (file_exists('config/development.config.php')) {
    $appConfig = ArrayUtils::merge($appConfig, include 'config/development.config.php');
}

if (class_exists(Application::class)) {
    // laminas-mvc is installed, so bootstrap the full application. This runs
    // every module's onBootstrap() listener, which is what this script has
    // always done and what modules registering event subscribers rely on.
    $serviceManager = Application::init($appConfig)->getServiceManager();
} else {
    // laminas-mvc is optional, and has no PHP 8.5 release. Wire the module
    // manager directly so the CLI keeps working without it.
    //
    // Note that no MVC bootstrap happens on this path, so module onBootstrap()
    // listeners are NOT invoked. Modules that register Doctrine event
    // subscribers there must do so from getConfig() or init() instead.
    $serviceManager = new ServiceManager();
    $serviceManager->setService('ApplicationConfig', $appConfig);

    $events = new EventManager(new SharedEventManager());

    $serviceListener = new ServiceListener($serviceManager);
    $serviceListener->addServiceManager(
        $serviceManager,
        'service_manager',
        ServiceProviderInterface::class,
        'getServiceConfig'
    );
    $serviceManager->setService('ServiceListener', $serviceListener);

    $defaultListeners = new DefaultListenerAggregate(
        new ListenerOptions($appConfig['module_listener_options'] ?? [])
    );
    $defaultListeners->attach($events);
    $serviceListener->attach($events);

    $moduleEvent = new ModuleEvent();
    $moduleEvent->setParam('ServiceManager', $serviceManager);

    $moduleManager = new ModuleManager($appConfig['modules'] ?? [], $events);
    $moduleManager->setEvent($moduleEvent);
    $moduleManager->loadModules();

    $serviceManager->setService('config', $moduleEvent->getConfigListener()->getMergedConfig(false));
}

/* @var $cli \Symfony\Component\Console\Application */
$cli = $serviceManager->get('doctrine.cli');
exit($cli->run());
