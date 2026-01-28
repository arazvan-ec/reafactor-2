<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Editorial\Client\QueryEditorialClient;
use Psr\Log\LoggerInterface;

final readonly class InsertedNewsResolver implements DataResolverInterface
{
    public function __construct(
        private QueryEditorialClient $editorialClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $body = $context->getBody();
        if ($body === null) {
            return;
        }

        $insertedNewsIds = $this->extractInsertedNewsIds($body);
        if (empty($insertedNewsIds)) {
            return;
        }

        foreach ($insertedNewsIds as $id) {
            try {
                $news = $this->editorialClient->findById($id);
                if ($news !== null) {
                    $context->addInsertedNews($news);
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve inserted news', [
                    'news_id' => $id,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasBody();
    }

    public function getPriority(): int
    {
        return 60;
    }

    private function extractInsertedNewsIds(array $body): array
    {
        $ids = [];

        $elements = $body['elements'] ?? $body;
        if (!is_array($elements)) {
            return $ids;
        }

        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }

            $type = $element['type'] ?? '';
            if ($type === 'insertedNews' || $type === 'inserted_news') {
                $newsId = $element['editorialId'] ?? $element['newsId'] ?? $element['id'] ?? null;
                if ($newsId !== null) {
                    $ids[] = $newsId;
                }
            }
        }

        return array_unique($ids);
    }
}
