<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

/**
 * Thrown when an aggregator is not found in the registry.
 */
final class AggregatorNotFoundException extends AggregatorException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf("Aggregator '%s' not found in registry", $name));
        $this->context = ['aggregator_name' => $name];
    }
}
