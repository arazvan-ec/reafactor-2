<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer\BodyElement;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Transformer for bodyTagWidget body elements.
 *
 * Handles embedded widgets (Twitter, Instagram, YouTube, etc.).
 */
final class BodyTagWidgetTransformer implements BodyElementTransformerInterface
{
    public function getType(): string
    {
        return 'bodyTagWidget';
    }

    public function transform(
        array $element,
        array $resolvedMultimedia,
        AggregatorContext $context
    ): array {
        return [
            'type' => 'bodyTagWidget',
            'widgetType' => $element['widgetType'] ?? 'unknown',
            'widgetId' => $element['widgetId'] ?? null,
            'embedUrl' => $element['embedUrl'] ?? null,
            'embedHtml' => $element['embedHtml'] ?? null,
            'width' => $element['width'] ?? null,
            'height' => $element['height'] ?? null,
            'attributes' => $element['attributes'] ?? [],
        ];
    }
}
