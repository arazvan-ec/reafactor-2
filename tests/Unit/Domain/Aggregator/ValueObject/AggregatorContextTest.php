<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Aggregator\ValueObject;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Domain\Aggregator\ValueObject\AggregatorContext
 */
final class AggregatorContextTest extends TestCase
{
    /** @test */
    public function it_creates_context_with_required_data(): void
    {
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: ['title' => 'Test']
        );

        $this->assertEquals('4433', $context->getEditorialId());
        $this->assertEquals('news', $context->getEditorialType());
        $this->assertEquals(['title' => 'Test'], $context->getRawData());
    }

    /** @test */
    public function it_creates_context_with_all_parameters(): void
    {
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: ['title' => 'Test'],
            resolvedData: ['tags' => ['tag1', 'tag2']],
            metadata: ['requestId' => 'req-123']
        );

        $this->assertEquals(['tags' => ['tag1', 'tag2']], $context->getResolvedData());
        $this->assertEquals(['requestId' => 'req-123'], $context->getMetadata());
    }

    /** @test */
    public function it_is_immutable_when_adding_resolved_data(): void
    {
        $original = new AggregatorContext('1', 'news', []);
        $modified = $original->withResolvedData('tags', ['tag1']);

        $this->assertNotSame($original, $modified);
        $this->assertEmpty($original->getResolvedData());
        $this->assertEquals(['tags' => ['tag1']], $modified->getResolvedData());
    }

    /** @test */
    public function it_preserves_existing_resolved_data_when_adding_new(): void
    {
        $context = new AggregatorContext('1', 'news', [], ['a' => 1]);
        $modified = $context->withResolvedData('b', 2);

        $this->assertEquals(['a' => 1, 'b' => 2], $modified->getResolvedData());
    }

    /** @test */
    public function it_returns_null_for_missing_resolved_key(): void
    {
        $context = new AggregatorContext('1', 'news', []);
        $this->assertNull($context->getResolvedDataByKey('nonexistent'));
    }

    /** @test */
    public function it_returns_resolved_data_by_key(): void
    {
        $context = new AggregatorContext('1', 'news', [], ['tags' => ['tag1']]);
        $this->assertEquals(['tag1'], $context->getResolvedDataByKey('tags'));
    }

    /** @test */
    public function it_is_immutable_when_adding_metadata(): void
    {
        $original = new AggregatorContext('1', 'news', []);
        $modified = $original->withMetadata('requestId', 'req-123');

        $this->assertNotSame($original, $modified);
        $this->assertEmpty($original->getMetadata());
        $this->assertEquals(['requestId' => 'req-123'], $modified->getMetadata());
    }

    /** @test */
    public function it_preserves_existing_metadata_when_adding_new(): void
    {
        $context = new AggregatorContext('1', 'news', [], [], ['c' => 3]);
        $modified = $context->withMetadata('d', 4);

        $this->assertEquals(['c' => 3, 'd' => 4], $modified->getMetadata());
    }

    /** @test */
    public function it_returns_raw_data_by_key(): void
    {
        $context = new AggregatorContext('1', 'news', ['title' => 'Test', 'id' => 123]);

        $this->assertEquals('Test', $context->getRawDataByKey('title'));
        $this->assertEquals(123, $context->getRawDataByKey('id'));
        $this->assertNull($context->getRawDataByKey('nonexistent'));
    }

    /** @test */
    public function it_returns_metadata_by_key(): void
    {
        $context = new AggregatorContext('1', 'news', [], [], ['requestId' => 'req-123']);

        $this->assertEquals('req-123', $context->getMetadataByKey('requestId'));
        $this->assertNull($context->getMetadataByKey('nonexistent'));
    }

    /** @test */
    public function it_checks_if_raw_data_exists(): void
    {
        $context = new AggregatorContext('1', 'news', [
            'tags' => ['tag1'],
            'emptyArray' => [],
            'nullValue' => null,
        ]);

        $this->assertTrue($context->hasRawData('tags'));
        $this->assertFalse($context->hasRawData('emptyArray'));
        $this->assertFalse($context->hasRawData('nullValue'));
        $this->assertFalse($context->hasRawData('nonexistent'));
    }

    /** @test */
    public function it_checks_if_resolved_data_exists(): void
    {
        $context = new AggregatorContext('1', 'news', [], [
            'tags' => ['tag1'],
            'nullValue' => null,
        ]);

        $this->assertTrue($context->hasResolvedData('tags'));
        $this->assertTrue($context->hasResolvedData('nullValue')); // array_key_exists
        $this->assertFalse($context->hasResolvedData('nonexistent'));
    }

    /** @test */
    public function it_allows_chaining_with_methods(): void
    {
        $context = new AggregatorContext('1', 'news', []);

        $modified = $context
            ->withResolvedData('tags', ['tag1'])
            ->withResolvedData('multimedia', ['photo1'])
            ->withMetadata('requestId', 'req-123');

        $this->assertEquals(['tags', 'multimedia'], array_keys($modified->getResolvedData()));
        $this->assertEquals(['requestId' => 'req-123'], $modified->getMetadata());
    }
}
