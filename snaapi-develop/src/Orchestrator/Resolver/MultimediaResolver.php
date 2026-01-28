<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Multimedia\Client\QueryMultimediaClient;
use Psr\Log\LoggerInterface;

final readonly class MultimediaResolver implements DataResolverInterface
{
    public function __construct(
        private QueryMultimediaClient $multimediaClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $multimediaIds = $this->extractMultimediaIds($editorial);

        foreach ($multimediaIds as $key => $id) {
            try {
                $multimedia = $this->multimediaClient->findById($id);
                if ($multimedia !== null) {
                    $context->addMultimedia($key, $multimedia);
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve multimedia', [
                    'multimedia_id' => $id,
                    'key' => $key,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 90;
    }

    private function extractMultimediaIds(array $editorial): array
    {
        $ids = [];

        if (!empty($editorial['multimediaId'])) {
            $ids['main'] = $editorial['multimediaId'];
        }

        if (!empty($editorial['openingMultimediaId'])) {
            $ids['opening'] = $editorial['openingMultimediaId'];
        }

        if (!empty($editorial['metaImageId'])) {
            $ids['meta'] = $editorial['metaImageId'];
        }

        return $ids;
    }
}
