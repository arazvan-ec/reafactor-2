<?php

declare(strict_types=1);

namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\Contract\SyncAggregatorInterface;
use App\Domain\Aggregator\Event\AggregatorCompletedEvent;
use App\Domain\Aggregator\Event\AggregatorStartedEvent;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Executes aggregators with dependency resolution and parallel execution.
 *
 * Orchestrates the execution of aggregators:
 * 1. Gets applicable aggregators from registry
 * 2. Resolves dependencies into batches
 * 3. Executes batches sequentially, aggregators within batch in parallel
 * 4. Updates context with resolved data between batches
 */
final class AggregatorExecutor
{
    public function __construct(
        private readonly AggregatorRegistry $registry,
        private readonly DependencyResolver $dependencyResolver,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Execute all applicable aggregators for the context.
     *
     * @return array<string, AggregatorResult>
     */
    public function execute(AggregatorContext $context): array
    {
        $aggregators = $this->registry->getForContext($context);

        if (empty($aggregators)) {
            return [];
        }

        $batches = $this->dependencyResolver->resolve($aggregators);
        $results = [];

        foreach ($batches as $batch) {
            $batchResults = $this->executeBatch($batch, $context);

            foreach ($batchResults as $name => $result) {
                $results[$name] = $result;
                // Update context with resolved data for next batch
                $context = $context->withResolvedData($name, $result->getData());
            }
        }

        return $results;
    }

    /**
     * Execute a batch of aggregators (parallel where possible).
     *
     * @param AggregatorInterface[] $aggregators
     * @return array<string, AggregatorResult>
     */
    private function executeBatch(array $aggregators, AggregatorContext $context): array
    {
        $promises = [];
        $syncResults = [];

        foreach ($aggregators as $aggregator) {
            $this->dispatchStartEvent($aggregator, $context);

            if ($aggregator instanceof AsyncAggregatorInterface) {
                $promises[$aggregator->getName()] = $this->executeAsync($aggregator, $context);
            } elseif ($aggregator instanceof SyncAggregatorInterface) {
                $syncResults[$aggregator->getName()] = $this->executeSync($aggregator, $context);
            }
        }

        $asyncResults = $this->resolvePromises($promises, $context);

        return array_merge($syncResults, $asyncResults);
    }

    /**
     * Execute async aggregator and wrap result in Promise.
     */
    private function executeAsync(
        AsyncAggregatorInterface $aggregator,
        AggregatorContext $context
    ): PromiseInterface {
        $startTime = microtime(true);
        $name = $aggregator->getName();

        return $aggregator->aggregate($context)
            ->then(
                function (mixed $data) use ($name, $startTime, $context): AggregatorResult {
                    $result = AggregatorResult::success(
                        $name,
                        $data,
                        microtime(true) - $startTime
                    );
                    $this->dispatchCompletedEvent($result, $context);
                    return $result;
                },
                function (\Throwable $e) use ($aggregator, $startTime, $context): AggregatorResult {
                    $result = AggregatorResult::failure(
                        $aggregator->getName(),
                        $e->getMessage(),
                        $aggregator->getFallback(),
                        microtime(true) - $startTime
                    );
                    $this->dispatchCompletedEvent($result, $context);
                    return $result;
                }
            );
    }

    /**
     * Execute sync aggregator.
     */
    private function executeSync(
        SyncAggregatorInterface $aggregator,
        AggregatorContext $context
    ): AggregatorResult {
        $startTime = microtime(true);

        try {
            $result = $aggregator->aggregate($context);
            $this->dispatchCompletedEvent($result, $context);
            return $result;
        } catch (\Throwable $e) {
            $result = AggregatorResult::failure(
                $aggregator->getName(),
                $e->getMessage(),
                null,
                microtime(true) - $startTime
            );
            $this->dispatchCompletedEvent($result, $context);
            return $result;
        }
    }

    /**
     * Resolve all promises and extract results.
     *
     * @param array<string, PromiseInterface> $promises
     * @return array<string, AggregatorResult>
     */
    private function resolvePromises(array $promises, AggregatorContext $context): array
    {
        if (empty($promises)) {
            return [];
        }

        $settled = Utils::settle($promises)->wait();
        $results = [];

        foreach ($settled as $name => $outcome) {
            // The promise already wraps results in AggregatorResult via then()
            $results[$name] = $outcome['value'];
        }

        return $results;
    }

    private function dispatchStartEvent(AggregatorInterface $aggregator, AggregatorContext $context): void
    {
        $this->eventDispatcher->dispatch(new AggregatorStartedEvent(
            $aggregator->getName(),
            $context->getEditorialId(),
            microtime(true)
        ));
    }

    private function dispatchCompletedEvent(AggregatorResult $result, AggregatorContext $context): void
    {
        $this->eventDispatcher->dispatch(new AggregatorCompletedEvent(
            $result->getAggregatorName(),
            $context->getEditorialId(),
            $result->isSuccess(),
            $result->getExecutionTime(),
            $result->getError()
        ));
    }
}
