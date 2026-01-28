<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Orchestrator\Resolver\ResolverRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ResolverCompilerPass implements CompilerPassInterface
{
    public const TAG_NAME = 'app.data_resolver';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ResolverRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(ResolverRegistry::class);
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addResolver', [new Reference($id)]);
        }
    }
}
