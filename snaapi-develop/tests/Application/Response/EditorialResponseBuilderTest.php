<?php

declare(strict_types=1);

namespace App\Tests\Application\Response;

use App\Application\Response\EditorialResponseBuilder;
use App\Infrastructure\Service\URLGenerationServiceInterface;
use App\Orchestrator\Context\OrchestrationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EditorialResponseBuilderTest extends TestCase
{
    private URLGenerationServiceInterface&MockObject $urlGenerator;
    private EditorialResponseBuilder $builder;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(URLGenerationServiceInterface::class);
        $this->builder = new EditorialResponseBuilder($this->urlGenerator);
    }

    public function testBuildReturnsEmptyArrayWithoutEditorial(): void
    {
        $context = new OrchestrationContext('id', 'site', 'com');

        $result = $this->builder->build($context);

        $this->assertEquals([], $result);
    }

    public function testBuildBasicEditorial(): void
    {
        $context = new OrchestrationContext('id-123', 'site', 'com');
        $context->setEditorial([
            'id' => 'id-123',
            'title' => 'Test Title',
            'headline' => 'Test Headline',
            'subheadline' => 'Test Subheadline',
            'standfirst' => 'Test Standfirst',
            'publishedAt' => '2026-01-28T10:00:00Z',
            'updatedAt' => '2026-01-28T12:00:00Z',
        ]);

        $result = $this->builder->build($context);

        $this->assertEquals('id-123', $result['id']);
        $this->assertEquals('Test Title', $result['title']);
        $this->assertEquals('Test Headline', $result['headline']);
        $this->assertEquals('Test Subheadline', $result['subheadline']);
        $this->assertEquals('Test Standfirst', $result['standfirst']);
        $this->assertEquals('2026-01-28T10:00:00Z', $result['publishedAt']);
        $this->assertEquals('2026-01-28T12:00:00Z', $result['updatedAt']);
    }

    public function testBuildWithSection(): void
    {
        $context = new OrchestrationContext('id-123', 'site', 'com');
        $context->setEditorial(['id' => 'id-123', 'title' => 'Test']);
        $context->setSection(['id' => 'sec-1', 'name' => 'Sports']);

        $this->urlGenerator
            ->method('generateEditorialUrl')
            ->willReturn('https://www.example.com/article');
        $this->urlGenerator
            ->method('generateSectionUrl')
            ->willReturn('https://www.example.com/sports');

        $result = $this->builder->build($context);

        $this->assertEquals('https://www.example.com/article', $result['url']);
        $this->assertArrayHasKey('section', $result);
        $this->assertEquals('sec-1', $result['section']['id']);
        $this->assertEquals('Sports', $result['section']['name']);
        $this->assertEquals('https://www.example.com/sports', $result['section']['url']);
    }

    public function testBuildWithMultimedia(): void
    {
        $context = new OrchestrationContext('id-123', 'site', 'com');
        $context->setEditorial(['id' => 'id-123', 'title' => 'Test']);
        $context->addMultimedia('main', [
            'id' => 'mm-1',
            'type' => 'photo',
            'url' => 'https://cdn.example.com/image.jpg',
            'caption' => 'Test caption',
            'credit' => 'Test credit',
        ]);

        $result = $this->builder->build($context);

        $this->assertArrayHasKey('multimedia', $result);
        $this->assertArrayHasKey('main', $result['multimedia']);
        $this->assertEquals('mm-1', $result['multimedia']['main']['id']);
        $this->assertEquals('photo', $result['multimedia']['main']['type']);
    }

    public function testBuildWithJournalists(): void
    {
        $context = new OrchestrationContext('id-123', 'site', 'com');
        $context->setEditorial(['id' => 'id-123', 'title' => 'Test']);
        $context->setSection(['id' => 'sec-1', 'siteId' => 'example']);
        $context->setJournalists([
            ['id' => 'j-1', 'name' => 'John Doe', 'role' => 'Reporter', 'alias' => 'john-doe'],
        ]);

        $this->urlGenerator
            ->method('generateEditorialUrl')
            ->willReturn('https://www.example.com/article');
        $this->urlGenerator
            ->method('generateSectionUrl')
            ->willReturn('https://www.example.com/section');
        $this->urlGenerator
            ->method('generateJournalistUrl')
            ->willReturn('https://www.example.com/autor/john-doe');

        $result = $this->builder->build($context);

        $this->assertArrayHasKey('signatures', $result);
        $this->assertCount(1, $result['signatures']);
        $this->assertEquals('j-1', $result['signatures'][0]['id']);
        $this->assertEquals('John Doe', $result['signatures'][0]['name']);
        $this->assertEquals('https://www.example.com/autor/john-doe', $result['signatures'][0]['url']);
    }

    public function testBuildWithTags(): void
    {
        $context = new OrchestrationContext('id-123', 'site', 'com');
        $context->setEditorial(['id' => 'id-123', 'title' => 'Test']);
        $context->setSection(['id' => 'sec-1', 'siteId' => 'example']);
        $context->setTags([
            ['id' => 't-1', 'name' => 'Sports', 'urlPath' => 'sports'],
        ]);

        $this->urlGenerator
            ->method('generateEditorialUrl')
            ->willReturn('https://www.example.com/article');
        $this->urlGenerator
            ->method('generateSectionUrl')
            ->willReturn('https://www.example.com/section');
        $this->urlGenerator
            ->method('generateTagUrl')
            ->willReturn('https://www.example.com/tag/sports');

        $result = $this->builder->build($context);

        $this->assertArrayHasKey('tags', $result);
        $this->assertCount(1, $result['tags']);
        $this->assertEquals('t-1', $result['tags'][0]['id']);
        $this->assertEquals('Sports', $result['tags'][0]['name']);
        $this->assertEquals('https://www.example.com/tag/sports', $result['tags'][0]['url']);
    }

    public function testBuildWithBody(): void
    {
        $context = new OrchestrationContext('id-123', 'site', 'com');
        $context->setEditorial(['id' => 'id-123', 'title' => 'Test']);
        $context->setBody(['elements' => [['type' => 'paragraph', 'content' => 'Test content']]]);

        $result = $this->builder->build($context);

        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('elements', $result['body']);
    }

    public function testBuildWithRecommendedNews(): void
    {
        $context = new OrchestrationContext('id-123', 'site', 'com');
        $context->setEditorial(['id' => 'id-123', 'title' => 'Test']);
        $context->setSection(['id' => 'sec-1', 'siteId' => 'example']);
        $context->addRecommendedNews(['id' => 'r-1', 'title' => 'Recommended 1']);
        $context->addRecommendedNews(['id' => 'r-2', 'title' => 'Recommended 2']);

        $this->urlGenerator
            ->method('generateEditorialUrl')
            ->willReturn('https://www.example.com/article');
        $this->urlGenerator
            ->method('generateSectionUrl')
            ->willReturn('https://www.example.com/section');

        $result = $this->builder->build($context);

        $this->assertArrayHasKey('recommendedNews', $result);
        $this->assertCount(2, $result['recommendedNews']);
        $this->assertEquals('r-1', $result['recommendedNews'][0]['id']);
        $this->assertEquals('Recommended 1', $result['recommendedNews'][0]['title']);
    }
}
