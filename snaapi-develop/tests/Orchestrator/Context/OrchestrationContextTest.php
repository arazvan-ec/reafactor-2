<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Context;

use App\Orchestrator\Context\OrchestrationContext;
use PHPUnit\Framework\TestCase;

final class OrchestrationContextTest extends TestCase
{
    private OrchestrationContext $context;

    protected function setUp(): void
    {
        $this->context = new OrchestrationContext('editorial-123', 'site-456', 'com');
    }

    public function testConstructorSetsReadOnlyProperties(): void
    {
        $this->assertEquals('editorial-123', $this->context->getEditorialId());
        $this->assertEquals('site-456', $this->context->getSiteId());
        $this->assertEquals('com', $this->context->getExtension());
    }

    public function testEditorialIsNullByDefault(): void
    {
        $this->assertNull($this->context->getEditorial());
        $this->assertFalse($this->context->hasEditorial());
    }

    public function testSetAndGetEditorial(): void
    {
        $editorial = ['id' => '123', 'title' => 'Test'];
        $this->context->setEditorial($editorial);

        $this->assertEquals($editorial, $this->context->getEditorial());
        $this->assertTrue($this->context->hasEditorial());
    }

    public function testSectionIsNullByDefault(): void
    {
        $this->assertNull($this->context->getSection());
        $this->assertFalse($this->context->hasSection());
    }

    public function testSetAndGetSection(): void
    {
        $section = ['id' => 'sec-123', 'name' => 'Sports'];
        $this->context->setSection($section);

        $this->assertEquals($section, $this->context->getSection());
        $this->assertTrue($this->context->hasSection());
    }

    public function testMultimediaIsEmptyByDefault(): void
    {
        $this->assertEquals([], $this->context->getMultimedia());
        $this->assertFalse($this->context->hasMultimedia());
    }

    public function testAddAndGetMultimedia(): void
    {
        $multimedia1 = ['id' => 'mm-1', 'type' => 'photo'];
        $multimedia2 = ['id' => 'mm-2', 'type' => 'video'];

        $this->context->addMultimedia('main', $multimedia1);
        $this->context->addMultimedia('opening', $multimedia2);

        $this->assertEquals([
            'main' => $multimedia1,
            'opening' => $multimedia2,
        ], $this->context->getMultimedia());
        $this->assertTrue($this->context->hasMultimedia());
    }

    public function testJournalistsAreEmptyByDefault(): void
    {
        $this->assertEquals([], $this->context->getJournalists());
        $this->assertFalse($this->context->hasJournalists());
    }

    public function testSetAndGetJournalists(): void
    {
        $journalists = [
            ['id' => 'j-1', 'name' => 'John Doe'],
            ['id' => 'j-2', 'name' => 'Jane Doe'],
        ];
        $this->context->setJournalists($journalists);

        $this->assertEquals($journalists, $this->context->getJournalists());
        $this->assertTrue($this->context->hasJournalists());
    }

    public function testTagsAreEmptyByDefault(): void
    {
        $this->assertEquals([], $this->context->getTags());
        $this->assertFalse($this->context->hasTags());
    }

    public function testSetAndGetTags(): void
    {
        $tags = [
            ['id' => 't-1', 'name' => 'Sports'],
            ['id' => 't-2', 'name' => 'News'],
        ];
        $this->context->setTags($tags);

        $this->assertEquals($tags, $this->context->getTags());
        $this->assertTrue($this->context->hasTags());
    }

    public function testBodyIsNullByDefault(): void
    {
        $this->assertNull($this->context->getBody());
        $this->assertFalse($this->context->hasBody());
    }

    public function testSetAndGetBody(): void
    {
        $body = ['elements' => [['type' => 'paragraph', 'content' => 'Test']]];
        $this->context->setBody($body);

        $this->assertEquals($body, $this->context->getBody());
        $this->assertTrue($this->context->hasBody());
    }

    public function testInsertedNewsAreEmptyByDefault(): void
    {
        $this->assertEquals([], $this->context->getInsertedNews());
        $this->assertFalse($this->context->hasInsertedNews());
    }

    public function testAddAndGetInsertedNews(): void
    {
        $news1 = ['id' => 'n-1', 'title' => 'News 1'];
        $news2 = ['id' => 'n-2', 'title' => 'News 2'];

        $this->context->addInsertedNews($news1);
        $this->context->addInsertedNews($news2);

        $this->assertEquals([$news1, $news2], $this->context->getInsertedNews());
        $this->assertTrue($this->context->hasInsertedNews());
    }

    public function testRecommendedNewsAreEmptyByDefault(): void
    {
        $this->assertEquals([], $this->context->getRecommendedNews());
        $this->assertFalse($this->context->hasRecommendedNews());
    }

    public function testAddAndGetRecommendedNews(): void
    {
        $news1 = ['id' => 'r-1', 'title' => 'Recommended 1'];
        $news2 = ['id' => 'r-2', 'title' => 'Recommended 2'];

        $this->context->addRecommendedNews($news1);
        $this->context->addRecommendedNews($news2);

        $this->assertEquals([$news1, $news2], $this->context->getRecommendedNews());
        $this->assertTrue($this->context->hasRecommendedNews());
    }
}
