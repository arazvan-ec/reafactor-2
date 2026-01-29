<?php

declare(strict_types=1);

namespace App\Infrastructure\Client\Contract;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Contract for section query client.
 *
 * Implementations should provide access to the section microservice.
 */
interface QuerySectionClientInterface
{
    /**
     * Find a section by its ID.
     *
     * @param string $id Section identifier
     * @return mixed|null Section data or null if not found
     */
    public function findById(string $id): mixed;

    /**
     * Find a section by ID asynchronously.
     *
     * @param string $id Section identifier
     * @return PromiseInterface Resolves to section data or null
     */
    public function findByIdAsync(string $id): PromiseInterface;
}
