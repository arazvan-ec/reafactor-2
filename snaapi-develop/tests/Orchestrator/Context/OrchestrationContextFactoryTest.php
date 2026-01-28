<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Context;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Context\OrchestrationContextFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class OrchestrationContextFactoryTest extends TestCase
{
    private OrchestrationContextFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new OrchestrationContextFactory('com');
    }

    public function testCreateFromRequest(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'editorial-123');
        $request->attributes->set('siteId', 'site-456');

        $context = $this->factory->createFromRequest($request);

        $this->assertInstanceOf(OrchestrationContext::class, $context);
        $this->assertEquals('editorial-123', $context->getEditorialId());
        $this->assertEquals('site-456', $context->getSiteId());
        $this->assertEquals('com', $context->getExtension());
    }

    public function testCreateFromRequestWithMissingAttributes(): void
    {
        $request = new Request();

        $context = $this->factory->createFromRequest($request);

        $this->assertEquals('', $context->getEditorialId());
        $this->assertEquals('', $context->getSiteId());
    }

    public function testCreate(): void
    {
        $context = $this->factory->create('editorial-789', 'site-101');

        $this->assertInstanceOf(OrchestrationContext::class, $context);
        $this->assertEquals('editorial-789', $context->getEditorialId());
        $this->assertEquals('site-101', $context->getSiteId());
        $this->assertEquals('com', $context->getExtension());
    }
}
