<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer;

use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\ValueObject\TransformationContext;
use App\Infrastructure\Attribute\AsJsonTransformer;

/**
 * Transforms tag aggregator results to JSON format.
 */
#[AsJsonTransformer(type: 'tag')]
final class TagJsonTransformer implements JsonTransformerInterface
{
    public function getType(): string
    {
        return 'tag';
    }

    public function supports(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // Check if this looks like tag data
        if (empty($data)) {
            return true; // Empty array is valid for tags
        }

        // Check first element has tag-like structure
        $first = reset($data);

        return is_array($first) && (isset($first['name']) || isset($first['slug']));
    }

    public function transform(mixed $data, TransformationContext $context): array
    {
        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($tag) => $this->transformTag($tag), $data);
    }

    /**
     * Transform a single tag to JSON format.
     *
     * @param mixed $tag
     * @return array<string, mixed>
     */
    private function transformTag(mixed $tag): array
    {
        if (!is_array($tag) && !is_object($tag)) {
            return ['id' => (string) $tag];
        }

        $tagData = is_object($tag) ? (array) $tag : $tag;

        return [
            'id' => $tagData['id'] ?? null,
            'name' => $tagData['name'] ?? null,
            'slug' => $tagData['slug'] ?? null,
            'description' => $tagData['description'] ?? null,
            'type' => $tagData['type'] ?? 'default',
        ];
    }
}
