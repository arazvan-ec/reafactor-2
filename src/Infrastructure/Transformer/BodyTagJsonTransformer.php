<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer;

use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\ValueObject\TransformationContext;
use App\Infrastructure\Attribute\AsJsonTransformer;

/**
 * Transforms bodyTag aggregator results to JSON format.
 *
 * Body elements are already transformed by BodyTagAggregator,
 * so this transformer mainly passes through the data with minimal changes.
 */
#[AsJsonTransformer(type: 'bodyTag')]
final class BodyTagJsonTransformer implements JsonTransformerInterface
{
    public function getType(): string
    {
        return 'bodyTag';
    }

    public function supports(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        if (empty($data)) {
            return true; // Empty body is valid
        }

        // Check first element has body element structure
        $first = reset($data);

        return is_array($first) && isset($first['type']);
    }

    public function transform(mixed $data, TransformationContext $context): array
    {
        if (!is_array($data)) {
            return [];
        }

        // Body elements are already transformed by BodyTagAggregator
        // Apply any final formatting here
        return array_map(fn($element) => $this->finalizeElement($element), $data);
    }

    /**
     * Apply final formatting to a body element.
     *
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    private function finalizeElement(array $element): array
    {
        // Ensure consistent structure
        return array_filter($element, fn($value) => $value !== null);
    }
}
