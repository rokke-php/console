<?php

declare(strict_types=1);

namespace Rokke\Console;

use Rokke\Console\Discovery\ConsoleDirectoryDiscoveryProvider;
use Rokke\Contracts\Module\ModuleBuilderInterface;
use Rokke\Contracts\Module\ModuleInterface;

/**
 * Registers CLI command discovery for a directory of annotated handler classes.
 *
 * Each class in the given directory bearing #[Command('name')] is discovered
 * at Build time and emits the corresponding capabilities into the application graph.
 *
 * Register this module with ConsoleKernel to wire commands into the CLI pipeline.
 */
final class ConsoleModule implements ModuleInterface
{
    public function __construct(
        private readonly string $directory,
        private readonly string $namespace,
    ) {
    }

    public function register(ModuleBuilderInterface $builder): void
    {
        $builder->addDiscoveryProvider(
            new ConsoleDirectoryDiscoveryProvider($this->directory, $this->namespace),
        );
    }
}
