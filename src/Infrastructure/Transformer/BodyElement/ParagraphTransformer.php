<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer\BodyElement;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Transformer for paragraph body elements.
 */
final class ParagraphTransformer implements BodyElementTransformerInterface
{
    public function getType(): string
    {
        return 'paragraph';
    }

    public function transform(
        array $element,
        array $resolvedMultimedia,
        AggregatorContext $context
    ): array {
        return [
            'type' => 'paragraph',
            'content' => $element['content'] ?? '',
            'attributes' => $element['attributes'] ?? [],
        ];
    }
}
