<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer;

use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\ValueObject\TransformationContext;
use App\Infrastructure\Attribute\AsJsonTransformer;

/**
 * Transforms section aggregator results to JSON format.
 */
#[AsJsonTransformer(type: 'section')]
final class SectionJsonTransformer implements JsonTransformerInterface
{
    public function getType(): string
    {
        return 'section';
    }

    public function supports(mixed $data): bool
    {
        if ($data === null) {
            return false;
        }

        if (!is_array($data) && !is_object($data)) {
            return false;
        }

        $dataArray = is_object($data) ? (array) $data : $data;

        // Check if this looks like section data
        return isset($dataArray['name']) || isset($dataArray['slug']) || isset($dataArray['siteId']);
    }

    public function transform(mixed $data, TransformationContext $context): array
    {
        if ($data === null) {
            return [];
        }

        $dataArray = is_object($data) ? (array) $data : $data;

        return [
            'id' => $dataArray['id'] ?? null,
            'name' => $dataArray['name'] ?? null,
            'slug' => $dataArray['slug'] ?? null,
            'url' => $dataArray['url'] ?? null,
            'siteId' => $dataArray['siteId'] ?? null,
            'siteName' => $dataArray['siteName'] ?? null,
            'parentId' => $dataArray['parentId'] ?? null,
            'metadata' => $dataArray['metadata'] ?? [],
        ];
    }
}
