<?php

declare(strict_types=1);

namespace App\Infrastructure\Client\Contract;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Contract for tag query client.
 *
 * Implementations should provide access to the tag microservice.
 */
interface QueryTagClientInterface
{
    /**
     * Find a tag by its ID.
     *
     * @param string $id Tag identifier
     * @return mixed|null Tag data or null if not found
     */
    public function findById(string $id): mixed;

    /**
     * Find a tag by ID asynchronously.
     *
     * @param string $id Tag identifier
     * @return PromiseInterface Resolves to tag data or null
     */
    public function findByIdAsync(string $id): PromiseInterface;
}
