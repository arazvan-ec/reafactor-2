<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

/**
 * Thrown when attempting to register an aggregator with a name that already exists.
 */
final class DuplicateAggregatorException extends AggregatorException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf("Aggregator '%s' is already registered", $name));
        $this->context = ['aggregator_name' => $name];
    }
}
