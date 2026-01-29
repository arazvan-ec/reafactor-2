<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer\BodyElement;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Contract for body element transformers.
 *
 * Each transformer handles a specific type of body element
 * (paragraph, subhead, picture, video, etc.).
 */
interface BodyElementTransformerInterface
{
    /**
     * Get the element type this transformer handles.
     *
     * @example "paragraph", "subhead", "bodyTagPicture", "bodyTagVideo"
     */
    public function getType(): string;

    /**
     * Transform a body element to JSON-serializable format.
     *
     * @param array<string, mixed> $element The body element data
     * @param array<string, mixed> $resolvedMultimedia Pre-resolved multimedia data
     * @param AggregatorContext $context Aggregation context
     * @return array<string, mixed> Transformed element
     */
    public function transform(
        array $element,
        array $resolvedMultimedia,
        AggregatorContext $context
    ): array;
}
