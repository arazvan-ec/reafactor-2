<?php

declare(strict_types=1);

namespace App\Application\Transformer;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Domain\Transformer\ValueObject\TransformationContext;

/**
 * Pipeline for transforming aggregator results to JSON format.
 *
 * Processes all aggregation results and transforms them
 * using the appropriate transformer for each data type.
 */
final class TransformationPipeline
{
    public function __construct(
        private readonly TransformerRegistry $registry
    ) {
    }

    /**
     * Transform all aggregator results to JSON-ready format.
     *
     * @param array<string, AggregatorResult> $results
     * @return array<string, mixed>
     */
    public function transform(array $results, AggregatorContext $context): array
    {
        $transformationContext = new TransformationContext($context, $results);
        $output = [];

        foreach ($results as $name => $result) {
            $output[$name] = $this->transformResult($result, $transformationContext);
        }

        return $output;
    }

    /**
     * Transform a single aggregator result.
     */
    private function transformResult(
        AggregatorResult $result,
        TransformationContext $context
    ): mixed {
        // Failed results use fallback data as-is
        if ($result->isFailure()) {
            return $result->getData();
        }

        $data = $result->getData();

        // Try to find a transformer for this data
        $transformer = $this->registry->getForData($data);

        if ($transformer !== null) {
            return $transformer->transform($data, $context);
        }

        // No transformer found - pass through as-is
        return $data;
    }
}
