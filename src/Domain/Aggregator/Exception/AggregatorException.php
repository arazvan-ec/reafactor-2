<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

/**
 * Base exception for all aggregator-related errors.
 */
class AggregatorException extends \RuntimeException
{
    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Get additional context for debugging.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
