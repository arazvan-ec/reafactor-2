<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidAspectRatioException extends SnaApiException
{
    public static function create(string $ratio, array $supportedRatios): self
    {
        $exception = new self(sprintf(
            'Invalid aspect ratio "%s". Supported: %s',
            $ratio,
            implode(', ', $supportedRatios)
        ));
        $exception->context = [
            'provided_ratio' => $ratio,
            'supported_ratios' => $supportedRatios,
        ];
        return $exception;
    }
}
