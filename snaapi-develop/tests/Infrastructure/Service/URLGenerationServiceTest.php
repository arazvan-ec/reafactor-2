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
        $section = ['siteId' => 'example', 'isSubdomainBlog' => false];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.example.com/news/article-title', $url);
    }

    public function testGenerateEditorialUrlWithPath(): void
    {
        $editorial = ['path' => 'news/another-article'];
        $section = ['siteId' => 'example', 'isSubdomainBlog' => false];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.example.com/news/another-article', $url);
    }

    public function testGenerateEditorialUrlWithBlogSubdomain(): void
    {
        $editorial = ['urlPath' => 'post/blog-post'];
        $section = ['siteId' => 'example', 'isSubdomainBlog' => true];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://blog.example.com/post/blog-post', $url);
    }

    public function testGenerateEditorialUrlWithLeadingSlash(): void
    {
        $editorial = ['urlPath' => '/news/article'];
        $section = ['siteId' => 'example', 'isSubdomainBlog' => false];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www.example.com/news/article', $url);
    }

    public function testGenerateSectionUrl(): void
    {
        $section = ['siteId' => 'example', 'urlPath' => 'sports', 'isSubdomainBlog' => false];

        $url = $this->service->generateSectionUrl($section);

        $this->assertEquals('https://www.example.com/sports', $url);
    }

    public function testGenerateSectionUrlWithBlogSubdomain(): void
    {
        $section = ['siteId' => 'example', 'urlPath' => 'tech', 'isSubdomainBlog' => true];

        $url = $this->service->generateSectionUrl($section);

        $this->assertEquals('https://blog.example.com/tech', $url);
    }

    public function testGenerateTagUrl(): void
    {
        $tag = ['urlPath' => 'football'];
        $section = ['siteId' => 'example', 'isSubdomainBlog' => false];

        $url = $this->service->generateTagUrl($tag, $section);

        $this->assertEquals('https://www.example.com/tag/football', $url);
    }

    public function testGenerateTagUrlWithSlug(): void
    {
        $tag = ['slug' => 'basketball'];
        $section = ['siteId' => 'example', 'isSubdomainBlog' => false];

        $url = $this->service->generateTagUrl($tag, $section);

        $this->assertEquals('https://www.example.com/tag/basketball', $url);
    }

    public function testGenerateJournalistUrl(): void
    {
        $journalist = ['alias' => 'john-doe'];
        $section = ['siteId' => 'example'];

        $url = $this->service->generateJournalistUrl($journalist, $section);

        $this->assertEquals('https://www.example.com/autor/john-doe', $url);
    }

    public function testGenerateJournalistUrlWithUrlAlias(): void
    {
        $journalist = ['urlAlias' => 'jane-doe'];
        $section = ['siteId' => 'example'];

        $url = $this->service->generateJournalistUrl($journalist, $section);

        $this->assertEquals('https://www.example.com/autor/jane-doe', $url);
    }

    public function testGenerateJournalistUrlWithSlug(): void
    {
        $journalist = ['slug' => 'reporter-x'];
        $section = ['siteId' => 'example'];

        $url = $this->service->generateJournalistUrl($journalist, $section);

        $this->assertEquals('https://www.example.com/autor/reporter-x', $url);
    }

    public function testMissingFieldsReturnEmptyPaths(): void
    {
        $editorial = [];
        $section = ['siteId' => '', 'isSubdomainBlog' => false];

        $url = $this->service->generateEditorialUrl($editorial, $section);

        $this->assertEquals('https://www..com/', $url);
    }
}
