<?php

declare(strict_types=1);

namespace App\Infrastructure\Attribute;

/**
 * Attribute to mark a class as a JSON Transformer for auto-registration.
 *
 * Classes marked with this attribute will be automatically registered
 * in the TransformerRegistry via the JsonTransformerCompilerPass.
 *
 * @example
 * #[AsJsonTransformer(type: 'tag')]
 * final class TagJsonTransformer implements JsonTransformerInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsJsonTransformer
{
    /**
     * @param string $type The data type this transformer handles
     */
    public function __construct(
        public readonly string $type
    ) {
    }
}
