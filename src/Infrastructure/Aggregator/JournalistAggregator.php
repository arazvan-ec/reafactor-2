<?php

declare(strict_types=1);

namespace App\Infrastructure\Aggregator;

use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Infrastructure\Attribute\AsAggregator;
use App\Infrastructure\Client\Contract\QueryJournalistClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;

/**
 * Aggregator for fetching journalist data from the journalist microservice.
 *
 * Loads journalists in parallel using async HTTP requests.
 */
#[AsAggregator(name: 'journalist', priority: 80, timeout: 3000)]
final class JournalistAggregator implements AsyncAggregatorInterface
{
    public function __construct(
        private readonly QueryJournalistClientInterface $journalistClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'journalist';
    }

    public function getPriority(): int
    {
        return 80;
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

        return !empty($rawData['journalistIds']) || !empty($rawData['journalistId']);
    }

    public function aggregate(AggregatorContext $context): PromiseInterface
    {
        $journalistIds = $this->extractJournalistIds($context->getRawData());

        if (empty($journalistIds)) {
            return Utils::queue()->run(fn() => []);
        }

        $promises = [];
        foreach ($journalistIds as $id) {
            $promises[$id] = $this->journalistClient->findByIdAsync($id)
                ->otherwise(function (\Throwable $e) use ($id, $context) {
                    $this->logger->warning('Failed to resolve journalist', [
                        'journalist_id' => $id,
                        'editorial_id' => $context->getEditorialId(),
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                });
        }

        return Utils::all($promises)
            ->then(fn(array $journalists) => array_values(array_filter($journalists)));
    }

    /**
     * Extract journalist IDs from editorial raw data.
     *
     * @param array<string, mixed> $editorial
     * @return string[]
     */
    private function extractJournalistIds(array $editorial): array
    {
        if (!empty($editorial['journalistIds']) && is_array($editorial['journalistIds'])) {
            return $editorial['journalistIds'];
        }

        if (!empty($editorial['journalistId'])) {
            return [$editorial['journalistId']];
        }

        return [];
    }
}
