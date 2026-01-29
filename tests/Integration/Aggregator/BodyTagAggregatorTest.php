<?php

declare(strict_types=1);

namespace App\Tests\Integration\Aggregator;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Infrastructure\Aggregator\BodyTagAggregator;
use App\Infrastructure\Transformer\BodyElement\BodyElementTransformerHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \App\Infrastructure\Aggregator\BodyTagAggregator
 */
final class BodyTagAggregatorTest extends TestCase
{
    private BodyElementTransformerHandler $transformerHandler;
    private BodyTagAggregator $aggregator;

    protected function setUp(): void
    {
        $this->transformerHandler = new BodyElementTransformerHandler(new NullLogger());
        $this->aggregator = new BodyTagAggregator($this->transformerHandler, new NullLogger());
    }

    /** @test */
    public function it_has_correct_name(): void
    {
        $this->assertEquals('bodyTag', $this->aggregator->getName());
    }

    /** @test */
    public function it_has_correct_priority(): void
    {
        $this->assertEquals(60, $this->aggregator->getPriority());
    }

    /** @test */
    public function it_depends_on_multimedia(): void
    {
        $this->assertEquals(['multimedia'], $this->aggregator->getDependencies());
    }

    /** @test */
    public function it_supports_context_with_body_elements(): void
    {
        $context = new AggregatorContext('1', 'news', [
            'body' => ['bodyElements' => [['type' => 'paragraph']]],
        ]);

        $this->assertTrue($this->aggregator->supports($context));
    }

    /** @test */
    public function it_supports_context_with_flat_body_elements(): void
    {
        $context = new AggregatorContext('1', 'news', [
            'bodyElements' => [['type' => 'paragraph']],
        ]);

        $this->assertTrue($this->aggregator->supports($context));
    }

    /** @test */
    public function it_does_not_support_context_without_body(): void
    {
        $context = new AggregatorContext('1', 'news', ['title' => 'Test']);

        $this->assertFalse($this->aggregator->supports($context));
    }

    /** @test */
    public function it_aggregates_body_elements(): void
    {
        $context = new AggregatorContext('1', 'news', [
            'body' => [
                'bodyElements' => [
                    ['type' => 'paragraph', 'content' => 'First paragraph'],
                    ['type' => 'paragraph', 'content' => 'Second paragraph'],
                ],
            ],
        ]);

        $result = $this->aggregator->aggregate($context);

        $this->assertInstanceOf(AggregatorResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertCount(2, $result->getData());
    }

    /** @test */
    public function it_adds_index_to_elements(): void
    {
        $context = new AggregatorContext('1', 'news', [
            'body' => [
                'bodyElements' => [
                    ['type' => 'paragraph', 'content' => 'First'],
                    ['type' => 'subHead', 'content' => 'Header'],
                ],
            ],
        ]);

        $result = $this->aggregator->aggregate($context);
        $data = $result->getData();

        $this->assertEquals(0, $data[0]['index']);
        $this->assertEquals(1, $data[1]['index']);
    }

    /** @test */
    public function it_uses_resolved_multimedia(): void
    {
        $resolvedMultimedia = [
            'photo1' => ['id' => 'photo1', 'url' => 'https://example.com/1.jpg'],
        ];

        $context = new AggregatorContext('1', 'news', [
            'body' => [
                'bodyElements' => [
                    [
                        'type' => 'bodyTagPicture',
                        'multimediaId' => 'photo1',
                    ],
                ],
            ],
        ], ['multimedia' => $resolvedMultimedia]);

        $result = $this->aggregator->aggregate($context);

        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function it_handles_empty_body_elements(): void
    {
        $context = new AggregatorContext('1', 'news', [
            'body' => ['bodyElements' => []],
        ]);

        $result = $this->aggregator->aggregate($context);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals([], $result->getData());
    }

    /** @test */
    public function it_continues_on_element_transformation_error(): void
    {
        // Create a context with a mix of valid and invalid elements
        $context = new AggregatorContext('1', 'news', [
            'body' => [
                'bodyElements' => [
                    ['type' => 'paragraph', 'content' => 'Valid'],
                    ['type' => 'unknownType'], // Unknown type - might fail
                    ['type' => 'paragraph', 'content' => 'Also valid'],
                ],
            ],
        ]);

        $result = $this->aggregator->aggregate($context);

        $this->assertTrue($result->isSuccess());
        // Should have at least some elements transformed
        $this->assertGreaterThanOrEqual(2, count($result->getData()));
    }

    /** @test */
    public function it_measures_execution_time(): void
    {
        $context = new AggregatorContext('1', 'news', [
            'body' => ['bodyElements' => []],
        ]);

        $result = $this->aggregator->aggregate($context);

        $this->assertGreaterThanOrEqual(0.0, $result->getExecutionTime());
    }
}
