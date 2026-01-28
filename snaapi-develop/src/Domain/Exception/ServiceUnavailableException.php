<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ServiceUnavailableException extends SnaApiException
{
    public static function forService(string $serviceName, ?\Throwable $previous = null): self
    {
        $exception = new self(
            sprintf('Service "%s" is currently unavailable', $serviceName),
            0,
            $previous
        );
        $exception->context = ['service_name' => $serviceName];
        return $exception;
    }
}
