<?php

declare(strict_types=1);

namespace App\Domain\Transformer\Exception;

/**
 * Thrown when a transformer is not found for a given type.
 */
final class TransformerNotFoundException extends \RuntimeException
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(string $type)
    {
        parent::__construct(sprintf("Transformer for type '%s' not found in registry", $type));
        $this->context = ['type' => $type];
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
