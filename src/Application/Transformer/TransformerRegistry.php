<?php

declare(strict_types=1);

namespace App\Application\Transformer;

use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\Exception\TransformerNotFoundException;

/**
 * Registry for JSON transformer instances.
 *
 * Manages registration and retrieval of transformers by type.
 * Transformers are registered via Compiler Pass at container build time.
 */
final class TransformerRegistry
{
    /**
     * @var array<string, JsonTransformerInterface>
     */
    private array $transformers = [];

    /**
     * Register a transformer.
     */
    public function register(JsonTransformerInterface $transformer): void
    {
        $this->transformers[$transformer->getType()] = $transformer;
    }

    /**
     * Get transformer by type.
     *
     * @throws TransformerNotFoundException if not found
     */
    public function get(string $type): JsonTransformerInterface
    {
        if (!isset($this->transformers[$type])) {
            throw new TransformerNotFoundException($type);
        }

        return $this->transformers[$type];
    }

    /**
     * Check if transformer exists for type.
     */
    public function has(string $type): bool
    {
        return isset($this->transformers[$type]);
    }

    /**
     * Find transformer that supports the given data.
     *
     * @return JsonTransformerInterface|null First matching transformer or null
     */
    public function getForData(mixed $data): ?JsonTransformerInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($data)) {
                return $transformer;
            }
        }

        return null;
    }

    /**
     * Get all registered transformers.
     *
     * @return JsonTransformerInterface[]
     */
    public function getAll(): array
    {
        return array_values($this->transformers);
    }

    /**
     * Get all registered types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return array_keys($this->transformers);
    }

    /**
     * Get count of registered transformers.
     */
    public function count(): int
    {
        return count($this->transformers);
    }
}
