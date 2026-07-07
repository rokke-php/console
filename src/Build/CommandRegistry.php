<?php

declare(strict_types=1);

namespace Rokke\Console\Build;

/**
 * Compiled artifact: a flat map from CLI command name to operation ID.
 *
 * Stored in CompiledRuntime::artifacts and consulted by ConsoleHost on every request.
 */
final class CommandRegistry
{
    /** @param array<string, string> $commands  name → operationId */
    public function __construct(private readonly array $commands)
    {
    }

    public function find(string $name): ?string
    {
        return $this->commands[$name] ?? null;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->commands);
    }

    public static function empty(): self
    {
        return new self([]);
    }
}
