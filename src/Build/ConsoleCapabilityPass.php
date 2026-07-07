<?php

declare(strict_types=1);

namespace Rokke\Console\Build;

use Rokke\Contracts\Module\CapabilityInterface;
use Rokke\Runtime\Build\ApplicationModel;
use Rokke\Runtime\Build\ModelBuilderPassInterface;

/**
 * Converts ConsoleCapability instances into CommandDescriptor definitions
 * so the CommandRegistryCompiler can build the final CommandRegistry artifact.
 */
final class ConsoleCapabilityPass implements ModelBuilderPassInterface
{
    /** @param list<CapabilityInterface> $capabilities */
    public function process(array $capabilities, ApplicationModel $model): void
    {
        foreach ($capabilities as $capability) {
            if (!$capability instanceof ConsoleCapability) {
                continue;
            }

            $model->add(new CommandDescriptor(
                name: $capability->name,
                operationId: $capability->operationId,
            ));
        }
    }
}
