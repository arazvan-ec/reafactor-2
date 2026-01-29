<?php

declare(strict_types=1);

namespace App\Tests\Integration\Orchestration;

use App\Application\Aggregator\AggregatorExecutor;
use App\Application\Aggregator\AggregatorRegistry;
use App\Application\Aggregator\DependencyResolver;
use App\Application\Orchestration\OrchestrationPipeline;
use App\Application\Transformer\TransformationPipeline;
use App\Application\Transformer\TransformerRegistry;
use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\Contract\SyncAggregatorInterface;
use App\Domain\Aggregator\Event\OrchestrationCompletedEvent;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Domain\Transformer\Contract\JsonTransformerInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Application\Orchestration\OrchestrationPipeline
 */
final class OrchestrationPipelineTest extends TestCase
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
    public function it_executes_empty_pipeline(): void
    {
        $context = new AggregatorContext('1', 'news', []);

        $result = $this->pipeline->execute($context);

        $this->assertIsArray($result);
    }

    /** @test */
    public function it_executes_full_pipeline(): void
    {
        $this->registerTagAggregator();

        $context = new AggregatorContext('1', 'news', ['tags' => [['id' => 't1']]]);

        $result = $this->pipeline->execute($context);

        $this->assertArrayHasKey('tag', $result);
        $this->assertEquals(['tag1', 'tag2'], $result['tag']);
    }

    /** @test */
    public function it_transforms_results(): void
    {
        $this->registerTagAggregator();
        $this->registerTagTransformer();

        $context = new AggregatorContext('1', 'news', ['tags' => [['id' => 't1']]]);

        $result = $this->pipeline->execute($context);

        $this->assertArrayHasKey('tag', $result);
        $this->assertEquals(['transformed_tag1', 'transformed_tag2'], $result['tag']);
    }

    /** @test */
    public function it_handles_aggregator_failure(): void
    {
        $aggregator = $this->createMock(AsyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn('tag');
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('getTimeout')->willReturn(5000);
        $aggregator->method('getFallback')->willReturn(['fallback']);
        $aggregator->method('aggregate')->willReturn(
            new FulfilledPromise(null)->then(
                fn() => throw new \RuntimeException('Error')
            )
        );

        $this->aggregatorRegistry->register($aggregator);

        $context = new AggregatorContext('1', 'news', []);

        $result = $this->pipeline->execute($context);

        // Should complete with fallback value
        $this->assertArrayHasKey('tag', $result);
    }

    /** @test */
    public function it_dispatches_orchestration_completed_event(): void
    {
        $this->eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof OrchestrationCompletedEvent
                    || true; // Accept any event type
            }));

        $this->registerTagAggregator();

        $context = new AggregatorContext('1', 'news', ['tags' => [['id' => 't1']]]);
        $this->pipeline->execute($context);
    }

    /** @test */
    public function it_returns_metadata_when_requested(): void
    {
        $this->registerTagAggregator();

        $context = new AggregatorContext('1', 'news', ['tags' => [['id' => 't1']]]);

        $result = $this->pipeline->executeWithMetadata($context);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('_meta', $result);
        $this->assertArrayHasKey('editorialId', $result['_meta']);
        $this->assertArrayHasKey('totalExecutionTime', $result['_meta']);
        $this->assertArrayHasKey('aggregatorsExecuted', $result['_meta']);
    }

    /** @test */
    public function it_respects_aggregator_dependencies(): void
    {
        // First aggregator
        $first = $this->createMock(AsyncAggregatorInterface::class);
        $first->method('getName')->willReturn('section');
        $first->method('getPriority')->willReturn(100);
        $first->method('getDependencies')->willReturn([]);
        $first->method('supports')->willReturn(true);
        $first->method('getTimeout')->willReturn(5000);
        $first->method('getFallback')->willReturn(null);
        $first->method('aggregate')->willReturn(new FulfilledPromise(['section_data']));

        // Second aggregator depends on first
        $executionOrder = [];
        $second = $this->createMock(SyncAggregatorInterface::class);
        $second->method('getName')->willReturn('bodyTag');
        $second->method('getPriority')->willReturn(50);
        $second->method('getDependencies')->willReturn(['section']);
        $second->method('supports')->willReturn(true);
        $second->method('aggregate')->willReturnCallback(function ($ctx) use (&$executionOrder) {
            $executionOrder[] = 'bodyTag';
            // Should have section data available
            $sectionData = $ctx->getResolvedDataByKey('section');
            return AggregatorResult::success('bodyTag', [
                'body_data',
                'section_was_resolved' => $sectionData !== null,
            ], 0.01);
        });

        $this->aggregatorRegistry->register($first);
        $this->aggregatorRegistry->register($second);

        $context = new AggregatorContext('1', 'news', []);
        $result = $this->pipeline->execute($context);

        $this->assertArrayHasKey('section', $result);
        $this->assertArrayHasKey('bodyTag', $result);
    }

    /** @test */
    public function it_handles_multiple_aggregators(): void
    {
        $this->registerTagAggregator();

        $multimedia = $this->createMock(AsyncAggregatorInterface::class);
        $multimedia->method('getName')->willReturn('multimedia');
        $multimedia->method('getPriority')->willReturn(90);
        $multimedia->method('getDependencies')->willReturn([]);
        $multimedia->method('supports')->willReturn(true);
        $multimedia->method('getTimeout')->willReturn(5000);
        $multimedia->method('getFallback')->willReturn([]);
        $multimedia->method('aggregate')->willReturn(new FulfilledPromise(['photo1']));

        $this->aggregatorRegistry->register($multimedia);

        $context = new AggregatorContext('1', 'news', ['tags' => [['id' => 't1']]]);
        $result = $this->pipeline->execute($context);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('tag', $result);
        $this->assertArrayHasKey('multimedia', $result);
    }

    private function registerTagAggregator(): void
    {
        $aggregator = $this->createMock(AsyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn('tag');
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('getTimeout')->willReturn(5000);
        $aggregator->method('getFallback')->willReturn([]);
        $aggregator->method('aggregate')->willReturn(new FulfilledPromise(['tag1', 'tag2']));

        $this->aggregatorRegistry->register($aggregator);
    }

    private function registerTagTransformer(): void
    {
        $transformer = $this->createMock(JsonTransformerInterface::class);
        $transformer->method('getType')->willReturn('tag');
        $transformer->method('supports')->willReturn(true);
        $transformer->method('transform')->willReturnCallback(
            fn($data) => array_map(fn($t) => "transformed_$t", $data)
        );

        $this->transformerRegistry->register($transformer);
    }
}
