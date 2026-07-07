# rokke/console

[![CI](https://github.com/rokke-php/console/actions/workflows/ci.yml/badge.svg)](https://github.com/rokke-php/console/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/github/v/tag/rokke-php/console?label=version)](https://github.com/rokke-php/console/releases)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4-8892be)](https://www.php.net)
[![License](https://img.shields.io/github/license/rokke-php/console)](LICENSE)

Console transport adapter for the [Rokke Runtime](https://github.com/rokke-php/runtime) — routes CLI commands to compiled operations exactly as `rokke/http` routes HTTP requests.

## What this is

`rokke/console` is the CLI transport layer for the Rokke Framework. It scans a directory for classes annotated with `#[Command('name')]`, compiles them into a flat `CommandRegistry` at build time, and at runtime parses `argv` tokens into an `OperationContext` that flows through the same `ExecutionEngine` as HTTP operations.

There is no Swoole dependency — `ConsoleHost::run()` uses plain PHP I/O. The build pipeline (`ConsoleKernel`) mirrors `HttpKernel` precisely: capability → model builder pass → descriptor → compiler → artifact → host.

## Installation

```bash
composer require rokke/console
```

**Requires:** PHP ≥ 8.4, `rokke/runtime ^0.15.0`

## Usage

### 1. Create a command handler

```php
use Rokke\Console\Attribute\Command;
use Rokke\Console\Attribute\Option;

#[Command('users:create')]
final class CreateUserCommand
{
    public function __invoke(
        #[Option] string $name,
        #[Option] int $age = 0,
    ): string {
        return "Created user {$name} (age {$age})";
    }
}
```

### 2. Boot the kernel and run

```php
use Rokke\Console\ConsoleKernel;
use Rokke\Console\ConsoleModule;

(new ConsoleKernel())
    ->register(new ConsoleModule(
        directory: __DIR__ . '/Commands',
        namespace: 'App\\Commands',
    ))
    ->build()
    ->run(); // reads $_SERVER['argv'] automatically
```

### 3. Invoke from the terminal

```bash
php app users:create --name=Fernando --age=28
# Created user Fernando (age 28)
```

## API reference

### Attributes

| Attribute | Target | Purpose |
|-----------|--------|---------|
| `#[Command('name')]` | class | Marks an invokable class as a CLI command |
| `#[Option]` | parameter | Resolves a handler parameter from `--key=value` or `--flag` |
| `#[Option(short: 'n')]` | parameter | Also accepts `-n value` short form |

### Key classes

| Class | Purpose |
|-------|---------|
| `ConsoleKernel` | Build entry point — discovers, compiles, produces `ConsoleHost` |
| `ConsoleModule` | Module that wires `ConsoleDirectoryDiscoveryProvider` into the graph |
| `ConsoleHost` | Runtime entry point — parses argv, dispatches via `ExecutionEngine` |
| `CommandRegistry` | Compiled artifact — flat map of `name → operationId` |
| `ConsoleContextFactory` | Parses raw argv tokens into `OperationContext` |

## When to use

Use `rokke/console` when you need to route CLI commands through the same compiled operation pipeline as HTTP handlers — sharing middleware, validation, and result plans between HTTP and CLI surfaces.

For one-off scripts with no need for the operation pipeline, use plain PHP directly.

## Stability

`0.x` — API may evolve as the framework matures. No breaking changes within a minor version.

## License

MIT
