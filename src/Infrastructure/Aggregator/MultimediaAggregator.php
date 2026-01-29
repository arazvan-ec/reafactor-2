<?php

declare(strict_types=1);

namespace App\Infrastructure\Aggregator;

use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Infrastructure\Attribute\AsAggregator;
use App\Infrastructure\Client\Contract\QueryMultimediaClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;

/**
 * Aggregator for fetching multimedia data from the multimedia microservice.
 *
 * Loads multimedia (main, opening, meta) in parallel using async HTTP requests.
 */
#[AsAggregator(name: 'multimedia', priority: 90, timeout: 5000)]
final class MultimediaAggregator implements AsyncAggregatorInterface
{
    public function __construct(
        private readonly QueryMultimediaClientInterface $multimediaClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'multimedia';
    }

    public function getPriority(): int
    {
        return 90;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getTimeout(): int
    {
        return 5000;
    }

    public function getFallback(): mixed
    {
        return [];
    }

    public function supports(AggregatorContext $context): bool
    {
        $rawData = $context->getRawData();

        return !empty($rawData['multimediaId'])
            || !empty($rawData['openingMultimediaId'])
            || !empty($rawData['metaImageId']);
    }

    public function aggregate(AggregatorContext $context): PromiseInterface
    {
        $multimediaIds = $this->extractMultimediaIds($context->getRawData());

        if (empty($multimediaIds)) {
            return Utils::queue()->run(fn() => []);
        }

        $promises = [];
        foreach ($multimediaIds as $key => $id) {
            $promises[$key] = $this->multimediaClient->findByIdAsync($id)
                ->otherwise(function (\Throwable $e) use ($key, $id, $context) {
                    $this->logger->warning('Failed to resolve multimedia', [
                        'multimedia_id' => $id,
                        'key' => $key,
                        'editorial_id' => $context->getEditorialId(),
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                });
        }

        return Utils::all($promises)
            ->then(fn(array $multimedia) => array_filter($multimedia));
    }

    /**
     * Extract multimedia IDs from editorial raw data.
     *
     * @param array<string, mixed> $editorial
     * @return array<string, string>
     */
    private function extractMultimediaIds(array $editorial): array
    {
        $ids = [];

        if (!empty($editorial['multimediaId'])) {
            $ids['main'] = $editorial['multimediaId'];
        }

        if (!empty($editorial['openingMultimediaId'])) {
            $ids['opening'] = $editorial['openingMultimediaId'];
        }

        if (!empty($editorial['metaImageId'])) {
            $ids['meta'] = $editorial['metaImageId'];
        }

        return $ids;
    }
}
