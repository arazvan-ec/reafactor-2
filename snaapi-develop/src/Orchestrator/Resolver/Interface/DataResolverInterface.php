<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver\Interface;

use App\Orchestrator\Context\OrchestrationContext;

interface DataResolverInterface
{
    /**
     * Resolve data and populate the orchestration context.
     */
    public function resolve(OrchestrationContext $context): void;

    /**
     * Check if this resolver supports the given context.
     */
    public function supports(OrchestrationContext $context): bool;

    /**
     * Get resolver priority (higher = executed first).
     * Default priorities:
     * - Section: 100
     * - Multimedia: 90
     * - Journalist: 80
     * - Tag: 70
     * - InsertedNews: 60
     * - RecommendedNews: 50
     */
    public function getPriority(): int;
}
