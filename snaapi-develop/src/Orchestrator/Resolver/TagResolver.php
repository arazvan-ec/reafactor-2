<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Tag\Client\QueryTagClient;
use Psr\Log\LoggerInterface;

final readonly class TagResolver implements DataResolverInterface
{
    public function __construct(
        private QueryTagClient $tagClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $tagIds = $this->extractTagIds($editorial);
        if (empty($tagIds)) {
            return;
        }

        $tags = [];
        foreach ($tagIds as $id) {
            try {
                $tag = $this->tagClient->findById($id);
                if ($tag !== null) {
                    $tags[] = $tag;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve tag', [
                    'tag_id' => $id,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($tags)) {
            $context->setTags($tags);
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 70;
    }

    private function extractTagIds(array $editorial): array
    {
        if (!empty($editorial['tagIds']) && is_array($editorial['tagIds'])) {
            return $editorial['tagIds'];
        }

        if (!empty($editorial['tags']) && is_array($editorial['tags'])) {
            return array_filter(array_map(
                fn($tag) => $tag['id'] ?? null,
                $editorial['tags']
            ));
        }

        return [];
    }
}
