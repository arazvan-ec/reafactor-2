<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Compiler;

use App\Infrastructure\Transformer\BodyElement\BodyElementTransformerHandler;
use App\Infrastructure\Transformer\BodyElement\BodyElementTransformerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to auto-register body element transformers.
 *
 * Finds all services implementing BodyElementTransformerInterface
 * and registers them with the BodyElementTransformerHandler.
 */
final class BodyElementTransformerCompilerPass implements CompilerPassInterface
{
    private const TAG_NAME = 'app.body_element_transformer';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(BodyElementTransformerHandler::class)) {
            return;
        }

        $handlerDefinition = $container->findDefinition(BodyElementTransformerHandler::class);

        // Register services tagged with 'app.body_element_transformer'
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach (array_keys($taggedServices) as $serviceId) {
            $handlerDefinition->addMethodCall('registerTransformer', [new Reference($serviceId)]);
        }

        // Auto-tag services implementing the interface
        $this->autoTagImplementingServices($container);
    }

    /**
     * Find and tag services that implement BodyElementTransformerInterface.
     */
    private function autoTagImplementingServices(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            if (!class_exists($class)) {
                continue;
            }

            if (!is_a($class, BodyElementTransformerInterface::class, true)) {
                continue;
            }

            // Skip the interface itself
            if ($class === BodyElementTransformerInterface::class) {
                continue;
            }

            // Add tag if not already tagged
            if (!$definition->hasTag(self::TAG_NAME)) {
                $definition->addTag(self::TAG_NAME);

                // Also register with the handler
                $handlerDefinition = $container->findDefinition(BodyElementTransformerHandler::class);
                $handlerDefinition->addMethodCall('registerTransformer', [new Reference($id)]);
            }
        }
    }
}
