<?php

declare(strict_types=1);

namespace Rokke\Console;

use Rokke\Runtime\Contracts\OperationInterface;

final readonly class ConsoleOperation implements OperationInterface
{
    public function __construct(private string $operationId)
    {
    }

    public function id(): string
    {
        return $this->operationId;
    }

    public function name(): string
    {
        return $this->operationId;
    }

    public function metadata(string $key, mixed $default = null): mixed
    {
        return $default;
    }
}
