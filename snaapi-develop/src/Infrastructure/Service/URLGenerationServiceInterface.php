<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

interface URLGenerationServiceInterface
{
    public function generateEditorialUrl(array $editorial, array $section): string;

    public function generateSectionUrl(array $section): string;

    public function generateTagUrl(array $tag, array $section): string;

    public function generateJournalistUrl(array $journalist, array $section): string;

    public function generateGenericUrl(string $format, string $subdomain, string $siteId, string $urlPath): string;
}
