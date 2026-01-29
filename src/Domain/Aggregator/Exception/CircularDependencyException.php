<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

/**
 * Thrown when circular dependencies are detected between aggregators.
 */
final class CircularDependencyException extends AggregatorException
{
    /**
     * @param string[] $cycle The aggregators involved in the cycle
     */
    public function __construct(array $cycle)
    {
        parent::__construct(sprintf(
            'Circular dependency detected: %s',
            implode(' -> ', $cycle)
        ));
        $this->context = ['cycle' => $cycle];
    }
}
