<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Event;

/**
 * Dispatched when an aggregator completes execution (success or failure).
 *
 * Useful for logging, metrics, and debugging.
 */
final readonly class AggregatorCompletedEvent
{
    public function __construct(
        public string $aggregatorName,
        public string $editorialId,
        public bool $success,
        public float $executionTime,
        public ?string $error = null
    ) {
    }
}
