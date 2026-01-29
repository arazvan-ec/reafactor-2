<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer\BodyElement;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Transformer for bodyTagVideo body elements.
 *
 * Enriches video elements with resolved multimedia data.
 */
final class BodyTagVideoTransformer implements BodyElementTransformerInterface
{
    public function getType(): string
    {
        return 'bodyTagVideo';
    }

    public function transform(
        array $element,
        array $resolvedMultimedia,
        AggregatorContext $context
    ): array {
        $multimediaId = $element['multimediaId'] ?? null;
        $multimedia = null;

        // Try to find resolved multimedia
        if ($multimediaId !== null && isset($resolvedMultimedia[$multimediaId])) {
            $multimedia = $resolvedMultimedia[$multimediaId];
        }

        return [
            'type' => 'bodyTagVideo',
            'multimediaId' => $multimediaId,
            'multimedia' => $multimedia,
            'caption' => $element['caption'] ?? null,
            'autoplay' => $element['autoplay'] ?? false,
            'loop' => $element['loop'] ?? false,
            'muted' => $element['muted'] ?? false,
            'attributes' => $element['attributes'] ?? [],
        ];
    }
}
