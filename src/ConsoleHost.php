<?php

declare(strict_types=1);

namespace Rokke\Console;

use Rokke\Console\Build\CommandRegistry;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Engine\ExecutionEngine;
use Rokke\Runtime\Engine\Invoker;
use RuntimeException;

/**
 * Dispatches a CLI command to the compiled operation pipeline.
 *
 * Mirrors HttpHost but for the console transport:
 *   1. Extract the command name from argv[0]
 *   2. Look up the operation ID in the CommandRegistry
 *   3. Parse remaining tokens into an OperationContext
 *   4. Execute via ExecutionEngine
 *
 * Usage (within an entry-point script):
 *   (new ConsoleKernel())
 *       ->register(new ConsoleModule(__DIR__ . '/commands', 'App\\Commands'))
 *       ->build()
 *       ->run();            // reads $_SERVER['argv'] automatically
 */
final class ConsoleHost
{
    private readonly CommandRegistry $registry;
    private readonly ExecutionEngine $engine;
    private readonly ConsoleContextFactory $contextFactory;

    public function __construct(CompiledRuntime $runtime)
    {
        $this->registry       = $runtime->artifacts->get(CommandRegistry::class) ?? CommandRegistry::empty();
        $this->engine         = new ExecutionEngine(new Invoker($runtime), runtime: $runtime);
        $this->contextFactory = new ConsoleContextFactory();
    }

    /**
     * @param list<string> $argv  e.g. ['users:create', '--name=Fernando']
     */
    public function handle(array $argv): mixed
    {
        if ($argv === []) {
            throw new RuntimeException('No command given. Usage: php <script> <command> [options]');
        }

        $commandName = array_shift($argv);
        $operationId = $this->registry->find($commandName);

        if ($operationId === null) {
            throw new RuntimeException(
                "Unknown command: {$commandName}. Available: " . implode(', ', $this->registry->names()),
            );
        }

        $operation = new ConsoleOperation($operationId);
        $context   = $this->contextFactory->make($argv);

        return $this->engine->execute($operation, $context);
    }

    /**
     * Entry point for CLI scripts — reads and strips the script name from $_SERVER['argv'].
     *
     * @param list<string>|null $argv  override for testing; defaults to $_SERVER['argv']
     */
    public function run(?array $argv = null): void
    {
        /** @var list<string> $rawArgv */
        $rawArgv = $argv ?? ($_SERVER['argv'] ?? []);
        $args    = array_slice($rawArgv, 1); // strip script name

        try {
            $result = $this->handle($args);

            if ($result !== null) {
                echo $result . PHP_EOL;
            }
        } catch (RuntimeException $e) {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }
    }
}
