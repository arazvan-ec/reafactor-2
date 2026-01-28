<?php

declare(strict_types=1);

namespace App\Orchestrator\Context;

use Symfony\Component\HttpFoundation\Request;

final readonly class OrchestrationContextFactory
{
    public function __construct(
        private string $extension,
    ) {}

    public function createFromRequest(Request $request): OrchestrationContext
    {
        $editorialId = $request->attributes->get('id', '');
        $siteId = $request->attributes->get('siteId', '');

        return new OrchestrationContext(
            editorialId: $editorialId,
            siteId: $siteId,
            extension: $this->extension,
        );
    }

    public function create(string $editorialId, string $siteId): OrchestrationContext
    {
        return new OrchestrationContext(
            editorialId: $editorialId,
            siteId: $siteId,
            extension: $this->extension,
        );
    }
}
