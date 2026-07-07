<?php

declare(strict_types=1);

namespace Rokke\Console;

use Rokke\Console\Build\CommandDescriptor;
use Rokke\Console\Build\CommandRegistryCompiler;
use Rokke\Console\Build\ConsoleCapabilityPass;
use Rokke\Console\Build\CommandRegistry;
use Rokke\Console\Build\OptionArgumentSourceCompiler;
use Rokke\Contracts\Module\ModuleInterface;
use Rokke\Runtime\Build\ArgumentPlanCompiler;
use Rokke\Runtime\Build\DiscoveryEngine;
use Rokke\Runtime\Build\FactoryCompiler;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Build\InterceptorChainCompiler;
use Rokke\Runtime\Build\InvokerInterceptorDescriptor;
use Rokke\Runtime\Build\InvokerInterceptorModelBuilderPass;
use Rokke\Runtime\Build\MaxValidationSourceCompiler;
use Rokke\Runtime\Build\MiddlewareDescriptor;
use Rokke\Runtime\Build\MinValidationSourceCompiler;
use Rokke\Runtime\Build\ModelBuilder;
use Rokke\Runtime\Build\NotBlankValidationSourceCompiler;
use Rokke\Runtime\Build\OperationDefinition;
use Rokke\Runtime\Build\OperationModelBuilderPass;
use Rokke\Runtime\Build\PipelineCompiler;
use Rokke\Runtime\Build\PipelineModelBuilderPass;
use Rokke\Runtime\Build\ResultPlanCompiler;
use Rokke\Runtime\Build\ServiceDescriptor;
use Rokke\Runtime\Build\ServiceModelBuilderPass;
use Rokke\Runtime\Build\ValidationPlanCompiler;
use Rokke\Runtime\Compiled\ArtifactRepository;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;
use Rokke\Runtime\Module\ModuleBuilder;
use Rokke\Runtime\Module\ModuleSystem;
use RuntimeException;

/**
 * Composition root for console applications built from modules.
 *
 * Wires the Console build pipeline (ConsoleCapabilityPass, CommandRegistryCompiler)
 * together with the standard runtime pipeline (OperationModelBuilderPass,
 * DiscoveryEngine) and produces a ConsoleHost ready to dispatch commands.
 *
 * Usage:
 *   (new ConsoleKernel())
 *       ->register(new ConsoleModule(__DIR__ . '/commands', 'App\\Commands'))
 *       ->build()
 *       ->run();
 */
final class ConsoleKernel
{
    private ModuleSystem $modules;
    private ?ConsoleHost $host = null;

    public function __construct()
    {
        $this->modules = new ModuleSystem();
    }

    public function register(ModuleInterface $module): self
    {
        $this->modules->register($module);

        return $this;
    }

    public function build(): self
    {
        $moduleBuilder = new ModuleBuilder();
        $this->modules->buildAll($moduleBuilder);

        $engine          = new DiscoveryEngine();
        $discovered      = $engine->run($moduleBuilder->getDiscoveryProviders());
        $allCapabilities = [...$moduleBuilder->getCapabilities(), ...$discovered];

        $modelBuilder = new ModelBuilder([
            new ConsoleCapabilityPass(),
            new OperationModelBuilderPass(),
            new ServiceModelBuilderPass(),
            new PipelineModelBuilderPass(),
            new InvokerInterceptorModelBuilderPass(),
        ]);
        $model = $modelBuilder->build($allCapabilities);

        $registryCompiler = new CommandRegistryCompiler();
        $registry         = $registryCompiler->compile($model->definitions(CommandDescriptor::class));

        $factories          = FactoryRepository::build($model->definitions(ServiceDescriptor::class), new FactoryCompiler());
        $argCompiler        = new ArgumentPlanCompiler([new OptionArgumentSourceCompiler()]);
        $resultCompiler     = new ResultPlanCompiler([]);
        $validationCompiler = new ValidationPlanCompiler([
            new NotBlankValidationSourceCompiler(),
            new MinValidationSourceCompiler(),
            new MaxValidationSourceCompiler(),
        ]);

        $handlers        = [];
        $argumentPlans   = [];
        $resultPlans     = [];
        $validationPlans = [];
        $compiledOps     = [];

        foreach ($model->definitions(OperationDefinition::class) as $index => $definition) {
            $handlers[$index]        = $definition->handler;
            $argumentPlans[$index]   = $argCompiler->compile($definition->handler, $factories);
            $resultPlans[$index]     = $resultCompiler->compile($definition->handler);
            $validationPlans[$index] = $validationCompiler->compile($definition->handler);
            $compiledOps[]           = new CompiledOperation($definition->id, 0, $index, $index, $index, validationPlanId: $index);
        }

        $pipelineCompiler = new PipelineCompiler();
        $pipeline         = $pipelineCompiler->compile($model->definitions(MiddlewareDescriptor::class));

        $interceptorCompiler = new InterceptorChainCompiler();
        $interceptorChain    = $interceptorCompiler->compile($model->definitions(InvokerInterceptorDescriptor::class));

        $runtime = new CompiledRuntime(
            pipelines: [0 => $pipeline],
            handlers: $handlers,
            argumentPlans: $argumentPlans,
            resultPlans: $resultPlans,
            operations: OperationRepository::build($compiledOps),
            artifacts: ArtifactRepository::build([CommandRegistry::class => $registry]),
            interceptorChains: [0 => $interceptorChain],
            validationPlans: $validationPlans,
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
