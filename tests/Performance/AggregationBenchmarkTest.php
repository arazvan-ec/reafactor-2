<?php

declare(strict_types=1);

namespace App\Tests\Performance;

use App\Application\Aggregator\AggregatorExecutor;
use App\Application\Aggregator\AggregatorRegistry;
use App\Application\Aggregator\DependencyResolver;
use App\Application\Orchestration\OrchestrationPipeline;
use App\Application\Transformer\TransformationPipeline;
use App\Application\Transformer\TransformerRegistry;
use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Performance benchmarks for the aggregation system.
 *
 * These tests measure execution times and ensure the system
 * meets performance requirements.
 */
final class AggregationBenchmarkTest extends TestCase
{
    private AggregatorRegistry $aggregatorRegistry;
    private TransformerRegistry $transformerRegistry;
    private EventDispatcherInterface $eventDispatcher;
    private OrchestrationPipeline $pipeline;

    protected function setUp(): void
    {
        $this->aggregatorRegistry = new AggregatorRegistry();
        $this->transformerRegistry = new TransformerRegistry();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $dependencyResolver = new DependencyResolver();
        $aggregatorExecutor = new AggregatorExecutor(
            $this->aggregatorRegistry,
            $dependencyResolver,
            $this->eventDispatcher
        );
        $transformationPipeline = new TransformationPipeline($this->transformerRegistry);

        $this->pipeline = new OrchestrationPipeline(
            $aggregatorExecutor,
            $transformationPipeline,
            $this->eventDispatcher
        );
    }

    /** @test */
    public function empty_pipeline_executes_quickly(): void
    {
        $context = new AggregatorContext('1', 'news', []);

        $times = [];
        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            $this->pipeline->execute($context);
            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);

        // Empty pipeline should execute in < 1ms
        $this->assertLessThan(0.001, $avgTime, "Average time: {$avgTime}s exceeds 1ms");
    }

    /** @test */
    public function single_aggregator_executes_quickly(): void
    {
        $this->registerFastAggregator('tag');

        $context = new AggregatorContext('1', 'news', []);

        $times = [];
        for ($i = 0; $i < 50; $i++) {
            $start = microtime(true);
            $this->pipeline->execute($context);
            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);

        // Single aggregator should execute in < 10ms
        $this->assertLessThan(0.01, $avgTime, "Average time: {$avgTime}s exceeds 10ms");
    }

    /** @test */
    public function multiple_parallel_aggregators_are_efficient(): void
    {
        // Register 5 aggregators with no dependencies (can run in parallel)
        $this->registerFastAggregator('tag');
        $this->registerFastAggregator('multimedia');
        $this->registerFastAggregator('section');
        $this->registerFastAggregator('journalist');
        $this->registerFastAggregator('related');

        $context = new AggregatorContext('1', 'news', []);

        $times = [];
        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);
            $this->pipeline->execute($context);
            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);

        // 5 parallel aggregators should execute in < 50ms
        $this->assertLessThan(0.05, $avgTime, "Average time: {$avgTime}s exceeds 50ms");
    }

    /** @test */
    public function dependency_resolution_overhead_is_minimal(): void
    {
        $resolver = new DependencyResolver();

        // Create 10 aggregators with complex dependencies
        $aggregators = [];
        for ($i = 0; $i < 10; $i++) {
            $deps = $i > 0 ? ['agg' . ($i - 1)] : [];
            $aggregators[] = $this->createMockAggregator("agg$i", 50, $deps);
        }

        $times = [];
        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            $resolver->resolve($aggregators);
            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);

        // Dependency resolution should be < 1ms even with 10 aggregators
        $this->assertLessThan(0.001, $avgTime, "Dependency resolution time: {$avgTime}s exceeds 1ms");
    }

    /** @test */
    public function registry_lookup_is_fast(): void
    {
        // Register many aggregators
        for ($i = 0; $i < 50; $i++) {
            $this->aggregatorRegistry->register(
                $this->createMockAggregator("agg$i", $i)
            );
        }

        $context = new AggregatorContext('1', 'news', []);

        $times = [];
        for ($i = 0; $i < 1000; $i++) {
            $start = microtime(true);
            $this->aggregatorRegistry->getForContext($context);
            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);

        // Registry lookup should be < 0.1ms
        $this->assertLessThan(0.0001, $avgTime, "Registry lookup time: {$avgTime}s exceeds 0.1ms");
    }

    /** @test */
    public function context_immutability_has_minimal_overhead(): void
    {
        $context = new AggregatorContext('1', 'news', ['data' => 'test']);

        $times = [];
        for ($i = 0; $i < 1000; $i++) {
            $start = microtime(true);
            $newContext = $context->withResolvedData("key$i", ['value']);
            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);

        // Context creation should be < 0.01ms
        $this->assertLessThan(0.00001, $avgTime, "Context creation time: {$avgTime}s exceeds 0.01ms");
    }

    private function registerFastAggregator(string $name): void
    {
        $aggregator = $this->createMock(AsyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn($name);
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('getTimeout')->willReturn(5000);
        $aggregator->method('getFallback')->willReturn([]);
        $aggregator->method('aggregate')->willReturn(new FulfilledPromise(['data']));

        $this->aggregatorRegistry->register($aggregator);
    }

    private function createMockAggregator(string $name, int $priority, array $deps = []): AsyncAggregatorInterface
    {
        $aggregator = $this->createMock(AsyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn($name);
        $aggregator->method('getPriority')->willReturn($priority);
        $aggregator->method('getDependencies')->willReturn($deps);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('getTimeout')->willReturn(5000);
        $aggregator->method('getFallback')->willReturn([]);
        $aggregator->method('aggregate')->willReturn(new FulfilledPromise(['data']));

        return $aggregator;
    }
}
