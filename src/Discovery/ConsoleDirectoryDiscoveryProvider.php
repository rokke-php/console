<?php

declare(strict_types=1);

namespace Rokke\Console\Discovery;

use Rokke\Console\Attribute\Command;
use Rokke\Console\Build\ConsoleCapability;
use Rokke\Contracts\Module\CapabilityInterface;
use Rokke\Contracts\Module\DiscoveryProviderInterface;
use Rokke\Runtime\Build\OperationCapability;

/**
 * Scans a directory for invokable classes annotated with #[Command] and
 * emits the corresponding capabilities.
 *
 * For each class bearing #[Command('name')], two capabilities are emitted:
 *   - ConsoleCapability   — registers the command name in the CommandRegistry
 *   - OperationCapability — registers the invokable handler for execution
 *
 * Classes without a #[Command] attribute are silently skipped.
 */
final class ConsoleDirectoryDiscoveryProvider implements DiscoveryProviderInterface
{
    public function __construct(
        private readonly string $directory,
        private readonly string $namespace,
    ) {
    }

    /** @return list<CapabilityInterface> */
    public function discover(): array
    {
        $capabilities = [];

        foreach ($this->phpFiles() as $file) {
            $class = $this->classFromFile($file);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $attrs      = $reflection->getAttributes(Command::class);

            if ($attrs === []) {
                continue;
            }

            if (!$reflection->hasMethod('__invoke') || !$reflection->getMethod('__invoke')->isPublic()) {
                throw new \InvalidArgumentException(
                    "Command handler {$class} does not declare a public __invoke() method.",
                );
            }

            $attr        = $attrs[0]->newInstance();
            $operationId = $this->operationId($class);

            $capabilities[] = new ConsoleCapability($attr->name, $operationId);
            $capabilities[] = new OperationCapability($operationId, $operationId, $class);
        }

        return $capabilities;
    }

    /** @return \Traversable<string> */
    private function phpFiles(): \Traversable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }

    private function classFromFile(string $filePath): string
    {
        $relative        = substr($filePath, strlen($this->directory) + 1);
        $withoutExtension = substr($relative, 0, -4);
        $namespaceSuffix = str_replace(['/', '\\'], '\\', $withoutExtension);

        return rtrim($this->namespace, '\\') . '\\' . $namespaceSuffix;
    }

    /** @param class-string $class */
    private function operationId(string $class): string
    {
        $pos = strrpos($class, '\\');

        return $pos === false ? $class : substr($class, $pos + 1);
    }
}
