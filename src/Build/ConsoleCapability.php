<?php

declare(strict_types=1);

namespace Rokke\Console\Build;

use Rokke\Contracts\Module\CapabilityInterface;

/**
 * Capability emitted by discovery for each class bearing #[Command].
 * Carries the command name and operation ID to be registered in the CommandRegistry.
 */
final readonly class ConsoleCapability implements CapabilityInterface
{
    public function __construct(
        public string $name,
        public string $operationId,
    ) {
    }
}
