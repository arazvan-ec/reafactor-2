<?php

declare(strict_types=1);

namespace App\Infrastructure\Client\Contract;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Contract for journalist query client.
 *
 * Implementations should provide access to the journalist microservice.
 */
interface QueryJournalistClientInterface
{
    /**
     * Find a journalist by their ID.
     *
     * @param string $id Journalist identifier
     * @return mixed|null Journalist data or null if not found
     */
    public function findById(string $id): mixed;

    /**
     * Find a journalist by ID asynchronously.
     *
     * @param string $id Journalist identifier
     * @return PromiseInterface Resolves to journalist data or null
     */
    public function findByIdAsync(string $id): PromiseInterface;
}
