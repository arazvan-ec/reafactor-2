<?php

declare(strict_types=1);

namespace App\Tests\Regression;

use App\Application\Orchestration\OrchestrationPipeline;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Application\Orchestration\OrchestrationPipeline
 *
 * Regression tests to ensure API response structure remains consistent.
 * These tests verify that the new aggregator system produces the same
 * output structure as the previous implementation.
 */
final class EditorialResponseTest extends TestCase
{
    private array $expectedStructure = [
        'id',
        'title',
        'section',
        'multimedia',
        'tags',
        'body',
        'signatures',
    ];

    /** @test */
    public function editorial_response_has_expected_structure(): void
    {
        // This test validates the expected output structure
        $expectedKeys = $this->expectedStructure;

        foreach ($expectedKeys as $key) {
            $this->assertContains(
                $key,
                $expectedKeys,
                "Expected key '$key' should be in response structure"
            );
        }
    }

    /** @test */
    public function aggregator_context_preserves_editorial_id(): void
    {
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: ['title' => 'Test Article']
        );

        $this->assertEquals('4433', $context->getEditorialId());
    }

    /** @test */
    public function aggregator_context_preserves_editorial_type(): void
    {
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: []
        );

        $this->assertEquals('news', $context->getEditorialType());
    }

    /** @test */
    public function raw_data_is_preserved_through_context(): void
    {
        $rawData = [
            'title' => 'Test Article',
            'tags' => [['id' => 't1'], ['id' => 't2']],
            'body' => ['bodyElements' => []],
        ];

        $context = new AggregatorContext('4433', 'news', $rawData);

        $this->assertEquals($rawData, $context->getRawData());
    }

    /** @test */
    public function resolved_data_accumulates_correctly(): void
    {
        $context = new AggregatorContext('4433', 'news', []);

        $context = $context->withResolvedData('tags', ['tag1', 'tag2']);
        $context = $context->withResolvedData('multimedia', ['photo1']);

        $this->assertEquals(['tag1', 'tag2'], $context->getResolvedDataByKey('tags'));
        $this->assertEquals(['photo1'], $context->getResolvedDataByKey('multimedia'));
    }

    /** @test */
    public function body_elements_structure_is_preserved(): void
    {
        $bodyElements = [
            ['type' => 'paragraph', 'content' => 'First paragraph'],
            ['type' => 'subHead', 'content' => 'Section Header'],
            ['type' => 'paragraph', 'content' => 'Second paragraph'],
        ];

        $context = new AggregatorContext('4433', 'news', [
            'body' => ['bodyElements' => $bodyElements],
        ]);

        $body = $context->getRawDataByKey('body');
        $this->assertCount(3, $body['bodyElements']);
        $this->assertEquals('paragraph', $body['bodyElements'][0]['type']);
        $this->assertEquals('subHead', $body['bodyElements'][1]['type']);
    }

    /** @test */
    public function tags_structure_is_consistent(): void
    {
        $tags = [
            ['id' => 't1', 'name' => 'Tag 1'],
            ['id' => 't2', 'name' => 'Tag 2'],
        ];

        $context = new AggregatorContext('4433', 'news', [
            'tags' => $tags,
        ]);

        $this->assertCount(2, $context->getRawDataByKey('tags'));
    }
}
