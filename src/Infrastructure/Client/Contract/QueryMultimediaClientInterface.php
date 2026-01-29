<?php

declare(strict_types=1);

namespace App\Infrastructure\Client\Contract;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Contract for multimedia query client.
 *
 * Implementations should provide access to the multimedia microservice.
 */
interface QueryMultimediaClientInterface
{
    /**
     * Find multimedia by its ID.
     *
     * @param string $id Multimedia identifier
     * @return mixed|null Multimedia data or null if not found
     */
    public function findById(string $id): mixed;

    /**
     * Find multimedia by ID asynchronously.
     *
     * @param string $id Multimedia identifier
     * @return PromiseInterface Resolves to multimedia data or null
     */
    public function findByIdAsync(string $id): PromiseInterface;
}
