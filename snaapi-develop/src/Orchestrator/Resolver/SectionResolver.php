<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Section\Client\QuerySectionClient;
use Psr\Log\LoggerInterface;

final readonly class SectionResolver implements DataResolverInterface
{
    public function __construct(
        private QuerySectionClient $sectionClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $sectionId = $editorial['sectionId'] ?? null;
        if ($sectionId === null) {
            $this->logger->warning('Editorial has no sectionId', [
                'editorial_id' => $context->getEditorialId(),
            ]);
            return;
        }

        try {
            $section = $this->sectionClient->findById($sectionId);
            if ($section !== null) {
                $context->setSection($section);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to resolve section', [
                'section_id' => $sectionId,
                'editorial_id' => $context->getEditorialId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 100; // Highest - section needed for URLs
    }
}
