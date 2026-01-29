<?php

declare(strict_types=1);

namespace App\Infrastructure\Aggregator;

use App\Domain\Aggregator\Contract\SyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Infrastructure\Attribute\AsAggregator;
use App\Infrastructure\Transformer\BodyElement\BodyElementTransformerHandler;
use Psr\Log\LoggerInterface;

/**
 * Aggregator for processing body elements with nested data.
 *
 * This is a synchronous aggregator that depends on multimedia being resolved first.
 * It processes body elements (paragraphs, subheads, pictures, etc.) and transforms
 * them using the resolved multimedia data.
 */
#[AsAggregator(name: 'bodyTag', priority: 60, dependencies: ['multimedia'])]
final class BodyTagAggregator implements SyncAggregatorInterface
{
    public function __construct(
        private readonly BodyElementTransformerHandler $transformerHandler,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'bodyTag';
    }

    public function getPriority(): int
    {
        return 60; // Lower priority - runs after multimedia
    }

    public function getDependencies(): array
    {
        return ['multimedia']; // Needs multimedia resolved first
    }

    public function supports(AggregatorContext $context): bool
    {
        $rawData = $context->getRawData();

        return isset($rawData['body']['bodyElements'])
            || isset($rawData['bodyElements']);
    }

    public function aggregate(AggregatorContext $context): AggregatorResult
    {
        $startTime = microtime(true);

        $bodyElements = $this->extractBodyElements($context->getRawData());
        $resolvedMultimedia = $context->getResolvedDataByKey('multimedia') ?? [];

        $transformedElements = [];
        foreach ($bodyElements as $index => $element) {
            try {
                $transformed = $this->transformerHandler->transform(
                    $element,
                    $resolvedMultimedia,
                    $context
                );

                if ($transformed !== null) {
                    $transformed['index'] = $index;
                    $transformedElements[] = $transformed;
                }
            } catch (\Throwable $e) {
                // Log error but continue with other elements
                $this->logger->warning('Failed to transform body element', [
                    'index' => $index,
                    'element_type' => $element['type'] ?? 'unknown',
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return AggregatorResult::success(
            $this->getName(),
            $transformedElements,
            microtime(true) - $startTime
        );
    }

    /**
     * Extract body elements from editorial raw data.
     *
     * @param array<string, mixed> $editorial
     * @return array<int, array<string, mixed>>
     */
    private function extractBodyElements(array $editorial): array
    {
        if (isset($editorial['body']['bodyElements']) && is_array($editorial['body']['bodyElements'])) {
            return $editorial['body']['bodyElements'];
        }

        if (isset($editorial['bodyElements']) && is_array($editorial['bodyElements'])) {
            return $editorial['bodyElements'];
        }

        return [];
    }
}
