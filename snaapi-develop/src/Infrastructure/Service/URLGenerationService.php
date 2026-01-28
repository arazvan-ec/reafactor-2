<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Infrastructure\Enum\SitesEnum;

final readonly class URLGenerationService implements URLGenerationServiceInterface
{
    public function __construct(
        private string $extension,
    ) {}

    public function generateEditorialUrl(array $editorial, array $section): string
    {
        $subdomain = $this->getSubdomain($section);
        $siteId = $section['siteId'] ?? '';
        $hostname = SitesEnum::getHostnameById($siteId);
        $urlPath = $editorial['urlPath'] ?? $editorial['path'] ?? '';

        return sprintf(
            'https://%s.%s.%s/%s',
            $subdomain,
            $hostname,
            $this->extension,
            ltrim($urlPath, '/')
        );
    }

    public function generateSectionUrl(array $section): string
    {
        $subdomain = $this->getSubdomain($section);
        $siteId = $section['siteId'] ?? '';
        $hostname = SitesEnum::getHostnameById($siteId);
        $urlPath = $section['urlPath'] ?? $section['path'] ?? '';

        return sprintf(
            'https://%s.%s.%s/%s',
            $subdomain,
            $hostname,
            $this->extension,
            ltrim($urlPath, '/')
        );
    }

    public function generateTagUrl(array $tag, array $section): string
    {
        $siteId = $section['siteId'] ?? '';
        $hostname = SitesEnum::getHostnameById($siteId);
        $tagPath = $tag['urlPath'] ?? $tag['path'] ?? $tag['slug'] ?? '';

        return sprintf(
            'https://www.%s.%s/tags/%s',
            $hostname,
            $this->extension,
            ltrim($tagPath, '/')
        );
    }

    public function generateJournalistUrl(array $journalist, array $section): string
    {
        $siteId = $section['siteId'] ?? '';
        $hostname = SitesEnum::getHostnameById($siteId);
        $alias = $journalist['alias'] ?? $journalist['urlAlias'] ?? $journalist['slug'] ?? '';

        return sprintf(
            'https://www.%s.%s/autor/%s',
            $hostname,
            $this->extension,
            ltrim($alias, '/')
        );
    }

    public function generateGenericUrl(string $format, string $subdomain, string $siteId, string $urlPath): string
    {
        $hostname = SitesEnum::getHostnameById($siteId);

        return sprintf(
            $format,
            $subdomain,
            $hostname,
            $this->extension,
            trim($urlPath, '/')
        );
    }

    private function getSubdomain(array $section): string
    {
        return ($section['isSubdomainBlog'] ?? false) ? 'blog' : 'www';
    }
}
