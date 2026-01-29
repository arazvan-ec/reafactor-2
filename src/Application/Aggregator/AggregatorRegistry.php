<?php

declare(strict_types=1);

namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Exception\AggregatorNotFoundException;
use App\Domain\Aggregator\Exception\DuplicateAggregatorException;
use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Registry for aggregator instances.
 *
 * Manages registration, retrieval, and filtering of aggregators.
 * Aggregators are registered via Compiler Pass at container build time.
 */
final class AggregatorRegistry
{
    /**
     * @var array<string, AggregatorInterface>
     */
    private array $aggregators = [];

    /**
     * Register an aggregator.
     *
     * @throws DuplicateAggregatorException if name already registered
     */
    public function register(AggregatorInterface $aggregator): void
    {
        $name = $aggregator->getName();

        if (isset($this->aggregators[$name])) {
            throw new DuplicateAggregatorException($name);
        }

        $this->aggregators[$name] = $aggregator;
    }

    /**
     * Get aggregator by name.
     *
     * @throws AggregatorNotFoundException if not found
     */
    public function get(string $name): AggregatorInterface
    {
        if (!isset($this->aggregators[$name])) {
            throw new AggregatorNotFoundException($name);
        }

        return $this->aggregators[$name];
    }

    /**
     * Check if aggregator exists.
     */
    public function has(string $name): bool
    {
        return isset($this->aggregators[$name]);
    }

    /**
     * Get all registered aggregators.
     *
     * @return AggregatorInterface[]
     */
    public function getAll(): array
    {
        return array_values($this->aggregators);
    }

    /**
     * Get all aggregator names.
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->aggregators);
    }

    /**
     * Get aggregators that support the given context, sorted by priority (descending).
     *
     * @return AggregatorInterface[]
     */
    public function getForContext(AggregatorContext $context): array
    {
        $applicable = array_filter(
            $this->aggregators,
            static fn(AggregatorInterface $aggregator): bool => $aggregator->supports($context)
        );

        uasort(
            $applicable,
            static fn(AggregatorInterface $a, AggregatorInterface $b): int =>
                $b->getPriority() <=> $a->getPriority()
        );

        return array_values($applicable);
    }

    /**
     * Get count of registered aggregators.
     */
    public function count(): int
    {
        return count($this->aggregators);
    }
}
