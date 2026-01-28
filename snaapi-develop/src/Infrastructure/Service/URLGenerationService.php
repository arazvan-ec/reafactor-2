<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

final readonly class URLGenerationService implements URLGenerationServiceInterface
{
    public function __construct(
        private string $extension,
    ) {}

    public function generateEditorialUrl(array $editorial, array $section): string
    {
        $subdomain = $this->getSubdomain($section);
        $siteId = $section['siteId'] ?? '';
        $urlPath = $editorial['urlPath'] ?? $editorial['path'] ?? '';

        return sprintf(
            'https://%s.%s.%s/%s',
            $subdomain,
            $siteId,
            $this->extension,
            ltrim($urlPath, '/')
        );
    }

    public function generateSectionUrl(array $section): string
    {
        $subdomain = $this->getSubdomain($section);
        $siteId = $section['siteId'] ?? '';
        $urlPath = $section['urlPath'] ?? $section['path'] ?? '';

        return sprintf(
            'https://%s.%s.%s/%s',
            $subdomain,
            $siteId,
            $this->extension,
            ltrim($urlPath, '/')
        );
    }

    public function generateTagUrl(array $tag, array $section): string
    {
        $subdomain = $this->getSubdomain($section);
        $siteId = $section['siteId'] ?? '';
        $tagPath = $tag['urlPath'] ?? $tag['path'] ?? $tag['slug'] ?? '';

        return sprintf(
            'https://%s.%s.%s/tag/%s',
            $subdomain,
            $siteId,
            $this->extension,
            ltrim($tagPath, '/')
        );
    }

    public function generateJournalistUrl(array $journalist, array $section): string
    {
        $siteId = $section['siteId'] ?? '';
        $alias = $journalist['alias'] ?? $journalist['urlAlias'] ?? $journalist['slug'] ?? '';

        return sprintf(
            'https://www.%s.%s/autor/%s',
            $siteId,
            $this->extension,
            ltrim($alias, '/')
        );
    }

    private function getSubdomain(array $section): string
    {
        return ($section['isSubdomainBlog'] ?? false) ? 'blog' : 'www';
    }
}
