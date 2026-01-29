<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Transformer;

use App\Application\Transformer\TransformationPipeline;
use App\Application\Transformer\TransformerRegistry;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\ValueObject\TransformationContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Application\Transformer\TransformationPipeline
 */
final class TransformationPipelineTest extends TestCase
{
    private TransformerRegistry $registry;
    private TransformationPipeline $pipeline;

    protected function setUp(): void
    {
        $this->registry = new TransformerRegistry();
        $this->pipeline = new TransformationPipeline($this->registry);
    }

    /** @test */
    public function it_transforms_empty_results(): void
    {
        $context = new AggregatorContext('1', 'news', []);
        $output = $this->pipeline->transform([], $context);

        $this->assertEmpty($output);
    }

    /** @test */
    public function it_passes_through_data_without_transformer(): void
    {
        $results = [
            'tag' => AggregatorResult::success('tag', ['tag1', 'tag2'], 0.01),
        ];
        $context = new AggregatorContext('1', 'news', []);

        $output = $this->pipeline->transform($results, $context);

        $this->assertEquals(['tag1', 'tag2'], $output['tag']);
    }

    /** @test */
    public function it_uses_transformer_when_available(): void
    {
        $transformer = $this->createMock(JsonTransformerInterface::class);
        $transformer->method('getType')->willReturn('tag');
        $transformer->method('supports')->willReturn(true);
        $transformer->method('transform')->willReturn([
            ['id' => 't1', 'name' => 'Tag 1'],
            ['id' => 't2', 'name' => 'Tag 2'],
        ]);

        $this->registry->register($transformer);

        $results = [
            'tag' => AggregatorResult::success('tag', ['t1', 't2'], 0.01),
        ];
        $context = new AggregatorContext('1', 'news', []);

        $output = $this->pipeline->transform($results, $context);

        $this->assertCount(2, $output['tag']);
        $this->assertEquals('Tag 1', $output['tag'][0]['name']);
    }

    /** @test */
    public function it_returns_fallback_for_failed_results(): void
    {
        $results = [
            'tag' => AggregatorResult::failure('tag', 'Timeout', ['fallback'], 5.0),
        ];
        $context = new AggregatorContext('1', 'news', []);

        $output = $this->pipeline->transform($results, $context);

        $this->assertEquals(['fallback'], $output['tag']);
    }

    /** @test */
    public function it_transforms_multiple_results(): void
    {
        $tagTransformer = $this->createMock(JsonTransformerInterface::class);
        $tagTransformer->method('getType')->willReturn('tag');
        $tagTransformer->method('supports')->willReturnCallback(
            fn($data) => is_array($data) && isset($data[0]) && str_starts_with($data[0], 'tag')
        );
        $tagTransformer->method('transform')->willReturn(['transformed_tags']);

        $this->registry->register($tagTransformer);

        $results = [
            'tag' => AggregatorResult::success('tag', ['tag1'], 0.01),
            'multimedia' => AggregatorResult::success('multimedia', ['photo1'], 0.02),
        ];
        $context = new AggregatorContext('1', 'news', []);

        $output = $this->pipeline->transform($results, $context);

        $this->assertCount(2, $output);
        $this->assertArrayHasKey('tag', $output);
        $this->assertArrayHasKey('multimedia', $output);
    }

    /** @test */
    public function it_provides_transformation_context_to_transformers(): void
    {
        $capturedContext = null;

        $transformer = $this->createMock(JsonTransformerInterface::class);
        $transformer->method('getType')->willReturn('tag');
        $transformer->method('supports')->willReturn(true);
        $transformer->method('transform')->willReturnCallback(
            function ($data, $ctx) use (&$capturedContext) {
                $capturedContext = $ctx;
                return $data;
            }
        );

        $this->registry->register($transformer);

        $tagResult = AggregatorResult::success('tag', ['tag1'], 0.01);
        $multimediaResult = AggregatorResult::success('multimedia', ['photo1'], 0.02);

        $results = [
            'tag' => $tagResult,
            'multimedia' => $multimediaResult,
        ];
        $context = new AggregatorContext('1', 'news', []);

        $this->pipeline->transform($results, $context);

        $this->assertInstanceOf(TransformationContext::class, $capturedContext);
        $this->assertEquals('1', $capturedContext->getEditorialId());
        $this->assertSame($tagResult, $capturedContext->getResult('tag'));
        $this->assertSame($multimediaResult, $capturedContext->getResult('multimedia'));
    }

    /** @test */
    public function it_skips_transformation_for_null_data(): void
    {
        $results = [
            'tag' => AggregatorResult::success('tag', null, 0.01),
        ];
        $context = new AggregatorContext('1', 'news', []);

        $output = $this->pipeline->transform($results, $context);

        $this->assertNull($output['tag']);
    }
}
