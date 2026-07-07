<?php

declare(strict_types=1);

namespace Rokke\Console\Tests\Discovery;

use PHPUnit\Framework\TestCase;
use Rokke\Console\Build\ConsoleCapability;
use Rokke\Console\Discovery\ConsoleDirectoryDiscoveryProvider;
use Rokke\Runtime\Build\OperationCapability;

final class ConsoleDirectoryDiscoveryProviderTest extends TestCase
{
    public function testDiscoversCommandsFromDirectory(): void
    {
        $provider = new ConsoleDirectoryDiscoveryProvider(
            directory: __DIR__ . '/Fixture',
            namespace: 'Rokke\\Console\\Tests\\Discovery\\Fixture',
        );

        $capabilities = $provider->discover();

        $this->assertCount(2, $capabilities);

        $console   = $capabilities[0];
        $operation = $capabilities[1];

        $this->assertInstanceOf(ConsoleCapability::class, $console);
        $this->assertSame('users:create', $console->name);

        $this->assertInstanceOf(OperationCapability::class, $operation);
    }

    public function testSkipsClassesWithoutCommandAttribute(): void
    {
        $provider = new ConsoleDirectoryDiscoveryProvider(
            directory: __DIR__ . '/Fixture',
            namespace: 'Rokke\\Console\\Tests\\Discovery\\Fixture',
        );

        $capabilities = $provider->discover();
        $names        = array_map(
            fn ($c) => $c instanceof ConsoleCapability ? $c->name : null,
            $capabilities,
        );

        $this->assertNotContains('NoCommandHere', $names);
    }
}
