<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Event;

/**
 * Dispatched when the full orchestration pipeline completes.
 *
 * Provides summary metrics for the entire aggregation process.
 */
final readonly class OrchestrationCompletedEvent
{
    public function __construct(
        public string $editorialId,
        public int $aggregatorCount,
        public int $successCount,
        public int $failureCount,
        public float $totalTime
    ) {
    }

    /**
     * Get total time in milliseconds.
     */
    public function getTotalTimeMs(): float
    {
        return $this->totalTime * 1000;
    }

    /**
     * Get success rate as percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->aggregatorCount === 0) {
            return 100.0;
        }

        return ($this->successCount / $this->aggregatorCount) * 100;
    }
}
