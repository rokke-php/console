<?php

declare(strict_types=1);

namespace Rokke\Console\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Console\ConsoleKernel;
use Rokke\Console\ConsoleExtension;
use RuntimeException;

/**
 * Integration tests for the full Console pipeline:
 *   ConsoleExtension → ConsoleKernel::build() → ConsoleHost::handle()
 *
 * Uses the fixture commands in tests/Discovery/Fixture/.
 */
final class ConsoleHostTest extends TestCase
{
    private function kernel(): ConsoleKernel
    {
        return (new ConsoleKernel())
            ->register(new ConsoleExtension(
                directory: __DIR__ . '/Discovery/Fixture',
                namespace: 'Rokke\\Console\\Tests\\Discovery\\Fixture',
            ))
            ->build();
    }

    public function testHandleDispatchesCommandAndResolvesOptions(): void
    {
        $result = $this->kernel()->host()->handle(['users:create', '--name=Fernando']);

        $this->assertSame('Created user Fernando (age 0)', $result);
    }

    public function testHandleResolvesMultipleOptions(): void
    {
        $result = $this->kernel()->host()->handle(['users:create', '--name=Ana', '--age=28']);

        $this->assertSame('Created user Ana (age 28)', $result);
    }

    public function testOptionTypeCoercionToInt(): void
    {
        $result = $this->kernel()->host()->handle(['users:create', '--name=Bob', '--age=42']);

        $this->assertSame('Created user Bob (age 42)', $result);
    }

    public function testMissingRequiredOptionThrows(): void
    {
        $this->expectException(RuntimeException::class);

        // --name is required (no default)
        $this->kernel()->host()->handle(['users:create']);
    }

    public function testUnknownCommandThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/unknown command/i');

        $this->kernel()->host()->handle(['does:not:exist']);
    }

    public function testEmptyArgvThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $this->kernel()->host()->handle([]);
    }

    public function testKernelThrowsIfHostCalledBeforeBuild(): void
    {
        $this->expectException(RuntimeException::class);

        (new ConsoleKernel())->host();
    }
}
