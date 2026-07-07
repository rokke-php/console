<?php

declare(strict_types=1);

namespace Rokke\Console\Build;

use Rokke\Contracts\Build\DefinitionInterface;

/**
 * Compile-time descriptor that maps a CLI command name to an operation ID.
 *
 * Produced by ConsoleCapabilityPass and consumed by CommandRegistryCompiler.
 */
final readonly class CommandDescriptor implements DefinitionInterface
{
    public function __construct(
        public string $name,
        public string $operationId,
    ) {
    }
}
