<?php

declare(strict_types=1);

namespace App\Tests\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Domain\Transformer\ValueObject\TransformationContext;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use PHPUnit\Framework\TestCase;

/**
 * Contract tests to ensure API responses meet mobile app requirements.
 *
 * These tests verify that the data structures returned by aggregators
 * and transformers meet the contracts expected by consuming applications.
 */
final class MobileAppContractTest extends TestCase
{
    /**
     * Required fields in editorial response for mobile apps.
     */
    private array $requiredEditorialFields = [
        'id',
        'title',
        'headline',
        'url',
        'publishedAt',
    ];

    /**
     * Required fields in section response.
     */
    private array $requiredSectionFields = [
        'id',
        'name',
        'url',
    ];

    /**
     * Required fields in tag response.
     */
    private array $requiredTagFields = [
        'id',
        'name',
    ];

    /** @test */
    public function aggregator_result_provides_required_methods(): void
    {
        $result = AggregatorResult::success('tag', ['tag1', 'tag2'], 0.05);

        // Verify required methods exist
        $this->assertIsString($result->getAggregatorName());
        $this->assertIsBool($result->isSuccess());
        $this->assertIsFloat($result->getExecutionTime());

        // getData can return any type
        $this->assertNotNull($result->getData());
    }

    /** @test */
    public function aggregator_result_success_has_correct_structure(): void
    {
        $data = ['id' => 't1', 'name' => 'Tag 1'];
        $result = AggregatorResult::success('tag', $data, 0.05);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertNull($result->getError());
        $this->assertEquals($data, $result->getData());
    }

    /** @test */
    public function aggregator_result_failure_has_correct_structure(): void
    {
        $fallback = [];
        $result = AggregatorResult::failure('tag', 'Service unavailable', $fallback, 5.0);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertIsString($result->getError());
        $this->assertEquals($fallback, $result->getData());
    }

    /** @test */
    public function transformation_context_provides_all_results(): void
    {
        $aggregatorContext = new AggregatorContext('4433', 'news', []);
        $results = [
            'tag' => AggregatorResult::success('tag', ['tag1'], 0.01),
            'multimedia' => AggregatorResult::success('multimedia', ['photo1'], 0.02),
        ];

        $context = new TransformationContext($aggregatorContext, $results);

        // Can access all results
        $this->assertEquals($results, $context->getAllResults());

        // Can access individual results
        $this->assertInstanceOf(AggregatorResult::class, $context->getResult('tag'));
        $this->assertInstanceOf(AggregatorResult::class, $context->getResult('multimedia'));

        // Returns null for missing
        $this->assertNull($context->getResult('nonexistent'));
    }

    /** @test */
    public function transformation_context_provides_result_data(): void
    {
        $aggregatorContext = new AggregatorContext('4433', 'news', []);
        $results = [
            'tag' => AggregatorResult::success('tag', ['tag1', 'tag2'], 0.01),
        ];

        $context = new TransformationContext($aggregatorContext, $results);

        $this->assertEquals(['tag1', 'tag2'], $context->getResultData('tag'));
        $this->assertNull($context->getResultData('nonexistent'));
    }

    /** @test */
    public function transformation_context_checks_successful_results(): void
    {
        $aggregatorContext = new AggregatorContext('4433', 'news', []);
        $results = [
            'tag' => AggregatorResult::success('tag', ['tag1'], 0.01),
            'multimedia' => AggregatorResult::failure('multimedia', 'Error', [], 0.02),
        ];

        $context = new TransformationContext($aggregatorContext, $results);

        $this->assertTrue($context->hasSuccessfulResult('tag'));
        $this->assertFalse($context->hasSuccessfulResult('multimedia'));
        $this->assertFalse($context->hasSuccessfulResult('nonexistent'));
    }

    /** @test */
    public function aggregator_context_field_types_are_correct(): void
    {
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: ['title' => 'Test'],
            resolvedData: ['tags' => []],
            metadata: ['requestId' => 'req-123']
        );

        // Verify field types
        $this->assertIsString($context->getEditorialId());
        $this->assertIsString($context->getEditorialType());
        $this->assertIsArray($context->getRawData());
        $this->assertIsArray($context->getResolvedData());
        $this->assertIsArray($context->getMetadata());
    }

    /** @test */
    public function execution_time_is_in_expected_format(): void
    {
        $result = AggregatorResult::success('tag', [], 0.123456);

        // Execution time in seconds (float)
        $this->assertEquals(0.123456, $result->getExecutionTime());

        // Can convert to milliseconds
        $this->assertEquals(123.456, $result->getExecutionTimeMs());
    }

    /** @test */
    public function transformation_context_provides_editorial_info(): void
    {
        $aggregatorContext = new AggregatorContext('4433', 'news', []);
        $context = new TransformationContext($aggregatorContext, []);

        $this->assertEquals('4433', $context->getEditorialId());
        $this->assertEquals('news', $context->getEditorialType());
    }

    /** @test */
    public function options_are_accessible_in_transformation_context(): void
    {
        $aggregatorContext = new AggregatorContext('4433', 'news', []);
        $options = ['format' => 'json', 'version' => 'v2'];
        $context = new TransformationContext($aggregatorContext, [], $options);

        $this->assertEquals($options, $context->getOptions());
        $this->assertEquals('json', $context->getOption('format'));
        $this->assertEquals('v2', $context->getOption('version'));
        $this->assertEquals('default', $context->getOption('missing', 'default'));
    }
}
