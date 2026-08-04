<?php

declare(strict_types=1);

namespace DoctrineModuleTest;

use DoctrineModule\Module;
use PHPUnit\Framework\TestCase;

use function serialize;
use function unserialize;

/** @covers \DoctrineModule\Module */
class ModuleTest extends TestCase
{
    /** @covers \DoctrineModule\Module::getConfig */
    public function testGetConfig(): void
    {
        $module = new Module();

        $config = $module->getConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('doctrine', $config);
        $this->assertArrayHasKey('doctrine_factories', $config);
        $this->assertArrayHasKey('service_manager', $config);

        $this->assertSame($config, unserialize(serialize($config)));
    }
}
