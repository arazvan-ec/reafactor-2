<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Chain;

use App\Application\Response\ResponseBuilderInterface;
use App\Orchestrator\Chain\EditorialOrchestratorFacade;
use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Context\OrchestrationContextFactory;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use App\Orchestrator\Resolver\Interface\ResolverRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class EditorialOrchestratorFacadeTest extends TestCase
{
    private ResolverRegistryInterface&MockObject $resolverRegistry;
    private OrchestrationContextFactory&MockObject $contextFactory;
    private ResponseBuilderInterface&MockObject $responseBuilder;
    private LoggerInterface&MockObject $logger;
    private EditorialOrchestratorFacade $facade;

    protected function setUp(): void
    {
        $this->resolverRegistry = $this->createMock(ResolverRegistryInterface::class);
        $this->contextFactory = $this->createMock(OrchestrationContextFactory::class);
        $this->responseBuilder = $this->createMock(ResponseBuilderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->facade = new EditorialOrchestratorFacade(
            $this->resolverRegistry,
            $this->contextFactory,
            $this->responseBuilder,
            $this->logger
        );
    }

    public function testCanOrchestrate(): void
    {
        $this->assertEquals('editorial', $this->facade->canOrchestrate());
    }

    public function testExecuteCreatesContextFromRequest(): void
    {
        $request = new Request();
        $context = new OrchestrationContext('id', 'site', 'com');

        $this->contextFactory
            ->expects($this->once())
            ->method('createFromRequest')
            ->with($request)
            ->willReturn($context);

        $this->resolverRegistry
            ->method('getResolversFor')
            ->willReturn([]);

        $this->responseBuilder
            ->method('build')
            ->willReturn([]);

        $this->facade->execute($request);
    }

    public function testExecuteCallsAllResolvers(): void
    {
        $request = new Request();
        $context = new OrchestrationContext('id', 'site', 'com');

        $resolver1 = $this->createMock(DataResolverInterface::class);
        $resolver2 = $this->createMock(DataResolverInterface::class);

        $resolver1->expects($this->once())->method('resolve')->with($context);
        $resolver2->expects($this->once())->method('resolve')->with($context);

        $this->contextFactory
            ->method('createFromRequest')
            ->willReturn($context);

        $this->resolverRegistry
            ->method('getResolversFor')
            ->willReturn([$resolver1, $resolver2]);

        $this->responseBuilder
            ->method('build')
            ->willReturn([]);

        $this->facade->execute($request);
    }

    public function testExecuteReturnsResponseFromBuilder(): void
    {
        $request = new Request();
        $context = new OrchestrationContext('id', 'site', 'com');
        $expectedResponse = ['id' => '123', 'title' => 'Test'];

        $this->contextFactory
            ->method('createFromRequest')
            ->willReturn($context);

        $this->resolverRegistry
            ->method('getResolversFor')
            ->willReturn([]);

        $this->responseBuilder
            ->expects($this->once())
            ->method('build')
            ->with($context)
            ->willReturn($expectedResponse);

        $result = $this->facade->execute($request);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testExecuteLogsAndContinuesOnResolverFailure(): void
    {
        $request = new Request();
        $context = new OrchestrationContext('id', 'site', 'com');

        $failingResolver = $this->createMock(DataResolverInterface::class);
        $failingResolver
            ->method('resolve')
            ->willThrowException(new \RuntimeException('Test error'));

        $successResolver = $this->createMock(DataResolverInterface::class);
        $successResolver->expects($this->once())->method('resolve');

        $this->contextFactory
            ->method('createFromRequest')
            ->willReturn($context);

        $this->resolverRegistry
            ->method('getResolversFor')
            ->willReturn([$failingResolver, $successResolver]);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Resolver failed', $this->anything());

        $this->responseBuilder
            ->method('build')
            ->willReturn([]);

        $this->facade->execute($request);
    }

    public function testFacadeHasOnlyFourDependencies(): void
    {
        $reflection = new \ReflectionClass(EditorialOrchestratorFacade::class);
        $constructor = $reflection->getConstructor();

        $this->assertCount(4, $constructor->getParameters());
    }

    public function testFacadeIsLessThan50Lines(): void
    {
        $reflection = new \ReflectionClass(EditorialOrchestratorFacade::class);
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();

        $this->assertLessThan(50, $endLine - $startLine);
    }
}
