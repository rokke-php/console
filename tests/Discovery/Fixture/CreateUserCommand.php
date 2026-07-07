<?php

declare(strict_types=1);

namespace Rokke\Console\Tests\Discovery\Fixture;

use Rokke\Console\Attribute\Command;
use Rokke\Console\Attribute\Option;

#[Command('users:create')]
final class CreateUserCommand
{
    public function __invoke(
        #[Option]
        string $name,
        #[Option]
        int $age = 0,
    ): string {
        return "Created user {$name} (age {$age})";
    }
}
