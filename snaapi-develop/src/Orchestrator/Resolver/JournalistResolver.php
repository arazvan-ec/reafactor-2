<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Journalist\Client\QueryJournalistClient;
use Psr\Log\LoggerInterface;

final readonly class JournalistResolver implements DataResolverInterface
{
    public function __construct(
        private QueryJournalistClient $journalistClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $journalistIds = $this->extractJournalistIds($editorial);
        if (empty($journalistIds)) {
            return;
        }

        $journalists = [];
        foreach ($journalistIds as $id) {
            try {
                $journalist = $this->journalistClient->findById($id);
                if ($journalist !== null) {
                    $journalists[] = $journalist;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve journalist', [
                    'journalist_id' => $id,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($journalists)) {
            $context->setJournalists($journalists);
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 80;
    }

    private function extractJournalistIds(array $editorial): array
    {
        $ids = [];

        if (!empty($editorial['journalistIds']) && is_array($editorial['journalistIds'])) {
            $ids = $editorial['journalistIds'];
        } elseif (!empty($editorial['journalistId'])) {
            $ids = [$editorial['journalistId']];
        }

        return $ids;
    }
}
