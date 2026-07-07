<?php

declare(strict_types=1);

namespace Rokke\Console\Attribute;

/**
 * Binds a handler parameter to a CLI option (--key=value or --flag).
 *
 * Usage:
 *   public function __invoke(
 *       #[Option] string $name,           // resolved from --name=Fernando
 *       #[Option('n')] string $name,      // also accepts -n Fernando (short form)
 *       #[Option] bool $verbose = false,  // flag: --verbose sets to true
 *   ): void {}
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Option
{
    /**
     * @param string|null $name    Long option name override. Defaults to parameter name.
     * @param string|null $short   Optional single-character short alias (e.g. 'n' for -n).
     */
    public function __construct(
        public ?string $name = null,
        public ?string $short = null,
    ) {
    }
}
