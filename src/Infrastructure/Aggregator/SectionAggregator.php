<?php

declare(strict_types=1);

namespace App\Infrastructure\Aggregator;

use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Infrastructure\Attribute\AsAggregator;
use App\Infrastructure\Client\Contract\QuerySectionClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;

/**
 * Aggregator for fetching section data from the section microservice.
 *
 * Section is fetched first (highest priority) as it's needed for URLs and routing.
 */
#[AsAggregator(name: 'section', priority: 100, timeout: 3000)]
final class SectionAggregator implements AsyncAggregatorInterface
{
    public function __construct(
        private readonly QuerySectionClientInterface $sectionClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'section';
    }

    public function getPriority(): int
    {
        return 100; // Highest priority - section needed for URLs
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
        return null;
    }

    public function supports(AggregatorContext $context): bool
    {
        $rawData = $context->getRawData();

        return !empty($rawData['sectionId']);
    }

    public function aggregate(AggregatorContext $context): PromiseInterface
    {
        $sectionId = $context->getRawData()['sectionId'] ?? null;

        if ($sectionId === null) {
            $this->logger->warning('Editorial has no sectionId', [
                'editorial_id' => $context->getEditorialId(),
            ]);

            return Utils::queue()->run(fn() => null);
        }

        return $this->sectionClient->findByIdAsync($sectionId)
            ->otherwise(function (\Throwable $e) use ($sectionId, $context) {
                $this->logger->error('Failed to resolve section', [
                    'section_id' => $sectionId,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);

                return null;
            });
    }
}
