<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer\BodyElement;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Transformer for subhead body elements.
 */
final class SubHeadTransformer implements BodyElementTransformerInterface
{
    public function getType(): string
    {
        return 'subhead';
    }

    public function transform(
        array $element,
        array $resolvedMultimedia,
        AggregatorContext $context
    ): array {
        return [
            'type' => 'subhead',
            'content' => $element['content'] ?? '',
            'level' => $element['level'] ?? 2,
            'attributes' => $element['attributes'] ?? [],
        ];
    }
}
