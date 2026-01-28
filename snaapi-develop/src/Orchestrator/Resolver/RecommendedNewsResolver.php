<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Editorial\Client\QueryEditorialClient;
use Psr\Log\LoggerInterface;

final readonly class RecommendedNewsResolver implements DataResolverInterface
{
    private const MAX_RECOMMENDED = 5;

    public function __construct(
        private QueryEditorialClient $editorialClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $recommendedIds = $this->extractRecommendedIds($editorial);
        if (empty($recommendedIds)) {
            return;
        }

        $count = 0;
        foreach ($recommendedIds as $id) {
            if ($count >= self::MAX_RECOMMENDED) {
                break;
            }

            try {
                $news = $this->editorialClient->findById($id);
                if ($news !== null) {
                    $context->addRecommendedNews($news);
                    $count++;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve recommended news', [
                    'news_id' => $id,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 50;
    }

    private function extractRecommendedIds(array $editorial): array
    {
        if (!empty($editorial['recommendedEditorialIds']) && is_array($editorial['recommendedEditorialIds'])) {
            return $editorial['recommendedEditorialIds'];
        }

        if (!empty($editorial['recommendedNews']) && is_array($editorial['recommendedNews'])) {
            return array_filter(array_map(
                fn($news) => $news['id'] ?? null,
                $editorial['recommendedNews']
            ));
        }

        return [];
    }
}
