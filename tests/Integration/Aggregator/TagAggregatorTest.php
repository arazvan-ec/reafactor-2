<?php

declare(strict_types=1);

namespace App\Tests\Integration\Aggregator;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Infrastructure\Aggregator\TagAggregator;
use App\Infrastructure\Client\Contract\QueryTagClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \App\Infrastructure\Aggregator\TagAggregator
 */
final class TagAggregatorTest extends TestCase
{
    private QueryTagClientInterface $mockClient;
    private TagAggregator $aggregator;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(QueryTagClientInterface::class);
        $this->aggregator = new TagAggregator($this->mockClient, new NullLogger());
    }

    /** @test */
    public function it_has_correct_name(): void
    {
        $this->assertEquals('tag', $this->aggregator->getName());
    }

    /** @test */
    public function it_has_correct_priority(): void
    {
        $this->assertEquals(70, $this->aggregator->getPriority());
    }

    /** @test */
    public function it_has_no_dependencies(): void
    {
        $this->assertEquals([], $this->aggregator->getDependencies());
    }

    /** @test */
    public function it_has_timeout(): void
    {
        $this->assertEquals(3000, $this->aggregator->getTimeout());
    }

    /** @test */
    public function it_has_empty_array_fallback(): void
    {
        $this->assertEquals([], $this->aggregator->getFallback());
    }

    /** @test */
    public function it_supports_context_with_tags(): void
    {
        $context = new AggregatorContext('1', 'news', ['tags' => [['id' => 't1']]]);
        $this->assertTrue($this->aggregator->supports($context));
    }

    /** @test */
    public function it_does_not_support_context_without_tags(): void
    {
        $context = new AggregatorContext('1', 'news', []);
        $this->assertFalse($this->aggregator->supports($context));
    }

    /** @test */
    public function it_does_not_support_context_with_empty_tags(): void
    {
        $context = new AggregatorContext('1', 'news', ['tags' => []]);
        $this->assertFalse($this->aggregator->supports($context));
    }

    /** @test */
    public function it_aggregates_tags_from_client(): void
    {
        $this->mockClient->method('findTagByIdAsync')
            ->willReturnCallback(function ($id) {
                return new FulfilledPromise(['id' => $id, 'name' => "Tag $id"]);
            });

        $context = new AggregatorContext('1', 'news', [
            'tags' => [['id' => 't1'], ['id' => 't2']],
        ]);

        $promise = $this->aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_returns_empty_array_when_no_tags(): void
    {
        $context = new AggregatorContext('1', 'news', ['tags' => []]);

        $promise = $this->aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_filters_null_results(): void
    {
        $this->mockClient->method('findTagByIdAsync')
            ->willReturnCallback(function ($id) {
                if ($id === 't2') {
                    return new FulfilledPromise(null);
                }
                return new FulfilledPromise(['id' => $id, 'name' => "Tag $id"]);
            });

        $context = new AggregatorContext('1', 'news', [
            'tags' => [['id' => 't1'], ['id' => 't2'], ['id' => 't3']],
        ]);

        $promise = $this->aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_handles_client_failure_gracefully(): void
    {
        $this->mockClient->method('findTagByIdAsync')
            ->willReturn(new RejectedPromise(new \Exception('Service unavailable')));

        $context = new AggregatorContext('1', 'news', [
            'tags' => [['id' => 't1']],
        ]);

        $promise = $this->aggregator->aggregate($context);

        $this->expectException(\Exception::class);
        $promise->wait();
    }
}
