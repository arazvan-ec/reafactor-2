<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain;

use App\Application\Response\ResponseBuilderInterface;
use App\Orchestrator\Context\OrchestrationContextFactory;
use App\Orchestrator\Resolver\Interface\ResolverRegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class EditorialOrchestratorFacade implements EditorialOrchestratorInterface
{
    public function __construct(
        private ResolverRegistryInterface $resolverRegistry,
        private OrchestrationContextFactory $contextFactory,
        private ResponseBuilderInterface $responseBuilder,
        private LoggerInterface $logger,
    ) {}

    public function execute(Request $request): array
    {
        $context = $this->contextFactory->createFromRequest($request);

        foreach ($this->resolverRegistry->getResolversFor($context) as $resolver) {
            try {
                $resolver->resolve($context);
            } catch (\Throwable $e) {
                $this->logger->error('Resolver failed', [
                    'resolver' => $resolver::class,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->responseBuilder->build($context);
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }
}
