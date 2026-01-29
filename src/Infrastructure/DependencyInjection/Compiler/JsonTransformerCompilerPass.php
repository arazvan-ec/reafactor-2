<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Compiler;

use App\Application\Transformer\TransformerRegistry;
use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Infrastructure\Attribute\AsJsonTransformer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to auto-register JSON transformers into the registry.
 *
 * Finds all services tagged with 'app.json_transformer' or marked with
 * the #[AsJsonTransformer] attribute and registers them with the TransformerRegistry.
 */
final class JsonTransformerCompilerPass implements CompilerPassInterface
{
    private const TAG_NAME = 'app.json_transformer';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(TransformerRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(TransformerRegistry::class);

        // Register services tagged with 'app.json_transformer'
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach (array_keys($taggedServices) as $serviceId) {
            $registryDefinition->addMethodCall('register', [new Reference($serviceId)]);
        }

        // Auto-tag services with #[AsJsonTransformer] attribute
        $this->autoTagAttributedServices($container);
    }

    /**
     * Find and tag services that implement JsonTransformerInterface
     * and have the #[AsJsonTransformer] attribute.
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

            if (!is_a($class, JsonTransformerInterface::class, true)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);
            $attributes = $reflectionClass->getAttributes(AsJsonTransformer::class);

            if (empty($attributes)) {
                continue;
            }

            // Add tag if not already tagged
            if (!$definition->hasTag(self::TAG_NAME)) {
                $definition->addTag(self::TAG_NAME);

                // Also register with the registry if not already done
                $registryDefinition = $container->findDefinition(TransformerRegistry::class);
                $registryDefinition->addMethodCall('register', [new Reference($id)]);
            }
        }
    }
}
