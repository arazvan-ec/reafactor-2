<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Orchestration\OrchestrationPipeline;
use App\Infrastructure\Factory\OrchestrationContextFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adapter for integrating the new OrchestrationPipeline with existing code.
 *
 * Provides a backwards-compatible interface that can replace or work alongside
 * the existing EditorialOrchestrator. Use this adapter during the migration
 * phase to gradually switch to the new aggregation system.
 *
 * @example
 * // In EditorialOrchestrator or Controller:
 * $result = $this->adapter->orchestrate($editorial, $request);
 */
final class EditorialOrchestratorAdapter
{
    public function __construct(
        private readonly OrchestrationPipeline $pipeline,
        private readonly OrchestrationContextFactory $contextFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Orchestrate editorial data using the new pipeline.
     *
     * @param array<string, mixed> $editorial Editorial data array
     * @param Request|null $request HTTP request for context
     * @return array<string, mixed> Transformed data
     */
    public function orchestrate(array $editorial, ?Request $request = null): array
    {
        try {
            $context = $this->contextFactory->createFromEditorial($editorial, $request);

            return $this->pipeline->execute($context);
        } catch (\Throwable $e) {
            $this->logger->error('Orchestration failed', [
                'editorial_id' => $editorial['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Orchestrate editorial domain object using the new pipeline.
     *
     * @param object $editorial Editorial domain object
     * @param Request|null $request HTTP request for context
     * @return array<string, mixed> Transformed data
     */
    public function orchestrateObject(object $editorial, ?Request $request = null): array
    {
        try {
            $context = $this->contextFactory->createFromEditorialObject($editorial, $request);

            return $this->pipeline->execute($context);
        } catch (\Throwable $e) {
            $editorialId = $this->extractEditorialId($editorial);
            $this->logger->error('Orchestration failed', [
                'editorial_id' => $editorialId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Orchestrate and include metadata (timing, stats).
     *
     * @param array<string, mixed> $editorial Editorial data array
     * @param Request|null $request HTTP request for context
     * @return array{data: array<string, mixed>, _meta: array<string, mixed>}
     */
    public function orchestrateWithMetadata(array $editorial, ?Request $request = null): array
    {
        try {
            $context = $this->contextFactory->createFromEditorial($editorial, $request);

            return $this->pipeline->executeWithMetadata($context);
        } catch (\Throwable $e) {
            $this->logger->error('Orchestration with metadata failed', [
                'editorial_id' => $editorial['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract editorial ID from domain object.
     */
    private function extractEditorialId(object $editorial): string
    {
        if (method_exists($editorial, 'id')) {
            $id = $editorial->id();
            if (is_object($id) && method_exists($id, 'id')) {
                return (string) $id->id();
            }

            return (string) $id;
        }

        return 'unknown';
    }
}
