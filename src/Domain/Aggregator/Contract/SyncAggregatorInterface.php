<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;

/**
 * Contract for synchronous aggregators.
 *
 * Use this for aggregators that don't make external HTTP calls
 * or process data already available in context (e.g., body element transformation).
 */
interface SyncAggregatorInterface extends AggregatorInterface
{
    /**
     * Execute aggregation synchronously.
     *
     * @return AggregatorResult The result containing success/failure and data
     */
    public function aggregate(AggregatorContext $context): AggregatorResult;
}
