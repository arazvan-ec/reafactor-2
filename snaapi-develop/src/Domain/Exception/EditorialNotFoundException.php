<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class EditorialNotFoundException extends SnaApiException
{
    public static function withId(string $id): self
    {
        $exception = new self(sprintf('Editorial with ID "%s" not found', $id));
        $exception->context = ['editorial_id' => $id];
        return $exception;
    }
}
