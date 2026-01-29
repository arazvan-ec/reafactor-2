<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Aggregator\ValueObject;

use App\Domain\Aggregator\ValueObject\AggregatorResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Domain\Aggregator\ValueObject\AggregatorResult
 */
final class AggregatorResultTest extends TestCase
{
    /** @test */
    public function it_creates_success_result(): void
    {
        $result = AggregatorResult::success('tag', ['data'], 0.05);

        $this->assertEquals('tag', $result->getAggregatorName());
        $this->assertEquals(['data'], $result->getData());
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertNull($result->getError());
        $this->assertEquals(0.05, $result->getExecutionTime());
    }

    /** @test */
    public function it_creates_failure_result(): void
    {
        $result = AggregatorResult::failure('tag', 'Timeout', [], 5.0);

        $this->assertEquals('tag', $result->getAggregatorName());
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Timeout', $result->getError());
        $this->assertEquals([], $result->getData()); // Fallback
        $this->assertEquals(5.0, $result->getExecutionTime());
    }

    /** @test */
    public function it_calculates_execution_time_in_milliseconds(): void
    {
        $result = AggregatorResult::success('tag', [], 0.123);

        $this->assertEquals(123.0, $result->getExecutionTimeMs());
    }

    /** @test */
    public function it_supports_various_data_types(): void
    {
        // Array data
        $result1 = AggregatorResult::success('tag', ['item1', 'item2'], 0.01);
        $this->assertEquals(['item1', 'item2'], $result1->getData());

        // Object data
        $obj = new \stdClass();
        $obj->name = 'test';
        $result2 = AggregatorResult::success('tag', $obj, 0.01);
        $this->assertEquals($obj, $result2->getData());

        // Null data
        $result3 = AggregatorResult::success('tag', null, 0.01);
        $this->assertNull($result3->getData());

        // String data
        $result4 = AggregatorResult::success('tag', 'string value', 0.01);
        $this->assertEquals('string value', $result4->getData());
    }

    /** @test */
    public function it_supports_fallback_data_on_failure(): void
    {
        $fallback = ['default' => 'value'];
        $result = AggregatorResult::failure('tag', 'Service unavailable', $fallback, 3.0);

        $this->assertEquals($fallback, $result->getData());
        $this->assertEquals('Service unavailable', $result->getError());
    }

    /** @test */
    public function it_allows_empty_error_message(): void
    {
        $result = AggregatorResult::failure('tag', '', [], 1.0);

        $this->assertEquals('', $result->getError());
        $this->assertFalse($result->isSuccess());
    }

    /** @test */
    public function it_allows_zero_execution_time(): void
    {
        $result = AggregatorResult::success('tag', [], 0.0);

        $this->assertEquals(0.0, $result->getExecutionTime());
        $this->assertEquals(0.0, $result->getExecutionTimeMs());
    }

    /** @test */
    public function it_handles_very_small_execution_times(): void
    {
        $result = AggregatorResult::success('tag', [], 0.000001);

        $this->assertEquals(0.000001, $result->getExecutionTime());
        $this->assertEquals(0.001, $result->getExecutionTimeMs());
    }
}
