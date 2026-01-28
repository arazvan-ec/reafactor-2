<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver\Interface;

use App\Orchestrator\Context\OrchestrationContext;

interface ResolverRegistryInterface
{
    public function addResolver(DataResolverInterface $resolver): void;

    /**
     * @return DataResolverInterface[]
     */
    public function getResolvers(): array;

    /**
     * @return DataResolverInterface[]
     */
    public function getResolversFor(OrchestrationContext $context): array;
}
