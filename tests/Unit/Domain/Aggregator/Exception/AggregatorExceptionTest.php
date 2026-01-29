<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Aggregator\Exception;

use App\Domain\Aggregator\Exception\AggregatorException;
use App\Domain\Aggregator\Exception\AggregatorNotFoundException;
use App\Domain\Aggregator\Exception\AggregatorTimeoutException;
use App\Domain\Aggregator\Exception\CircularDependencyException;
use App\Domain\Aggregator\Exception\DuplicateAggregatorException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Domain\Aggregator\Exception\AggregatorException
 * @covers \App\Domain\Aggregator\Exception\AggregatorNotFoundException
 * @covers \App\Domain\Aggregator\Exception\AggregatorTimeoutException
 * @covers \App\Domain\Aggregator\Exception\CircularDependencyException
 * @covers \App\Domain\Aggregator\Exception\DuplicateAggregatorException
 */
final class AggregatorExceptionTest extends TestCase
{
    // AggregatorException tests

    /** @test */
    public function base_exception_extends_runtime_exception(): void
    {
        $exception = new AggregatorException('Test error');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test error', $exception->getMessage());
    }

    /** @test */
    public function base_exception_has_empty_context_by_default(): void
    {
        $exception = new AggregatorException('Test error');

        $this->assertEquals([], $exception->getContext());
    }

    // AggregatorNotFoundException tests

    /** @test */
    public function aggregator_not_found_exception_has_correct_message(): void
    {
        $exception = new AggregatorNotFoundException('tag');

        $this->assertStringContainsString('tag', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    /** @test */
    public function aggregator_not_found_exception_has_name_in_context(): void
    {
        $exception = new AggregatorNotFoundException('multimedia');

        $this->assertEquals(['aggregator_name' => 'multimedia'], $exception->getContext());
    }

    /** @test */
    public function aggregator_not_found_exception_extends_base(): void
    {
        $exception = new AggregatorNotFoundException('tag');

        $this->assertInstanceOf(AggregatorException::class, $exception);
    }

    // CircularDependencyException tests

    /** @test */
    public function circular_dependency_exception_shows_cycle(): void
    {
        $exception = new CircularDependencyException(['a', 'b', 'c', 'a']);

        $this->assertStringContainsString('a -> b -> c -> a', $exception->getMessage());
        $this->assertStringContainsString('Circular dependency', $exception->getMessage());
    }

    /** @test */
    public function circular_dependency_exception_has_cycle_in_context(): void
    {
        $cycle = ['tag', 'multimedia', 'tag'];
        $exception = new CircularDependencyException($cycle);

        $this->assertEquals(['cycle' => $cycle], $exception->getContext());
    }

    /** @test */
    public function circular_dependency_exception_extends_base(): void
    {
        $exception = new CircularDependencyException(['a', 'b']);

        $this->assertInstanceOf(AggregatorException::class, $exception);
    }

    /** @test */
    public function circular_dependency_exception_handles_two_element_cycle(): void
    {
        $exception = new CircularDependencyException(['a', 'b']);

        $this->assertStringContainsString('a -> b', $exception->getMessage());
    }

    // DuplicateAggregatorException tests

    /** @test */
    public function duplicate_aggregator_exception_has_correct_message(): void
    {
        $exception = new DuplicateAggregatorException('tag');

        $this->assertStringContainsString('tag', $exception->getMessage());
        $this->assertStringContainsString('already registered', $exception->getMessage());
    }

    /** @test */
    public function duplicate_aggregator_exception_has_name_in_context(): void
    {
        $exception = new DuplicateAggregatorException('multimedia');

        $this->assertEquals(['aggregator_name' => 'multimedia'], $exception->getContext());
    }

    /** @test */
    public function duplicate_aggregator_exception_extends_base(): void
    {
        $exception = new DuplicateAggregatorException('tag');

        $this->assertInstanceOf(AggregatorException::class, $exception);
    }

    // AggregatorTimeoutException tests

    /** @test */
    public function aggregator_timeout_exception_has_correct_message(): void
    {
        $exception = new AggregatorTimeoutException('tag', 5000);

        $this->assertStringContainsString('tag', $exception->getMessage());
        $this->assertStringContainsString('5000', $exception->getMessage());
        $this->assertStringContainsString('timed out', $exception->getMessage());
    }

    /** @test */
    public function aggregator_timeout_exception_has_context(): void
    {
        $exception = new AggregatorTimeoutException('multimedia', 3000);

        $context = $exception->getContext();
        $this->assertEquals('multimedia', $context['aggregator_name']);
        $this->assertEquals(3000, $context['timeout_ms']);
    }

    /** @test */
    public function aggregator_timeout_exception_extends_base(): void
    {
        $exception = new AggregatorTimeoutException('tag', 5000);

        $this->assertInstanceOf(AggregatorException::class, $exception);
    }
}
