<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Event;

/**
 * Dispatched when an aggregator starts execution.
 *
 * Useful for logging and metrics collection.
 */
final readonly class AggregatorStartedEvent
{
    public function __construct(
        public string $aggregatorName,
        public string $editorialId,
        public float $timestamp
    ) {
    }
}
