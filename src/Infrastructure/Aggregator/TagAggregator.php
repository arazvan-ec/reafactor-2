<?php

declare(strict_types=1);

namespace App\Infrastructure\Aggregator;

use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Infrastructure\Attribute\AsAggregator;
use App\Infrastructure\Client\Contract\QueryTagClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;

/**
 * Aggregator for fetching tag data from the tag microservice.
 *
 * Loads tags in parallel using async HTTP requests.
 */
#[AsAggregator(name: 'tag', priority: 70, timeout: 3000)]
final class TagAggregator implements AsyncAggregatorInterface
{
    public function __construct(
        private readonly QueryTagClientInterface $tagClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'tag';
    }

    public function getPriority(): int
    {
        return 70;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getTimeout(): int
    {
        return 3000;
    }

    public function getFallback(): mixed
    {
        return [];
    }

    public function supports(AggregatorContext $context): bool
    {
        $rawData = $context->getRawData();

        return !empty($rawData['tagIds']) || !empty($rawData['tags']);
    }

    public function aggregate(AggregatorContext $context): PromiseInterface
    {
        $tagIds = $this->extractTagIds($context->getRawData());

        if (empty($tagIds)) {
            return Utils::queue()->run(fn() => []);
        }

        $promises = [];
        foreach ($tagIds as $tagId) {
            $promises[$tagId] = $this->tagClient->findByIdAsync($tagId)
                ->otherwise(function (\Throwable $e) use ($tagId, $context) {
                    $this->logger->warning('Failed to resolve tag', [
                        'tag_id' => $tagId,
                        'editorial_id' => $context->getEditorialId(),
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                });
        }

        return Utils::all($promises)
            ->then(fn(array $tags) => array_values(array_filter($tags)));
    }

    /**
     * Extract tag IDs from editorial raw data.
     *
     * @param array<string, mixed> $editorial
     * @return string[]
     */
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
