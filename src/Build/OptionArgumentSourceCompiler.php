<?php

declare(strict_types=1);

namespace Rokke\Console\Build;

use ReflectionNamedType;
use ReflectionParameter;
use Rokke\Console\Attribute\Option;
use Rokke\Runtime\Build\ArgumentSourceCompilerInterface;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;

/**
 * Compiles #[Option]-annotated parameters into OptionInstructions.
 */
final class OptionArgumentSourceCompiler implements ArgumentSourceCompilerInterface
{
    public function compile(ReflectionParameter $param, FactoryRepository $factories): ?ArgumentInstructionInterface
    {
        $attrs = $param->getAttributes(Option::class);

        if ($attrs === []) {
            return null;
        }

        $attr     = $attrs[0]->newInstance();
        $key      = $attr->name ?? $param->getName();
        $type     = $param->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'string';
        $nullable = !$type instanceof ReflectionNamedType || $type->allowsNull();
        $default  = $param->isOptional() && $param->isDefaultValueAvailable()
            ? $param->getDefaultValue()
            : null;

        return new OptionInstruction(
            key: $key,
            short: $attr->short,
            type: $typeName,
            nullable: $nullable,
            default: $default,
        );
    }
}
