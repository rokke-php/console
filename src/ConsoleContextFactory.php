<?php

declare(strict_types=1);

namespace Rokke\Console;

use Rokke\Runtime\Context\OperationContext;

/**
 * Parses a raw argv token list into an OperationContext.
 *
 * Supported token formats:
 *   --key=value   → options['key'] = 'value'
 *   --flag        → options['flag'] = '1'
 *   -k            → options['k']   = '1'  (short flags)
 *   positional    → args[]
 *
 * The first element of the argv passed to ConsoleHost::handle() is
 * the command name and is NOT included here.
 */
final class ConsoleContextFactory
{
    /**
     * @param list<string> $argv  tokens after the command name
     */
    public function make(array $argv): OperationContext
    {
        ['options' => $options, 'args' => $args] = $this->parse($argv);

        return new OperationContext(
            id: uniqid('cli-', true),
            metadata: [
                'options' => $options,
                'args'    => $args,
                'argv'    => $argv,
            ],
        );
    }

    /**
     * @param list<string> $tokens
     * @return array{options: array<string, string>, args: list<string>}
     */
    private function parse(array $tokens): array
    {
        $options = [];
        $args    = [];

        foreach ($tokens as $token) {
            if (str_starts_with($token, '--')) {
                $raw = substr($token, 2);

                if (str_contains($raw, '=')) {
                    [$key, $value]  = explode('=', $raw, 2);
                    $options[$key]  = $value;
                } else {
                    $options[$raw] = '1';
                }
            } elseif (str_starts_with($token, '-') && strlen($token) === 2) {
                $options[substr($token, 1)] = '1';
            } else {
                $args[] = $token;
            }
        }

        return ['options' => $options, 'args' => $args];
    }
}
