<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer;

use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\ValueObject\TransformationContext;
use App\Infrastructure\Attribute\AsJsonTransformer;

/**
 * Transforms multimedia aggregator results to JSON format.
 */
#[AsJsonTransformer(type: 'multimedia')]
final class MultimediaJsonTransformer implements JsonTransformerInterface
{
    public function getType(): string
    {
        return 'multimedia';
    }

    public function supports(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // Check if this looks like multimedia data (keyed by position: main, opening, meta)
        if (empty($data)) {
            return false;
        }

        return isset($data['main']) || isset($data['opening']) || isset($data['meta']);
    }

    public function transform(mixed $data, TransformationContext $context): array
    {
        if (!is_array($data)) {
            return [];
        }

        $transformed = [];

        foreach ($data as $key => $multimedia) {
            if ($multimedia !== null) {
                $transformed[$key] = $this->transformMultimedia($multimedia);
            }
        }

        return $transformed;
    }

    /**
     * Transform a single multimedia item to JSON format.
     *
     * @param mixed $multimedia
     * @return array<string, mixed>
     */
    private function transformMultimedia(mixed $multimedia): array
    {
        if (!is_array($multimedia) && !is_object($multimedia)) {
            return ['id' => (string) $multimedia];
        }

        $data = is_object($multimedia) ? (array) $multimedia : $multimedia;

        return [
            'id' => $data['id'] ?? null,
            'type' => $data['type'] ?? 'image',
            'url' => $data['url'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'caption' => $data['caption'] ?? null,
            'credit' => $data['credit'] ?? null,
            'alt' => $data['alt'] ?? null,
            'mimeType' => $data['mimeType'] ?? null,
            'sizes' => $data['sizes'] ?? [],
        ];
    }
}
