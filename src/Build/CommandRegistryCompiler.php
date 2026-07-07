<?php

declare(strict_types=1);

namespace Rokke\Console\Build;

final class CommandRegistryCompiler
{
    /** @param list<CommandDescriptor> $descriptors */
    public function compile(array $descriptors): CommandRegistry
    {
        $commands = [];

        foreach ($descriptors as $descriptor) {
            $commands[$descriptor->name] = $descriptor->operationId;
        }

        return new CommandRegistry($commands);
    }
}
