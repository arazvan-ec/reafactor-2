<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Compiler;

use App\Application\Aggregator\AggregatorRegistry;
use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Infrastructure\Attribute\AsAggregator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to auto-register aggregators into the registry.
 *
 * Finds all services tagged with 'app.aggregator' or marked with
 * the #[AsAggregator] attribute and registers them with the AggregatorRegistry.
 */
final class AggregatorCompilerPass implements CompilerPassInterface
{
    private const TAG_NAME = 'app.aggregator';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AggregatorRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(AggregatorRegistry::class);

        // Register services tagged with 'app.aggregator'
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach (array_keys($taggedServices) as $serviceId) {
            $registryDefinition->addMethodCall('register', [new Reference($serviceId)]);
        }

        // Auto-tag services with #[AsAggregator] attribute
        $this->autoTagAttributedServices($container);
    }

    /**
     * Find and tag services that implement AggregatorInterface
     * and have the #[AsAggregator] attribute.
     */
    private function autoTagAttributedServices(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            if (!class_exists($class)) {
                continue;
            }

            if (!is_a($class, AggregatorInterface::class, true)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);
            $attributes = $reflectionClass->getAttributes(AsAggregator::class);

            if (empty($attributes)) {
                continue;
            }

            // Add tag if not already tagged
            if (!$definition->hasTag(self::TAG_NAME)) {
                $definition->addTag(self::TAG_NAME);

                // Also register with the registry if not already done
                $registryDefinition = $container->findDefinition(AggregatorRegistry::class);
                $registryDefinition->addMethodCall('register', [new Reference($id)]);
            }
        }
    }
}
