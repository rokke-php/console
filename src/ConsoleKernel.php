<?php

declare(strict_types=1);

namespace Rokke\Console;

use Rokke\Console\Build\CommandDescriptor;
use Rokke\Console\Build\CommandRegistryCompiler;
use Rokke\Console\Build\ConsoleCapabilityPass;
use Rokke\Console\Build\CommandRegistry;
use Rokke\Console\Build\OptionArgumentSourceCompiler;
use Rokke\Contracts\Extension\ExtensionInterface;
use Rokke\Runtime\Build\ArgumentPlanCompiler;
use Rokke\Runtime\Build\DiscoveryEngine;
use Rokke\Runtime\Build\FactoryCompiler;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Build\MaxValidationSourceCompiler;
use Rokke\Runtime\Build\MinValidationSourceCompiler;
use Rokke\Runtime\Build\ModelBuilder;
use Rokke\Runtime\Build\NotBlankValidationSourceCompiler;
use Rokke\Runtime\Build\OperationDefinition;
use Rokke\Runtime\Build\OperationModelBuilderPass;
use Rokke\Runtime\Build\ResultPlanCompiler;
use Rokke\Runtime\Build\ServiceDescriptor;
use Rokke\Runtime\Build\ServiceModelBuilderPass;
use Rokke\Runtime\Build\ValidationPlanCompiler;
use Rokke\Runtime\Compiled\ArtifactRepository;
use Rokke\Runtime\Compiled\CompiledExecutionPipeline;
use Rokke\Runtime\Compiled\CompiledInterceptorPipeline;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;
use Rokke\Runtime\Extension\ExtensionBuilder;
use Rokke\Runtime\Extension\ExtensionRegistry;
use RuntimeException;

/**
 * Composition root for console applications built from extensions.
 *
 * Wires the Console build pipeline (ConsoleCapabilityPass, CommandRegistryCompiler)
 * together with the standard runtime pipeline (OperationModelBuilderPass,
 * DiscoveryEngine) and produces a ConsoleHost ready to dispatch commands.
 *
 * Usage:
 *   (new ConsoleKernel())
 *       ->register(new ConsoleExtension(__DIR__ . '/commands', 'App\\Commands'))
 *       ->build()
 *       ->run();
 */
final class ConsoleKernel
{
    private ExtensionRegistry $extensions;
    private ?ConsoleHost $host = null;

    public function __construct()
    {
        $this->extensions = new ExtensionRegistry();
    }

    public function register(ExtensionInterface $extension): self
    {
        $this->extensions->register($extension);

        return $this;
    }

    public function build(): self
    {
        $extensionBuilder = new ExtensionBuilder();
        $this->extensions->buildAll($extensionBuilder);

        $engine          = new DiscoveryEngine();
        $discovered      = $engine->run($extensionBuilder->getDiscoveryProviders());
        $allCapabilities = [...$extensionBuilder->getCapabilities(), ...$discovered];

        $modelBuilder = new ModelBuilder([
            new ConsoleCapabilityPass(),
            new OperationModelBuilderPass(),
            new ServiceModelBuilderPass(),
        ]);
        $model = $modelBuilder->build($allCapabilities);

        $registryCompiler = new CommandRegistryCompiler();
        $registry         = $registryCompiler->compile($model->definitions(CommandDescriptor::class));

        $serviceDescriptors = $model->definitions(ServiceDescriptor::class);
        $registeredImpls    = array_map(static fn (ServiceDescriptor $d): string => $d->implementation, $serviceDescriptors);
        $handlerDescriptors = [];

        foreach ($model->definitions(OperationDefinition::class) as $definition) {
            $class = $definition->handler;
            if (!in_array($class, $registeredImpls, true)) {
                $reflection = new \ReflectionClass($class);
                if (!$reflection->hasMethod('__invoke') || !$reflection->getMethod('__invoke')->isPublic()) {
                    throw new \RuntimeException("Handler {$class} must declare a public __invoke() method.");
                }
                $handlerDescriptors[] = new ServiceDescriptor($class, $class, [$class]);
                $registeredImpls[]    = $class;
            }
        }

        $factories          = FactoryRepository::build([...$serviceDescriptors, ...$handlerDescriptors], new FactoryCompiler());
        $argCompiler        = new ArgumentPlanCompiler([new OptionArgumentSourceCompiler()]);
        $resultCompiler     = new ResultPlanCompiler([]);
        $validationCompiler = new ValidationPlanCompiler([
            new NotBlankValidationSourceCompiler(),
            new MinValidationSourceCompiler(),
            new MaxValidationSourceCompiler(),
        ]);

        $argumentPlans   = [];
        $resultPlans     = [];
        $validationPlans = [];
        $compiledOps     = [];

        foreach ($model->definitions(OperationDefinition::class) as $index => $definition) {
            $factoryId               = $factories->id($definition->handler)
                ?? throw new \RuntimeException("Handler {$definition->handler} not found in factory repository.");
            $argumentPlans[$index]   = $argCompiler->compile($definition->handler, $factories);
            $resultPlans[$index]     = $resultCompiler->compile($definition->handler);
            $validationPlans[$index] = $validationCompiler->compile($definition->handler);
            $compiledOps[]           = new CompiledOperation(
                id: $definition->id,
                pipelineId: 0,
                factoryId: $factoryId,
                argumentPlanId: $index,
                resultPlanId: $index,
                validationPlanId: $index,
            );
        }

        $executionPipeline = new CompiledExecutionPipeline(
            factories: $factories,
            argumentPlans: $argumentPlans,
            resultPlans: $resultPlans,
            behaviorPipelines: [],
            validationPlans: $validationPlans,
        );

        $runtime = new CompiledRuntime(
            executionPipeline: $executionPipeline,
            interceptorPipeline: CompiledInterceptorPipeline::empty(),
            operations: OperationRepository::build($compiledOps),
            factories: $factories,
            artifacts: ArtifactRepository::build([CommandRegistry::class => $registry]),
        );

        $this->host = new ConsoleHost($runtime);

        return $this;
    }

    public function host(): ConsoleHost
    {
        if ($this->host === null) {
            throw new RuntimeException('Call build() before host().');
        }

        return $this->host;
    }

    /** @param list<string>|null $argv */
    public function run(?array $argv = null): void
    {
        $this->host()->run($argv);
    }
}
