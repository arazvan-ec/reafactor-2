<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Selector that allows gradual migration from legacy EditorialOrchestrator
 * to the new EditorialOrchestratorFacade based on feature flag.
 *
 * Configuration:
 * - ORCHESTRATOR_FACADE_ENABLED=false (default): Use legacy orchestrator
 * - ORCHESTRATOR_FACADE_ENABLED=true: Use new facade orchestrator
 * - ORCHESTRATOR_FACADE_PERCENTAGE=0-100: Gradual rollout percentage
 */
final class EditorialOrchestratorSelector implements EditorialOrchestratorInterface
{
    public function __construct(
        private readonly EditorialOrchestrator $legacyOrchestrator,
        private readonly EditorialOrchestratorFacade $facadeOrchestrator,
        private readonly LoggerInterface $logger,
        private readonly bool $facadeEnabled = false,
        private readonly int $facadePercentage = 0,
    ) {}

    public function execute(Request $request): array
    {
        $useFacade = $this->shouldUseFacade($request);

        $this->logger->info('Editorial orchestrator selected', [
            'use_facade' => $useFacade,
            'editorial_id' => $request->attributes->get('id', 'unknown'),
        ]);

        if ($useFacade) {
            return $this->facadeOrchestrator->execute($request);
        }

        return $this->legacyOrchestrator->execute($request);
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }

    private function shouldUseFacade(Request $request): bool
    {
        if (!$this->facadeEnabled) {
            return false;
        }

        if ($this->facadePercentage >= 100) {
            return true;
        }

        if ($this->facadePercentage <= 0) {
            return false;
        }

        // Use consistent hashing based on editorial ID for deterministic rollout
        $editorialId = $request->attributes->get('id', '');
        if (empty($editorialId)) {
            return false;
        }

        $hash = crc32($editorialId);
        $percentage = abs($hash % 100);

        return $percentage < $this->facadePercentage;
    }
}
