<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Aggregator;

use App\Application\Aggregator\DependencyResolver;
use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Exception\AggregatorNotFoundException;
use App\Domain\Aggregator\Exception\CircularDependencyException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Application\Aggregator\DependencyResolver
 */
final class DependencyResolverTest extends TestCase
{
    private DependencyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DependencyResolver();
    }

    /** @test */
    public function it_resolves_empty_list(): void
    {
        $batches = $this->resolver->resolve([]);
        $this->assertEmpty($batches);
    }

    /** @test */
    public function it_resolves_aggregators_without_dependencies(): void
    {
        $a = $this->mockAggregator('a', 50, []);
        $b = $this->mockAggregator('b', 70, []);

        $batches = $this->resolver->resolve([$a, $b]);

        $this->assertCount(1, $batches); // All in one batch
        $this->assertCount(2, $batches[0]);
    }

    /** @test */
    public function it_creates_separate_batches_for_dependencies(): void
    {
        $a = $this->mockAggregator('a', 50, []);
        $b = $this->mockAggregator('b', 70, ['a']); // b depends on a

        $batches = $this->resolver->resolve([$a, $b]);

        $this->assertCount(2, $batches);
        $this->assertEquals('a', $batches[0][0]->getName());
        $this->assertEquals('b', $batches[1][0]->getName());
    }

    /** @test */
    public function it_detects_simple_cycle(): void
    {
        $this->expectException(CircularDependencyException::class);

        $a = $this->mockAggregator('a', 50, ['b']);
        $b = $this->mockAggregator('b', 50, ['a']);

        $this->resolver->resolve([$a, $b]);
    }

    /** @test */
    public function it_detects_complex_cycle(): void
    {
        $this->expectException(CircularDependencyException::class);

        $a = $this->mockAggregator('a', 50, ['c']);
        $b = $this->mockAggregator('b', 50, ['a']);
        $c = $this->mockAggregator('c', 50, ['b']);

        $this->resolver->resolve([$a, $b, $c]);
    }

    /** @test */
    public function it_detects_self_dependency(): void
    {
        $this->expectException(CircularDependencyException::class);

        $a = $this->mockAggregator('a', 50, ['a']);

        $this->resolver->resolve([$a]);
    }

    /** @test */
    public function it_throws_when_dependency_not_found(): void
    {
        $this->expectException(AggregatorNotFoundException::class);

        $a = $this->mockAggregator('a', 50, ['nonexistent']);

        $this->resolver->resolve([$a]);
    }

    /** @test */
    public function it_sorts_within_batch_by_priority(): void
    {
        $a = $this->mockAggregator('a', 30, []);
        $b = $this->mockAggregator('b', 90, []);
        $c = $this->mockAggregator('c', 60, []);

        $batches = $this->resolver->resolve([$a, $b, $c]);

        $names = array_map(fn($agg) => $agg->getName(), $batches[0]);
        $this->assertEquals(['b', 'c', 'a'], $names);
    }

    /** @test */
    public function it_handles_chain_dependencies(): void
    {
        // a -> b -> c (c depends on b, b depends on a)
        $a = $this->mockAggregator('a', 50, []);
        $b = $this->mockAggregator('b', 50, ['a']);
        $c = $this->mockAggregator('c', 50, ['b']);

        $batches = $this->resolver->resolve([$a, $b, $c]);

        $this->assertCount(3, $batches);
        $this->assertEquals('a', $batches[0][0]->getName());
        $this->assertEquals('b', $batches[1][0]->getName());
        $this->assertEquals('c', $batches[2][0]->getName());
    }

    /** @test */
    public function it_handles_diamond_dependencies(): void
    {
        // a -> b, a -> c, b -> d, c -> d
        $a = $this->mockAggregator('a', 50, []);
        $b = $this->mockAggregator('b', 50, ['a']);
        $c = $this->mockAggregator('c', 50, ['a']);
        $d = $this->mockAggregator('d', 50, ['b', 'c']);

        $batches = $this->resolver->resolve([$a, $b, $c, $d]);

        $this->assertCount(3, $batches);
        $this->assertCount(1, $batches[0]); // a
        $this->assertCount(2, $batches[1]); // b, c (parallel)
        $this->assertCount(1, $batches[2]); // d
    }

    /** @test */
    public function it_handles_multiple_dependencies(): void
    {
        $a = $this->mockAggregator('a', 50, []);
        $b = $this->mockAggregator('b', 50, []);
        $c = $this->mockAggregator('c', 50, ['a', 'b']); // c depends on both a and b

        $batches = $this->resolver->resolve([$a, $b, $c]);

        $this->assertCount(2, $batches);
        $this->assertCount(2, $batches[0]); // a, b (parallel)
        $this->assertCount(1, $batches[1]); // c
    }

    /** @test */
    public function it_handles_single_aggregator(): void
    {
        $a = $this->mockAggregator('a', 50, []);

        $batches = $this->resolver->resolve([$a]);

        $this->assertCount(1, $batches);
        $this->assertCount(1, $batches[0]);
        $this->assertEquals('a', $batches[0][0]->getName());
    }

    /** @test */
    public function it_resolves_complex_graph(): void
    {
        // section -> multimedia -> bodyTag (where bodyTag depends on multimedia)
        $section = $this->mockAggregator('section', 100, []);
        $tag = $this->mockAggregator('tag', 70, []);
        $multimedia = $this->mockAggregator('multimedia', 90, []);
        $bodyTag = $this->mockAggregator('bodyTag', 60, ['multimedia']);

        $batches = $this->resolver->resolve([$section, $tag, $multimedia, $bodyTag]);

        $this->assertCount(2, $batches);
        // First batch: section, tag, multimedia (all parallel, no deps)
        $this->assertCount(3, $batches[0]);
        // Second batch: bodyTag
        $this->assertCount(1, $batches[1]);
        $this->assertEquals('bodyTag', $batches[1][0]->getName());
    }

    private function mockAggregator(string $name, int $priority, array $dependencies): AggregatorInterface
    {
        $mock = $this->createMock(AggregatorInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('getPriority')->willReturn($priority);
        $mock->method('getDependencies')->willReturn($dependencies);

        return $mock;
    }
}
