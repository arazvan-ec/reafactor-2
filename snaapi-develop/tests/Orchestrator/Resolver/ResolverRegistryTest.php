<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use App\Orchestrator\Resolver\ResolverRegistry;
use PHPUnit\Framework\TestCase;

final class ResolverRegistryTest extends TestCase
{
    public function testAddAndGetResolvers(): void
    {
        $registry = new ResolverRegistry();
        $resolver = $this->createMock(DataResolverInterface::class);
        $resolver->method('getPriority')->willReturn(50);

        $registry->addResolver($resolver);

        $this->assertCount(1, $registry->getResolvers());
    }

    public function testResolversAreSortedByPriority(): void
    {
        $registry = new ResolverRegistry();

        $lowPriority = $this->createMock(DataResolverInterface::class);
        $lowPriority->method('getPriority')->willReturn(10);

        $highPriority = $this->createMock(DataResolverInterface::class);
        $highPriority->method('getPriority')->willReturn(100);

        $medPriority = $this->createMock(DataResolverInterface::class);
        $medPriority->method('getPriority')->willReturn(50);

        $registry->addResolver($lowPriority);
        $registry->addResolver($highPriority);
        $registry->addResolver($medPriority);

        $resolvers = $registry->getResolvers();

        $this->assertSame($highPriority, $resolvers[0]);
        $this->assertSame($medPriority, $resolvers[1]);
        $this->assertSame($lowPriority, $resolvers[2]);
    }

    public function testGetResolversForFiltersUnsupported(): void
    {
        $registry = new ResolverRegistry();
        $context = new OrchestrationContext('id', 'site', 'com');

        $supported = $this->createMock(DataResolverInterface::class);
        $supported->method('getPriority')->willReturn(50);
        $supported->method('supports')->willReturn(true);

        $unsupported = $this->createMock(DataResolverInterface::class);
        $unsupported->method('getPriority')->willReturn(50);
        $unsupported->method('supports')->willReturn(false);

        $registry->addResolver($supported);
        $registry->addResolver($unsupported);

        $resolvers = $registry->getResolversFor($context);

        $this->assertCount(1, $resolvers);
    }

    public function testSortingIsCached(): void
    {
        $registry = new ResolverRegistry();

        $resolver1 = $this->createMock(DataResolverInterface::class);
        $resolver1->method('getPriority')->willReturn(10);

        $resolver2 = $this->createMock(DataResolverInterface::class);
        $resolver2->method('getPriority')->willReturn(20);

        $registry->addResolver($resolver1);
        $registry->addResolver($resolver2);

        // First call triggers sorting
        $resolvers1 = $registry->getResolvers();
        // Second call should use cached sorting
        $resolvers2 = $registry->getResolvers();

        $this->assertEquals($resolvers1, $resolvers2);
    }

    public function testAddingResolverInvalidatesCache(): void
    {
        $registry = new ResolverRegistry();

        $resolver1 = $this->createMock(DataResolverInterface::class);
        $resolver1->method('getPriority')->willReturn(10);

        $registry->addResolver($resolver1);
        $registry->getResolvers(); // Triggers sorting

        $resolver2 = $this->createMock(DataResolverInterface::class);
        $resolver2->method('getPriority')->willReturn(100);

        $registry->addResolver($resolver2);
        $resolvers = $registry->getResolvers();

        // resolver2 should be first due to higher priority
        $this->assertSame($resolver2, $resolvers[0]);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new ResolverRegistry();

        $this->assertEquals([], $registry->getResolvers());
    }

    public function testGetResolversForWithEmptyRegistry(): void
    {
        $registry = new ResolverRegistry();
        $context = new OrchestrationContext('id', 'site', 'com');

        $this->assertEquals([], $registry->getResolversFor($context));
    }
}
