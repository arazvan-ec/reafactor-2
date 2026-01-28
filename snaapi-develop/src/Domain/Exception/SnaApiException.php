<?php

declare(strict_types=1);

namespace App\Domain\Exception;

abstract class SnaApiException extends \Exception
{
    protected array $context = [];

    public function getContext(): array
    {
        return $this->context;
    }

    public function withContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    public function toArray(): array
    {
        return [
            'error' => static::class,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ];
    }
}
