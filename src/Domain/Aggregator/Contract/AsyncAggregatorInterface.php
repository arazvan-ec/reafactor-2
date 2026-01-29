<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Contract for asynchronous aggregators that return Promises.
 *
 * Use this for aggregators that make HTTP calls to external services.
 * The Promise pattern allows parallel execution of multiple aggregators.
 */
interface AsyncAggregatorInterface extends AggregatorInterface
{
    /**
     * Execute aggregation asynchronously.
     *
     * The Promise should resolve to the aggregated data (any type).
     * Error handling is done by the executor using getFallback().
     *
     * @return PromiseInterface Promise that resolves to aggregated data
     */
    public function aggregate(AggregatorContext $context): PromiseInterface;

    /**
     * Timeout in milliseconds for this aggregator.
     *
     * If exceeded, the aggregator will fail and use fallback value.
     *
     * @return int Timeout in ms (e.g., 3000 = 3 seconds)
     */
    public function getTimeout(): int;

    /**
     * Fallback value when aggregation fails.
     *
     * This value is used when:
     * - HTTP request fails (timeout, 5xx, network error)
     * - Response parsing fails
     * - Any exception is thrown
     *
     * @return mixed Typically [] or null
     */
    public function getFallback(): mixed;
}
