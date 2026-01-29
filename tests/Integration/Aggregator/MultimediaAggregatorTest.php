<?php

declare(strict_types=1);

namespace App\Tests\Integration\Aggregator;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Infrastructure\Aggregator\MultimediaAggregator;
use App\Infrastructure\Client\Contract\QueryMultimediaClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \App\Infrastructure\Aggregator\MultimediaAggregator
 */
final class MultimediaAggregatorTest extends TestCase
{
    private QueryMultimediaClientInterface $mockClient;
    private MultimediaAggregator $aggregator;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(QueryMultimediaClientInterface::class);
        $this->aggregator = new MultimediaAggregator($this->mockClient, new NullLogger());
    }

    /** @test */
    public function it_has_correct_name(): void
    {
        $this->assertEquals('multimedia', $this->aggregator->getName());
    }

    /** @test */
    public function it_has_correct_priority(): void
    {
        $this->assertEquals(90, $this->aggregator->getPriority());
    }

    /** @test */
    public function it_has_no_dependencies(): void
    {
        $this->assertEquals([], $this->aggregator->getDependencies());
    }

    /** @test */
    public function it_has_timeout(): void
    {
        $this->assertEquals(5000, $this->aggregator->getTimeout());
    }

    /** @test */
    public function it_has_array_fallback(): void
    {
        $this->assertEquals([], $this->aggregator->getFallback());
    }

    /** @test */
    public function it_supports_context_with_multimedia_id(): void
    {
        $context = new AggregatorContext('1', 'news', ['multimediaId' => 'm1']);
        $this->assertTrue($this->aggregator->supports($context));
    }

    /** @test */
    public function it_supports_context_with_opening_multimedia_id(): void
    {
        $context = new AggregatorContext('1', 'news', ['openingMultimediaId' => 'o1']);
        $this->assertTrue($this->aggregator->supports($context));
    }

    /** @test */
    public function it_supports_context_with_meta_image_id(): void
    {
        $context = new AggregatorContext('1', 'news', ['metaImageId' => 'meta1']);
        $this->assertTrue($this->aggregator->supports($context));
    }

    /** @test */
    public function it_does_not_support_context_without_multimedia(): void
    {
        $context = new AggregatorContext('1', 'news', ['title' => 'Test']);
        $this->assertFalse($this->aggregator->supports($context));
    }

    /** @test */
    public function it_aggregates_main_multimedia(): void
    {
        $this->mockClient->method('findByIdAsync')
            ->willReturn(new FulfilledPromise([
                'id' => 'm1',
                'type' => 'photo',
                'url' => 'https://example.com/photo.jpg',
            ]));

        $context = new AggregatorContext('1', 'news', ['multimediaId' => 'm1']);

        $promise = $this->aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertArrayHasKey('main', $result);
        $this->assertEquals('m1', $result['main']['id']);
    }

    /** @test */
    public function it_aggregates_multiple_multimedia_types(): void
    {
        $this->mockClient->method('findByIdAsync')
            ->willReturnCallback(function ($id) {
                return new FulfilledPromise(['id' => $id, 'type' => 'photo']);
            });

        $context = new AggregatorContext('1', 'news', [
            'multimediaId' => 'm1',
            'openingMultimediaId' => 'o1',
            'metaImageId' => 'meta1',
        ]);

        $promise = $this->aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertArrayHasKey('main', $result);
        $this->assertArrayHasKey('opening', $result);
        $this->assertArrayHasKey('meta', $result);
    }

    /** @test */
    public function it_returns_empty_for_no_multimedia_ids(): void
    {
        $context = new AggregatorContext('1', 'news', []);

        $promise = $this->aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_handles_partial_failure(): void
    {
        $callCount = 0;
        $this->mockClient->method('findByIdAsync')
            ->willReturnCallback(function ($id) use (&$callCount) {
                $callCount++;
                if ($id === 'o1') {
                    return new RejectedPromise(new \Exception('Not found'));
                }
                return new FulfilledPromise(['id' => $id]);
            });

        $context = new AggregatorContext('1', 'news', [
            'multimediaId' => 'm1',
            'openingMultimediaId' => 'o1',
        ]);

        $promise = $this->aggregator->aggregate($context);
        $result = $promise->wait();

        // Main should succeed, opening should fail gracefully
        $this->assertArrayHasKey('main', $result);
    }
}
