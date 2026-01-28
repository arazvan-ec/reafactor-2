<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use App\Orchestrator\Resolver\Interface\ResolverRegistryInterface;

final class ResolverRegistry implements ResolverRegistryInterface
{
    /** @var DataResolverInterface[] */
    private array $resolvers = [];

    private bool $sorted = false;

    public function addResolver(DataResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
        $this->sorted = false;
    }

    public function getResolvers(): array
    {
        $this->sortResolvers();
        return $this->resolvers;
    }

    public function getResolversFor(OrchestrationContext $context): array
    {
        $this->sortResolvers();

        return array_filter(
            $this->resolvers,
            static fn(DataResolverInterface $resolver) => $resolver->supports($context)
        );
    }

    private function sortResolvers(): void
    {
        if ($this->sorted) {
            return;
        }

        usort(
            $this->resolvers,
            static fn(DataResolverInterface $a, DataResolverInterface $b) => $b->getPriority() <=> $a->getPriority()
        );

        $this->sorted = true;
    }
}
