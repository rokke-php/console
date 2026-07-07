<?php

declare(strict_types=1);

namespace Rokke\Console\Build;

use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;
use Rokke\Runtime\Contracts\OperationContextInterface;
use RuntimeException;

/**
 * Resolves a handler parameter from a parsed CLI option (--key=value or --flag).
 *
 * Reads from OperationContext::metadata('options'), which is a pre-parsed
 * array<string, string> produced by ConsoleContextFactory.
 */
final readonly class OptionInstruction implements ArgumentInstructionInterface
{
    public function __construct(
        private string $key,
        private ?string $short,
        private string $type,
        private bool $nullable,
        private mixed $default,
    ) {
    }

    public function resolve(OperationContextInterface $context): mixed
    {
        /** @var array<string, string> $options */
        $options = $context->metadata('options') ?? [];

        $value = $options[$this->key] ?? ($this->short !== null ? ($options[$this->short] ?? null) : null);

        if ($value === null) {
            if ($this->default !== null) {
                return $this->default;
            }

            if ($this->nullable) {
                return null;
            }

            throw new RuntimeException(
                "Required option '--{$this->key}' is missing.",
            );
        }

        return $this->coerce($value);
    }

    private function coerce(string $value): mixed
    {
        return match ($this->type) {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => $value === '1' || strtolower($value) === 'true',
            default => $value,
        };
    }
}
