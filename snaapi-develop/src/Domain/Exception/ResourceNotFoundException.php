<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ResourceNotFoundException extends SnaApiException
{
    public static function forResource(string $resourceType, string $id): self
    {
        $exception = new self(sprintf('%s with ID "%s" not found', $resourceType, $id));
        $exception->context = [
            'resource_type' => $resourceType,
            'resource_id' => $id,
        ];
        return $exception;
    }
}
