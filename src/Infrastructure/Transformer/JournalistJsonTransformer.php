<?php

declare(strict_types=1);

namespace App\Infrastructure\Transformer;

use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\ValueObject\TransformationContext;
use App\Infrastructure\Attribute\AsJsonTransformer;

/**
 * Transforms journalist aggregator results to JSON format.
 */
#[AsJsonTransformer(type: 'journalist')]
final class JournalistJsonTransformer implements JsonTransformerInterface
{
    public function getType(): string
    {
        return 'journalist';
    }

    public function supports(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        if (empty($data)) {
            return true; // Empty array is valid for journalists
        }

        // Check first element has journalist-like structure
        $first = reset($data);

        return is_array($first) && (isset($first['name']) || isset($first['aliasId']));
    }

    public function transform(mixed $data, TransformationContext $context): array
    {
        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($journalist) => $this->transformJournalist($journalist), $data);
    }

    /**
     * Transform a single journalist to JSON format.
     *
     * @param mixed $journalist
     * @return array<string, mixed>
     */
    private function transformJournalist(mixed $journalist): array
    {
        if (!is_array($journalist) && !is_object($journalist)) {
            return ['id' => (string) $journalist];
        }

        $data = is_object($journalist) ? (array) $journalist : $journalist;

        return [
            'id' => $data['id'] ?? null,
            'aliasId' => $data['aliasId'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'bio' => $data['bio'] ?? null,
            'imageUrl' => $data['imageUrl'] ?? null,
            'socialLinks' => $data['socialLinks'] ?? [],
        ];
    }
}
