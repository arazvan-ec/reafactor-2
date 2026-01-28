<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Service\URLGenerationService;
use PHPUnit\Framework\TestCase;

final class URLGenerationServiceTest extends TestCase
{
    private URLGenerationService $service;

    protected function setUp(): void
    {
        $this->service = new URLGenerationService('com');
    }

    public function testGenerateEditorialUrl(): void
    {
        $editorial = ['urlPath' => 'news/article-title'];
        $section = ['siteId' => '1', 'isSubdomainBlog' => false]; // 1 = elconfidencial

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.elconfidencial.com/news/article-title', $url);
    }

    public function testGenerateEditorialUrlWithPath(): void
    {
        $editorial = ['path' => 'news/another-article'];
        $section = ['siteId' => '1', 'isSubdomainBlog' => false];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.elconfidencial.com/news/another-article', $url);
    }

    public function testGenerateEditorialUrlWithBlogSubdomain(): void
    {
        $editorial = ['urlPath' => 'post/blog-post'];
        $section = ['siteId' => '1', 'isSubdomainBlog' => true];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://blog.elconfidencial.com/post/blog-post', $url);
    }

    public function testGenerateEditorialUrlWithLeadingSlash(): void
    {
        $editorial = ['urlPath' => '/news/article'];
        $section = ['siteId' => '1', 'isSubdomainBlog' => false];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.elconfidencial.com/news/article', $url);
    }

    public function testGenerateSectionUrl(): void
    {
        $section = ['siteId' => '1', 'urlPath' => 'sports', 'isSubdomainBlog' => false];

        $url = $this->service->generateSectionUrl($section);

        $this->assertEquals('https://www.elconfidencial.com/sports', $url);
    }

    public function testGenerateSectionUrlWithBlogSubdomain(): void
    {
        $section = ['siteId' => '1', 'urlPath' => 'tech', 'isSubdomainBlog' => true];

        $url = $this->service->generateSectionUrl($section);

        $this->assertEquals('https://blog.elconfidencial.com/tech', $url);
    }

    public function testGenerateTagUrl(): void
    {
        $tag = ['urlPath' => 'football'];
        $section = ['siteId' => '1', 'isSubdomainBlog' => false];

        $url = $this->service->generateTagUrl($tag, $section);

        $this->assertEquals('https://www.elconfidencial.com/tags/football', $url);
    }

    public function testGenerateTagUrlWithSlug(): void
    {
        $tag = ['slug' => 'basketball'];
        $section = ['siteId' => '1', 'isSubdomainBlog' => false];

        $url = $this->service->generateTagUrl($tag, $section);

        $this->assertEquals('https://www.elconfidencial.com/tags/basketball', $url);
    }

    public function testGenerateJournalistUrl(): void
    {
        $journalist = ['alias' => 'john-doe'];
        $section = ['siteId' => '1'];

        $url = $this->service->generateJournalistUrl($journalist, $section);

        $this->assertEquals('https://www.elconfidencial.com/autor/john-doe', $url);
    }

    public function testGenerateJournalistUrlWithUrlAlias(): void
    {
        $journalist = ['urlAlias' => 'jane-doe'];
        $section = ['siteId' => '1'];

        $url = $this->service->generateJournalistUrl($journalist, $section);

        $this->assertEquals('https://www.elconfidencial.com/autor/jane-doe', $url);
    }

    public function testGenerateJournalistUrlWithSlug(): void
    {
        $journalist = ['slug' => 'reporter-x'];
        $section = ['siteId' => '1'];

        $url = $this->service->generateJournalistUrl($journalist, $section);

        $this->assertEquals('https://www.elconfidencial.com/autor/reporter-x', $url);
    }

    public function testGenerateGenericUrl(): void
    {
        $url = $this->service->generateGenericUrl(
            'https://%s.%s.%s/%s',
            'www',
            '1',
            'custom/path'
        );

        $this->assertEquals('https://www.elconfidencial.com/custom/path', $url);
    }

    public function testGenerateGenericUrlWithBlogSubdomain(): void
    {
        $url = $this->service->generateGenericUrl(
            'https://%s.%s.%s/%s',
            'blog',
            '1',
            'post/title'
        );

        $this->assertEquals('https://blog.elconfidencial.com/post/title', $url);
    }

    public function testVanitatisUrls(): void
    {
        $editorial = ['urlPath' => 'celebs/news'];
        $section = ['siteId' => '2', 'isSubdomainBlog' => false]; // 2 = vanitatis

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.vanitatis.elconfidencial.com/celebs/news', $url);
    }

    public function testAlimenteUrls(): void
    {
        $editorial = ['urlPath' => 'health/nutrition'];
        $section = ['siteId' => '5', 'isSubdomainBlog' => false]; // 5 = alimente

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.alimente.elconfidencial.com/health/nutrition', $url);
    }

    public function testUnknownSiteIdDefaultsToElconfidencial(): void
    {
        $editorial = ['urlPath' => 'test/article'];
        $section = ['siteId' => '999', 'isSubdomainBlog' => false]; // Unknown siteId

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.elconfidencial.com/test/article', $url);
    }

    public function testMissingFieldsReturnEmptyPaths(): void
    {
        $editorial = [];
        $section = ['siteId' => '', 'isSubdomainBlog' => false];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        // Empty siteId defaults to 'elconfidencial'
        $this->assertEquals('https://www.elconfidencial.com/', $url);
    }
}
