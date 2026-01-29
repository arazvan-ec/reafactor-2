<?php

declare(strict_types=1);

namespace App\Domain\Transformer\Contract;

use App\Domain\Transformer\ValueObject\TransformationContext;

/**
 * Contract for transforming aggregated data to JSON-serializable format.
 *
 * Transformers convert domain data into the final JSON structure
 * that will be returned to API consumers.
 */
interface JsonTransformerInterface
{
    /**
     * The type of data this transformer handles.
     *
     * Used for auto-registration and lookup.
     *
     * @example "tag", "multimedia", "paragraph", "bodyTagPicture"
     */
    public function getType(): string;

    /**
     * Transform data to JSON-serializable array.
     *
     * @param mixed $data Domain object or raw data to transform
     * @param TransformationContext $context Access to other results and options
     * @return array<string, mixed> JSON-serializable structure
     */
    public function transform(mixed $data, TransformationContext $context): array;

    /**
     * Whether this transformer supports the given data.
     *
     * @param mixed $data Data to check
     */
    public function supports(mixed $data): bool;
}
