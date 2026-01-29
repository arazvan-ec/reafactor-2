<?php

declare(strict_types=1);

namespace App\Application\Orchestration;

use App\Application\Aggregator\AggregatorExecutor;
use App\Application\Transformer\TransformationPipeline;
use App\Domain\Aggregator\Event\OrchestrationCompletedEvent;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Main orchestration pipeline.
 *
 * Coordinates the full aggregation and transformation process:
 * 1. Execute all aggregators (with dependency resolution)
 * 2. Transform results to JSON format
 * 3. Build final response
 * 4. Emit completion event
 */
final class OrchestrationPipeline
{
    public function __construct(
        private readonly AggregatorExecutor $aggregatorExecutor,
        private readonly TransformationPipeline $transformationPipeline,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Execute full orchestration: aggregate -> transform.
     *
     * @return array<string, mixed> Transformed data ready for JSON response
     */
    public function execute(AggregatorContext $context): array
    {
        $startTime = microtime(true);

        // Phase 1: Execute all aggregators
        $results = $this->aggregatorExecutor->execute($context);

        // Phase 2: Transform results to JSON format
        $transformed = $this->transformationPipeline->transform($results, $context);

        // Calculate metrics
        $successCount = 0;
        $failureCount = 0;
        foreach ($results as $result) {
            if ($result->isSuccess()) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        // Dispatch completion event
        $this->eventDispatcher->dispatch(new OrchestrationCompletedEvent(
            $context->getEditorialId(),
            count($results),
            $successCount,
            $failureCount,
            microtime(true) - $startTime
        ));

        return $transformed;
    }

    /**
     * Execute orchestration and return with metadata.
     *
     * @return array{data: array<string, mixed>, _meta: array<string, mixed>}
     */
    public function executeWithMetadata(AggregatorContext $context): array
    {
        $startTime = microtime(true);

        $results = $this->aggregatorExecutor->execute($context);
        $transformed = $this->transformationPipeline->transform($results, $context);

        $successCount = 0;
        $failureCount = 0;
        $timings = [];

        foreach ($results as $name => $result) {
            if ($result->isSuccess()) {
                $successCount++;
            } else {
                $failureCount++;
            }
            $timings[$name] = $result->getExecutionTimeMs();
        }

        $totalTime = microtime(true) - $startTime;

        $this->eventDispatcher->dispatch(new OrchestrationCompletedEvent(
            $context->getEditorialId(),
            count($results),
            $successCount,
            $failureCount,
            $totalTime
        ));

        return [
            'data' => $transformed,
            '_meta' => [
                'aggregatorStats' => [
                    'total' => count($results),
                    'successful' => $successCount,
                    'failed' => $failureCount,
                    'totalTime' => round($totalTime * 1000, 2),
                    'timings' => $timings,
                ],
            ],
        ];
    }
}
