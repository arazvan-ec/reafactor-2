<?php

declare(strict_types=1);

namespace App\Domain\Transformer\ValueObject;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;

/**
 * Context for JSON transformation.
 *
 * Provides transformers with:
 * - Original aggregator context
 * - All aggregation results (for cross-reference)
 * - Transformation options
 */
final readonly class TransformationContext
{
    /**
     * @param AggregatorContext $aggregatorContext Original context
     * @param array<string, AggregatorResult> $allResults All aggregator results
     * @param array<string, mixed> $options Transformation options
     */
    public function __construct(
        private AggregatorContext $aggregatorContext,
        private array $allResults,
        private array $options = []
    ) {
    }

    public function getAggregatorContext(): AggregatorContext
    {
        return $this->aggregatorContext;
    }

    /**
     * @return array<string, AggregatorResult>
     */
    public function getAllResults(): array
    {
        return $this->allResults;
    }

    /**
     * Get result for a specific aggregator.
     */
    public function getResult(string $aggregatorName): ?AggregatorResult
    {
        return $this->allResults[$aggregatorName] ?? null;
    }

    /**
     * Get data from a specific aggregator result.
     */
    public function getResultData(string $aggregatorName): mixed
    {
        return $this->allResults[$aggregatorName]?->getData();
    }

    /**
     * Check if a specific aggregator result exists and was successful.
     */
    public function hasSuccessfulResult(string $aggregatorName): bool
    {
        $result = $this->allResults[$aggregatorName] ?? null;
        return $result !== null && $result->isSuccess();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get a specific option value.
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Get editorial ID from the underlying context.
     */
    public function getEditorialId(): string
    {
        return $this->aggregatorContext->getEditorialId();
    }

    /**
     * Get editorial type from the underlying context.
     */
    public function getEditorialType(): string
    {
        return $this->aggregatorContext->getEditorialType();
    }
}
