<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Aggregator;

use App\Application\Aggregator\AggregatorExecutor;
use App\Application\Aggregator\AggregatorRegistry;
use App\Application\Aggregator\DependencyResolver;
use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\Contract\SyncAggregatorInterface;
use App\Domain\Aggregator\Event\AggregatorCompletedEvent;
use App\Domain\Aggregator\Event\AggregatorStartedEvent;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Application\Aggregator\AggregatorExecutor
 */
final class AggregatorExecutorTest extends TestCase
{
    private AggregatorRegistry $registry;
    private DependencyResolver $resolver;
    private EventDispatcherInterface $eventDispatcher;
    private AggregatorExecutor $executor;

    protected function setUp(): void
    {
        $this->registry = new AggregatorRegistry();
        $this->resolver = new DependencyResolver();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->executor = new AggregatorExecutor(
            $this->registry,
            $this->resolver,
            $this->eventDispatcher
        );
    }

    /** @test */
    public function it_returns_empty_for_no_aggregators(): void
    {
        $context = new AggregatorContext('1', 'news', []);
        $results = $this->executor->execute($context);

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_executes_sync_aggregator(): void
    {
        $aggregator = $this->createSyncAggregator('tag', ['tag1', 'tag2']);
        $this->registry->register($aggregator);

        $context = new AggregatorContext('1', 'news', ['tags' => true]);

        $results = $this->executor->execute($context);

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('tag', $results);
        $this->assertTrue($results['tag']->isSuccess());
        $this->assertEquals(['tag1', 'tag2'], $results['tag']->getData());
    }

    /** @test */
    public function it_executes_async_aggregator(): void
    {
        $aggregator = $this->createAsyncAggregator('multimedia', ['photo1', 'photo2']);
        $this->registry->register($aggregator);

        $context = new AggregatorContext('1', 'news', ['multimedia' => true]);

        $results = $this->executor->execute($context);

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('multimedia', $results);
        $this->assertTrue($results['multimedia']->isSuccess());
        $this->assertEquals(['photo1', 'photo2'], $results['multimedia']->getData());
    }

    /** @test */
    public function it_handles_sync_aggregator_failure(): void
    {
        $aggregator = $this->createFailingSyncAggregator('tag', 'Service unavailable');
        $this->registry->register($aggregator);

        $context = new AggregatorContext('1', 'news', ['tags' => true]);

        $results = $this->executor->execute($context);

        $this->assertCount(1, $results);
        $this->assertFalse($results['tag']->isSuccess());
        $this->assertEquals('Service unavailable', $results['tag']->getError());
    }

    /** @test */
    public function it_handles_async_aggregator_failure(): void
    {
        $aggregator = $this->createFailingAsyncAggregator('multimedia', 'Timeout', []);
        $this->registry->register($aggregator);

        $context = new AggregatorContext('1', 'news', ['multimedia' => true]);

        $results = $this->executor->execute($context);

        $this->assertCount(1, $results);
        $this->assertFalse($results['multimedia']->isSuccess());
        $this->assertEquals('Timeout', $results['multimedia']->getError());
        $this->assertEquals([], $results['multimedia']->getData()); // Fallback
    }

    /** @test */
    public function it_executes_multiple_aggregators(): void
    {
        $this->registry->register($this->createAsyncAggregator('tag', ['tag1']));
        $this->registry->register($this->createAsyncAggregator('multimedia', ['photo1']));

        $context = new AggregatorContext('1', 'news', []);

        $results = $this->executor->execute($context);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('tag', $results);
        $this->assertArrayHasKey('multimedia', $results);
    }

    /** @test */
    public function it_dispatches_start_events(): void
    {
        $this->eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof AggregatorStartedEvent
                    || $event instanceof AggregatorCompletedEvent;
            }));

        $this->registry->register($this->createSyncAggregator('tag', ['tag1']));

        $context = new AggregatorContext('1', 'news', []);
        $this->executor->execute($context);
    }

    /** @test */
    public function it_respects_context_support(): void
    {
        // Aggregator that only supports contexts with tags
        $aggregator = $this->createMock(AsyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn('tag');
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(false); // Does not support

        $this->registry->register($aggregator);

        $context = new AggregatorContext('1', 'news', []);
        $results = $this->executor->execute($context);

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_updates_context_between_batches(): void
    {
        // First aggregator
        $first = $this->createAsyncAggregator('multimedia', ['photo1']);

        // Second aggregator that depends on first
        $second = $this->createMock(SyncAggregatorInterface::class);
        $second->method('getName')->willReturn('bodyTag');
        $second->method('getPriority')->willReturn(50);
        $second->method('getDependencies')->willReturn(['multimedia']);
        $second->method('supports')->willReturn(true);

        // Capture the context passed to second aggregator
        $capturedContext = null;
        $second->method('aggregate')->willReturnCallback(function ($ctx) use (&$capturedContext) {
            $capturedContext = $ctx;
            return AggregatorResult::success('bodyTag', ['body1'], 0.01);
        });

        $this->registry->register($first);
        $this->registry->register($second);

        $context = new AggregatorContext('1', 'news', []);
        $this->executor->execute($context);

        // Verify the context was updated with multimedia data
        $this->assertNotNull($capturedContext);
        $this->assertEquals(['photo1'], $capturedContext->getResolvedDataByKey('multimedia'));
    }

    /** @test */
    public function it_measures_execution_time(): void
    {
        $aggregator = $this->createSyncAggregator('tag', ['tag1']);
        $this->registry->register($aggregator);

        $context = new AggregatorContext('1', 'news', []);
        $results = $this->executor->execute($context);

        $this->assertGreaterThanOrEqual(0.0, $results['tag']->getExecutionTime());
    }

    private function createSyncAggregator(string $name, mixed $data): SyncAggregatorInterface
    {
        $aggregator = $this->createMock(SyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn($name);
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('aggregate')->willReturn(
            AggregatorResult::success($name, $data, 0.01)
        );

        return $aggregator;
    }

    private function createAsyncAggregator(string $name, mixed $data): AsyncAggregatorInterface
    {
        $aggregator = $this->createMock(AsyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn($name);
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('getTimeout')->willReturn(5000);
        $aggregator->method('getFallback')->willReturn([]);
        $aggregator->method('aggregate')->willReturn(new FulfilledPromise($data));

        return $aggregator;
    }

    private function createFailingSyncAggregator(string $name, string $error): SyncAggregatorInterface
    {
        $aggregator = $this->createMock(SyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn($name);
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('aggregate')->willThrowException(new \RuntimeException($error));

        return $aggregator;
    }

    private function createFailingAsyncAggregator(string $name, string $error, mixed $fallback): AsyncAggregatorInterface
    {
        $aggregator = $this->createMock(AsyncAggregatorInterface::class);
        $aggregator->method('getName')->willReturn($name);
        $aggregator->method('getPriority')->willReturn(50);
        $aggregator->method('getDependencies')->willReturn([]);
        $aggregator->method('supports')->willReturn(true);
        $aggregator->method('getTimeout')->willReturn(5000);
        $aggregator->method('getFallback')->willReturn($fallback);
        $aggregator->method('aggregate')->willReturn(new RejectedPromise(new \RuntimeException($error)));

        return $aggregator;
    }
}
