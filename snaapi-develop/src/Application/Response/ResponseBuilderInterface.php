<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Orchestrator\Context\OrchestrationContext;

interface ResponseBuilderInterface
{
    public function build(OrchestrationContext $context): array;
}
