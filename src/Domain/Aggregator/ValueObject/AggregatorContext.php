<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\ValueObject;

/**
 * Immutable context for aggregator execution.
 *
 * Contains all information needed by aggregators:
 * - Editorial identification (id, type)
 * - Raw data from the editorial
 * - Data resolved by previous aggregators
 * - Request metadata
 */
final readonly class AggregatorContext
{
    /**
     * @param string $editorialId   ID of the editorial being processed
     * @param string $editorialType Type of editorial (news, opinion, etc.)
     * @param array<string, mixed> $rawData Raw data from the editorial
     * @param array<string, mixed> $resolvedData Data already resolved by other aggregators
     * @param array<string, mixed> $metadata Request metadata (requestId, siteId, etc.)
     */
    public function __construct(
        private string $editorialId,
        private string $editorialType,
        private array $rawData,
        private array $resolvedData = [],
        private array $metadata = []
    ) {
    }

    public function getEditorialId(): string
    {
        return $this->editorialId;
    }

    public function getEditorialType(): string
    {
        return $this->editorialType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * Get a specific key from raw data.
     */
    public function getRawDataByKey(string $key): mixed
    {
        return $this->rawData[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResolvedData(): array
    {
        return $this->resolvedData;
    }

    /**
     * Get resolved data for a specific aggregator.
     */
    public function getResolvedDataByKey(string $key): mixed
    {
        return $this->resolvedData[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get a specific metadata value.
     */
    public function getMetadataByKey(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    /**
     * Create a new context with additional resolved data.
     *
     * @return self New immutable instance
     */
    public function withResolvedData(string $key, mixed $data): self
    {
        return new self(
            $this->editorialId,
            $this->editorialType,
            $this->rawData,
            array_merge($this->resolvedData, [$key => $data]),
            $this->metadata
        );
    }

    /**
     * Create a new context with additional metadata.
     *
     * @return self New immutable instance
     */
    public function withMetadata(string $key, mixed $value): self
    {
        return new self(
            $this->editorialId,
            $this->editorialType,
            $this->rawData,
            $this->resolvedData,
            array_merge($this->metadata, [$key => $value])
        );
    }

    /**
     * Check if raw data contains a specific key with non-empty value.
     */
    public function hasRawData(string $key): bool
    {
        return isset($this->rawData[$key]) && !empty($this->rawData[$key]);
    }

    /**
     * Check if resolved data contains a specific key.
     */
    public function hasResolvedData(string $key): bool
    {
        return array_key_exists($key, $this->resolvedData);
    }
}
