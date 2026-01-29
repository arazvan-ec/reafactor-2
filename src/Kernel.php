<?php

declare(strict_types=1);

namespace App;

use App\Infrastructure\DependencyInjection\Compiler\AggregatorCompilerPass;
use App\Infrastructure\DependencyInjection\Compiler\BodyElementTransformerCompilerPass;
use App\Infrastructure\DependencyInjection\Compiler\JsonTransformerCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Application Kernel with auto-registration of aggregators and transformers.
 *
 * Registers compiler passes for the new scalable aggregator system:
 * - AggregatorCompilerPass: Auto-registers classes with #[AsAggregator]
 * - JsonTransformerCompilerPass: Auto-registers classes with #[AsJsonTransformer]
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register aggregator and transformer compiler passes
        $container->addCompilerPass(new AggregatorCompilerPass());
        $container->addCompilerPass(new JsonTransformerCompilerPass());
        $container->addCompilerPass(new BodyElementTransformerCompilerPass());
    }
}
