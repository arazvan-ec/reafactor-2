<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Aggregator;

use App\Application\Aggregator\AggregatorRegistry;
use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Exception\AggregatorNotFoundException;
use App\Domain\Aggregator\Exception\DuplicateAggregatorException;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Application\Aggregator\AggregatorRegistry
 */
final class AggregatorRegistryTest extends TestCase
{
    private AggregatorRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new AggregatorRegistry();
    }

    /** @test */
    public function it_registers_and_retrieves_aggregator(): void
    {
        $aggregator = $this->createMockAggregator('tag', 70);
        $this->registry->register($aggregator);

        $this->assertSame($aggregator, $this->registry->get('tag'));
    }

    /** @test */
    public function it_throws_on_duplicate_registration(): void
    {
        $this->expectException(DuplicateAggregatorException::class);

        $agg1 = $this->createMockAggregator('tag', 70);
        $agg2 = $this->createMockAggregator('tag', 80);

        $this->registry->register($agg1);
        $this->registry->register($agg2);
    }

    /** @test */
    public function it_throws_when_aggregator_not_found(): void
    {
        $this->expectException(AggregatorNotFoundException::class);
        $this->registry->get('nonexistent');
    }

    /** @test */
    public function it_checks_if_aggregator_exists(): void
    {
        $aggregator = $this->createMockAggregator('tag', 70);
        $this->registry->register($aggregator);

        $this->assertTrue($this->registry->has('tag'));
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    /** @test */
    public function it_returns_all_aggregators(): void
    {
        $tag = $this->createMockAggregator('tag', 70);
        $multimedia = $this->createMockAggregator('multimedia', 90);

        $this->registry->register($tag);
        $this->registry->register($multimedia);

        $all = $this->registry->getAll();
        $this->assertCount(2, $all);
        $this->assertContains($tag, $all);
        $this->assertContains($multimedia, $all);
    }

    /** @test */
    public function it_returns_all_aggregator_names(): void
    {
        $this->registry->register($this->createMockAggregator('tag', 70));
        $this->registry->register($this->createMockAggregator('multimedia', 90));

        $names = $this->registry->getNames();
        $this->assertCount(2, $names);
        $this->assertContains('tag', $names);
        $this->assertContains('multimedia', $names);
    }

    /** @test */
    public function it_counts_registered_aggregators(): void
    {
        $this->assertEquals(0, $this->registry->count());

        $this->registry->register($this->createMockAggregator('tag', 70));
        $this->assertEquals(1, $this->registry->count());

        $this->registry->register($this->createMockAggregator('multimedia', 90));
        $this->assertEquals(2, $this->registry->count());
    }

    /** @test */
    public function it_filters_by_context_support(): void
    {
        $supported = $this->createMockAggregator('tag', 70, true);
        $notSupported = $this->createMockAggregator('multimedia', 90, false);

        $this->registry->register($supported);
        $this->registry->register($notSupported);

        $context = new AggregatorContext('1', 'news', []);
        $result = $this->registry->getForContext($context);

        $this->assertCount(1, $result);
        $this->assertEquals('tag', $result[0]->getName());
    }

    /** @test */
    public function it_sorts_by_priority_descending(): void
    {
        $low = $this->createMockAggregator('low', 10, true);
        $high = $this->createMockAggregator('high', 90, true);
        $medium = $this->createMockAggregator('medium', 50, true);

        $this->registry->register($low);
        $this->registry->register($high);
        $this->registry->register($medium);

        $context = new AggregatorContext('1', 'news', []);
        $result = $this->registry->getForContext($context);

        $this->assertEquals(['high', 'medium', 'low'], array_map(
            fn($a) => $a->getName(),
            $result
        ));
    }

    /** @test */
    public function it_returns_empty_when_none_support_context(): void
    {
        $this->registry->register($this->createMockAggregator('tag', 70, false));
        $this->registry->register($this->createMockAggregator('multimedia', 90, false));

        $context = new AggregatorContext('1', 'news', []);
        $result = $this->registry->getForContext($context);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_handles_aggregators_with_same_priority(): void
    {
        $a = $this->createMockAggregator('a', 50, true);
        $b = $this->createMockAggregator('b', 50, true);
        $c = $this->createMockAggregator('c', 50, true);

        $this->registry->register($a);
        $this->registry->register($b);
        $this->registry->register($c);

        $context = new AggregatorContext('1', 'news', []);
        $result = $this->registry->getForContext($context);

        // All three should be returned
        $this->assertCount(3, $result);
    }

    private function createMockAggregator(string $name, int $priority, bool $supports = true): AggregatorInterface
    {
        $mock = $this->createMock(AggregatorInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('getPriority')->willReturn($priority);
        $mock->method('supports')->willReturn($supports);
        $mock->method('getDependencies')->willReturn([]);

        return $mock;
    }
}
