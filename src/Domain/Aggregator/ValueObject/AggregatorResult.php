<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\ValueObject;

/**
 * Immutable result of aggregator execution.
 *
 * Contains:
 * - Aggregator identification
 * - Result data (or fallback on failure)
 * - Success/failure status
 * - Error message if failed
 * - Execution time for metrics
 */
final readonly class AggregatorResult
{
    /**
     * @param string $aggregatorName Name of the aggregator
     * @param mixed $data Result data or fallback value
     * @param bool $success Whether execution was successful
     * @param string|null $error Error message if failed
     * @param float $executionTime Execution time in seconds
     */
    public function __construct(
        private string $aggregatorName,
        private mixed $data,
        private bool $success,
        private ?string $error = null,
        private float $executionTime = 0.0
    ) {
    }

    public function getAggregatorName(): string
    {
        return $this->aggregatorName;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Get execution time in milliseconds.
     */
    public function getExecutionTimeMs(): float
    {
        return $this->executionTime * 1000;
    }

    /**
     * Create a successful result.
     */
    public static function success(string $name, mixed $data, float $executionTime): self
    {
        return new self($name, $data, true, null, $executionTime);
    }

    /**
     * Create a failure result with fallback data.
     */
    public static function failure(string $name, string $error, mixed $fallback, float $executionTime): self
    {
        return new self($name, $fallback, false, $error, $executionTime);
    }
}
