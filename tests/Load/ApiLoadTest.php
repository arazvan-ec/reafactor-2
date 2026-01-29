<?php

declare(strict_types=1);

namespace App\Tests\Load;

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
 * Load tests for the aggregation system.
 *
 * These tests simulate concurrent requests and verify system
 * stability under load.
 */
final class ApiLoadTest extends TestCase
{
    private OrchestrationPipeline $pipeline;
    private AggregatorRegistry $aggregatorRegistry;

    protected function setUp(): void
    {
        $this->aggregatorRegistry = new AggregatorRegistry();
        $transformerRegistry = new TransformerRegistry();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $dependencyResolver = new DependencyResolver();
        $aggregatorExecutor = new AggregatorExecutor(
            $this->aggregatorRegistry,
            $dependencyResolver,
            $eventDispatcher
        );
        $transformationPipeline = new TransformationPipeline($transformerRegistry);

        $this->pipeline = new OrchestrationPipeline(
            $aggregatorExecutor,
            $transformationPipeline,
            $eventDispatcher
        );

        // Register realistic aggregators
        $this->registerSimulatedAggregators();
    }

    /** @test */
    public function handles_sequential_requests(): void
    {
        $requestCount = 100;
        $successCount = 0;
        $totalTime = 0;

        for ($i = 0; $i < $requestCount; $i++) {
            $context = $this->createTestContext("editorial-$i");

            $start = microtime(true);
            try {
                $result = $this->pipeline->execute($context);
                if (!empty($result)) {
                    $successCount++;
                }
            } catch (\Throwable $e) {
                // Count failures
            }
            $totalTime += microtime(true) - $start;
        }

        $avgTime = $totalTime / $requestCount;

        // All requests should succeed
        $this->assertEquals($requestCount, $successCount);

        // Average time should be reasonable
        $this->assertLessThan(0.1, $avgTime, "Average request time: {$avgTime}s exceeds 100ms");
    }

    /** @test */
    public function handles_burst_of_requests(): void
    {
        $burstSize = 50;
        $results = [];

        $start = microtime(true);

        for ($i = 0; $i < $burstSize; $i++) {
            $context = $this->createTestContext("burst-$i");
            $results[] = $this->pipeline->execute($context);
        }

        $totalTime = microtime(true) - $start;

        // All should complete
        $this->assertCount($burstSize, $results);

        // Should complete in reasonable time
        $this->assertLessThan(5.0, $totalTime, "Burst of $burstSize requests took {$totalTime}s");
    }

    /** @test */
    public function memory_usage_stays_bounded(): void
    {
        $iterations = 100;

        $memoryBefore = memory_get_usage(true);

        for ($i = 0; $i < $iterations; $i++) {
            $context = $this->createTestContext("memory-test-$i");
            $this->pipeline->execute($context);

            // Force garbage collection periodically
            if ($i % 10 === 0) {
                gc_collect_cycles();
            }
        }

        gc_collect_cycles();
        $memoryAfter = memory_get_usage(true);

        $memoryIncrease = $memoryAfter - $memoryBefore;
        $memoryIncreaseMB = $memoryIncrease / 1024 / 1024;

        // Memory increase should be < 10MB for 100 iterations
        $this->assertLessThan(10, $memoryIncreaseMB, "Memory increase: {$memoryIncreaseMB}MB exceeds 10MB");
    }

    /** @test */
    public function latency_percentiles_meet_requirements(): void
    {
        $requestCount = 100;
        $times = [];

        for ($i = 0; $i < $requestCount; $i++) {
            $context = $this->createTestContext("percentile-$i");

            $start = microtime(true);
            $this->pipeline->execute($context);
            $times[] = (microtime(true) - $start) * 1000; // Convert to ms
        }

        sort($times);

        $p50 = $times[(int) ($requestCount * 0.50)];
        $p95 = $times[(int) ($requestCount * 0.95)];
        $p99 = $times[(int) ($requestCount * 0.99)];

        // p50 should be < 50ms
        $this->assertLessThan(50, $p50, "p50 latency: {$p50}ms exceeds 50ms");

        // p95 should be < 100ms
        $this->assertLessThan(100, $p95, "p95 latency: {$p95}ms exceeds 100ms");

        // p99 should be < 200ms
        $this->assertLessThan(200, $p99, "p99 latency: {$p99}ms exceeds 200ms");
    }

    /** @test */
    public function handles_varied_payload_sizes(): void
    {
        $payloadSizes = [10, 50, 100, 500];
        $results = [];

        foreach ($payloadSizes as $size) {
            $context = $this->createTestContextWithPayload("payload-$size", $size);

            $start = microtime(true);
            $result = $this->pipeline->execute($context);
            $time = microtime(true) - $start;

            $results[$size] = [
                'success' => !empty($result),
                'time' => $time,
            ];
        }

        // All payload sizes should succeed
        foreach ($payloadSizes as $size) {
            $this->assertTrue($results[$size]['success'], "Failed for payload size: $size");
        }
    }

    private function registerSimulatedAggregators(): void
    {
        $aggregatorConfigs = [
            ['name' => 'section', 'priority' => 100, 'deps' => []],
            ['name' => 'tag', 'priority' => 70, 'deps' => []],
            ['name' => 'multimedia', 'priority' => 90, 'deps' => []],
            ['name' => 'journalist', 'priority' => 80, 'deps' => []],
            ['name' => 'bodyTag', 'priority' => 60, 'deps' => ['multimedia']],
        ];

        foreach ($aggregatorConfigs as $config) {
            $aggregator = $this->createMock(AsyncAggregatorInterface::class);
            $aggregator->method('getName')->willReturn($config['name']);
            $aggregator->method('getPriority')->willReturn($config['priority']);
            $aggregator->method('getDependencies')->willReturn($config['deps']);
            $aggregator->method('supports')->willReturn(true);
            $aggregator->method('getTimeout')->willReturn(5000);
            $aggregator->method('getFallback')->willReturn([]);
            $aggregator->method('aggregate')->willReturn(
                new FulfilledPromise([$config['name'] . '_data'])
            );

            $this->aggregatorRegistry->register($aggregator);
        }
    }

    private function createTestContext(string $editorialId): AggregatorContext
    {
        return new AggregatorContext(
            $editorialId,
            'news',
            [
                'title' => 'Test Article',
                'tags' => [['id' => 't1'], ['id' => 't2']],
                'body' => ['bodyElements' => [
                    ['type' => 'paragraph', 'content' => 'Test content'],
                ]],
                'signatures' => [['journalistId' => 'j1']],
                'multimedia' => ['mainPhotoId' => 'p1'],
            ]
        );
    }

    private function createTestContextWithPayload(string $editorialId, int $elementCount): AggregatorContext
    {
        $bodyElements = [];
        for ($i = 0; $i < $elementCount; $i++) {
            $bodyElements[] = [
                'type' => 'paragraph',
                'content' => str_repeat('Test content. ', 10),
            ];
        }

        return new AggregatorContext(
            $editorialId,
            'news',
            [
                'title' => 'Test Article with ' . $elementCount . ' elements',
                'tags' => array_map(fn($i) => ['id' => "t$i"], range(1, min($elementCount, 10))),
                'body' => ['bodyElements' => $bodyElements],
                'signatures' => [['journalistId' => 'j1']],
                'multimedia' => ['mainPhotoId' => 'p1'],
            ]
        );
    }
}
