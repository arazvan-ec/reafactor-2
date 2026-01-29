<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer\BodyElement;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Transformer for bodyTagPicture body elements.
 *
 * Enriches picture elements with resolved multimedia data.
 */
final class BodyTagPictureTransformer implements BodyElementTransformerInterface
{
    public function getType(): string
    {
        return 'bodyTagPicture';
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
            'type' => 'bodyTagPicture',
            'multimediaId' => $multimediaId,
            'multimedia' => $multimedia,
            'caption' => $element['caption'] ?? null,
            'credit' => $element['credit'] ?? null,
            'alt' => $element['alt'] ?? null,
            'attributes' => $element['attributes'] ?? [],
        ];
    }
}
