<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Base contract for all data aggregators.
 *
 * Aggregators are responsible for fetching and combining data from external sources.
 * They execute based on priority and can declare dependencies on other aggregators.
 */
interface AggregatorInterface
{
    /**
     * Unique name identifier for this aggregator.
     *
     * @example "tag", "multimedia", "journalist", "bodyTag"
     */
    public function getName(): string;

    /**
     * Execution priority (higher number = executes first in its batch).
     *
     * Priority levels:
     * - 100+: Critical aggregators (bodyTag with dependencies)
     * - 70-99: High priority (tag, multimedia)
     * - 40-69: Medium priority (journalist, section)
     * - 0-39: Low priority (recommendations)
     */
    public function getPriority(): int;

    /**
     * Names of aggregators this one depends on.
     *
     * Aggregators with dependencies execute AFTER their dependencies complete.
     * Circular dependencies will throw CircularDependencyException.
     *
     * @return string[]
     * @example ["multimedia"] for bodyTag that needs resolved multimedia
     */
    public function getDependencies(): array;

    /**
     * Whether this aggregator should execute for the given context.
     *
     * Return false to skip execution (e.g., if no tags in editorial).
     */
    public function supports(AggregatorContext $context): bool;
}
