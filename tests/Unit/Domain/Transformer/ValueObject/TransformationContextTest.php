<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Transformer\ValueObject;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Domain\Transformer\ValueObject\TransformationContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Domain\Transformer\ValueObject\TransformationContext
 */
final class TransformationContextTest extends TestCase
{
    private AggregatorContext $aggregatorContext;

    protected function setUp(): void
    {
        $this->aggregatorContext = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: ['title' => 'Test']
        );
    }

    /** @test */
    public function it_creates_context_with_required_parameters(): void
    {
        $context = new TransformationContext($this->aggregatorContext, []);

        $this->assertSame($this->aggregatorContext, $context->getAggregatorContext());
        $this->assertEquals([], $context->getAllResults());
        $this->assertEquals([], $context->getOptions());
    }

    /** @test */
    public function it_returns_aggregator_context(): void
    {
        $context = new TransformationContext($this->aggregatorContext, []);

        $this->assertEquals('4433', $context->getEditorialId());
        $this->assertEquals('news', $context->getEditorialType());
    }

    /** @test */
    public function it_returns_all_results(): void
    {
        $tagResult = AggregatorResult::success('tag', ['tag1'], 0.01);
        $multimediaResult = AggregatorResult::success('multimedia', ['photo1'], 0.02);

        $context = new TransformationContext(
            $this->aggregatorContext,
            ['tag' => $tagResult, 'multimedia' => $multimediaResult]
        );

        $results = $context->getAllResults();
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('tag', $results);
        $this->assertArrayHasKey('multimedia', $results);
    }

    /** @test */
    public function it_returns_result_by_name(): void
    {
        $tagResult = AggregatorResult::success('tag', ['tag1'], 0.01);

        $context = new TransformationContext(
            $this->aggregatorContext,
            ['tag' => $tagResult]
        );

        $this->assertSame($tagResult, $context->getResult('tag'));
        $this->assertNull($context->getResult('nonexistent'));
    }

    /** @test */
    public function it_returns_result_data_by_name(): void
    {
        $tagResult = AggregatorResult::success('tag', ['tag1', 'tag2'], 0.01);

        $context = new TransformationContext(
            $this->aggregatorContext,
            ['tag' => $tagResult]
        );

        $this->assertEquals(['tag1', 'tag2'], $context->getResultData('tag'));
        $this->assertNull($context->getResultData('nonexistent'));
    }

    /** @test */
    public function it_checks_for_successful_result(): void
    {
        $successResult = AggregatorResult::success('tag', ['tag1'], 0.01);
        $failureResult = AggregatorResult::failure('multimedia', 'Error', [], 0.02);

        $context = new TransformationContext(
            $this->aggregatorContext,
            ['tag' => $successResult, 'multimedia' => $failureResult]
        );

        $this->assertTrue($context->hasSuccessfulResult('tag'));
        $this->assertFalse($context->hasSuccessfulResult('multimedia'));
        $this->assertFalse($context->hasSuccessfulResult('nonexistent'));
    }

    /** @test */
    public function it_returns_options(): void
    {
        $context = new TransformationContext(
            $this->aggregatorContext,
            [],
            ['format' => 'json', 'includeMetadata' => true]
        );

        $this->assertEquals(['format' => 'json', 'includeMetadata' => true], $context->getOptions());
    }

    /** @test */
    public function it_returns_option_by_key(): void
    {
        $context = new TransformationContext(
            $this->aggregatorContext,
            [],
            ['format' => 'json']
        );

        $this->assertEquals('json', $context->getOption('format'));
        $this->assertNull($context->getOption('nonexistent'));
    }

    /** @test */
    public function it_returns_default_value_for_missing_option(): void
    {
        $context = new TransformationContext($this->aggregatorContext, []);

        $this->assertEquals('default', $context->getOption('nonexistent', 'default'));
        $this->assertEquals(123, $context->getOption('missing', 123));
    }

    /** @test */
    public function it_delegates_editorial_id_to_aggregator_context(): void
    {
        $context = new TransformationContext($this->aggregatorContext, []);

        $this->assertEquals(
            $this->aggregatorContext->getEditorialId(),
            $context->getEditorialId()
        );
    }

    /** @test */
    public function it_delegates_editorial_type_to_aggregator_context(): void
    {
        $context = new TransformationContext($this->aggregatorContext, []);

        $this->assertEquals(
            $this->aggregatorContext->getEditorialType(),
            $context->getEditorialType()
        );
    }
}
