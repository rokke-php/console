<?php

declare(strict_types=1);

namespace Rokke\Console\Attribute;

/**
 * Marks an invokable class as a CLI command handler.
 *
 * Usage:
 *   #[Command('users:create')]
 *   final class CreateUserCommand
 *   {
 *       public function __invoke(#[Option] string $name): string { ... }
 *   }
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Command
{
    public function __construct(public string $name)
    {
    }
}
