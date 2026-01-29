<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

/**
 * Thrown when an aggregator exceeds its configured timeout.
 */
final class AggregatorTimeoutException extends AggregatorException
{
    public function __construct(string $name, int $timeoutMs, string $editorialId)
    {
        parent::__construct(sprintf(
            "Aggregator '%s' timed out after %d ms while processing editorial '%s'",
            $name,
            $timeoutMs,
            $editorialId
        ));
        $this->context = [
            'aggregator_name' => $name,
            'timeout_ms' => $timeoutMs,
            'editorial_id' => $editorialId,
        ];
    }
}
