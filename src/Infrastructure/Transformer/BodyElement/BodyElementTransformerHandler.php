<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer\BodyElement;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Handler for transforming body elements.
 *
 * Delegates to specific transformers based on element type.
 * Uses Chain of Responsibility pattern for extensibility.
 */
final class BodyElementTransformerHandler
{
    /**
     * @var array<string, BodyElementTransformerInterface>
     */
    private array $transformers = [];

    /**
     * Register a transformer for a specific element type.
     */
    public function registerTransformer(BodyElementTransformerInterface $transformer): void
    {
        $this->transformers[$transformer->getType()] = $transformer;
    }

    /**
     * Transform a body element using the appropriate transformer.
     *
     * @param array<string, mixed> $element The body element to transform
     * @param array<string, mixed> $resolvedMultimedia Pre-resolved multimedia data
     * @param AggregatorContext $context Aggregation context
     * @return array<string, mixed>|null Transformed element or null if unsupported
     */
    public function transform(
        array $element,
        array $resolvedMultimedia,
        AggregatorContext $context
    ): ?array {
        $type = $element['type'] ?? null;

        if ($type === null) {
            return null;
        }

        if (isset($this->transformers[$type])) {
            return $this->transformers[$type]->transform($element, $resolvedMultimedia, $context);
        }

        // Default passthrough for unknown types
        return $this->defaultTransform($element);
    }

    /**
     * Default transformation for unknown element types.
     *
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    private function defaultTransform(array $element): array
    {
        return [
            'type' => $element['type'] ?? 'unknown',
            'content' => $element['content'] ?? null,
            'attributes' => $element['attributes'] ?? [],
        ];
    }

    /**
     * Get all registered transformer types.
     *
     * @return string[]
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->transformers);
    }

    /**
     * Check if a transformer is registered for the given type.
     */
    public function hasTransformer(string $type): bool
    {
        return isset($this->transformers[$type]);
    }
}
