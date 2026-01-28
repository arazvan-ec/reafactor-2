<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Registry;

use App\Domain\Exception\InvalidAspectRatioException;
use App\Infrastructure\Registry\ImageSizesRegistry;
use PHPUnit\Framework\TestCase;

final class ImageSizesRegistryTest extends TestCase
{
    public function testGetSizesForValidRatio(): void
    {
        $sizes = ImageSizesRegistry::getSizesForRatio('4:3');

        $this->assertIsArray($sizes);
        $this->assertArrayHasKey('1440w', $sizes);
        $this->assertEquals(1440, $sizes['1440w']['width']);
        $this->assertEquals(1080, $sizes['1440w']['height']);
    }

    public function testGetSizesForInvalidRatioThrowsException(): void
    {
        $this->expectException(InvalidAspectRatioException::class);

        ImageSizesRegistry::getSizesForRatio('invalid');
    }

    public function testGetSupportedRatios(): void
    {
        $ratios = ImageSizesRegistry::getSupportedRatios();

        $this->assertContains('4:3', $ratios);
        $this->assertContains('16:9', $ratios);
        $this->assertContains('1:1', $ratios);
        $this->assertCount(5, $ratios);
    }

    public function testIsRatioSupported(): void
    {
        $this->assertTrue(ImageSizesRegistry::isRatioSupported('4:3'));
        $this->assertTrue(ImageSizesRegistry::isRatioSupported('16:9'));
        $this->assertFalse(ImageSizesRegistry::isRatioSupported('invalid'));
    }

    public function testGetSize(): void
    {
        $size = ImageSizesRegistry::getSize('4:3', '1440w');

        $this->assertEquals(1440, $size['width']);
        $this->assertEquals(1080, $size['height']);
    }

    public function testGetSizeReturnsNullForInvalidKey(): void
    {
        $size = ImageSizesRegistry::getSize('4:3', 'nonexistent');

        $this->assertNull($size);
    }

    public function testAllRatiosHaveCorrectAspectRatio(): void
    {
        foreach (ImageSizesRegistry::getAllSizes() as $ratio => $sizes) {
            foreach ($sizes as $key => $size) {
                $expectedRatio = $this->parseRatio($ratio);
                $actualRatio = $size['width'] / $size['height'];

                $this->assertEqualsWithDelta(
                    $expectedRatio,
                    $actualRatio,
                    0.01,
                    "Size {$key} in ratio {$ratio} has incorrect aspect ratio"
                );
            }
        }
    }

    public function testGetDefaultRatio(): void
    {
        $this->assertEquals('4:3', ImageSizesRegistry::getDefaultRatio());
    }

    public function testGetAllSizesReturnsAllRatios(): void
    {
        $allSizes = ImageSizesRegistry::getAllSizes();

        $this->assertArrayHasKey('4:3', $allSizes);
        $this->assertArrayHasKey('16:9', $allSizes);
        $this->assertArrayHasKey('1:1', $allSizes);
        $this->assertArrayHasKey('3:4', $allSizes);
        $this->assertArrayHasKey('9:16', $allSizes);
    }

    public function testAspectRatioConstants(): void
    {
        $this->assertEquals('4:3', ImageSizesRegistry::ASPECT_RATIO_4_3);
        $this->assertEquals('16:9', ImageSizesRegistry::ASPECT_RATIO_16_9);
        $this->assertEquals('1:1', ImageSizesRegistry::ASPECT_RATIO_1_1);
        $this->assertEquals('3:4', ImageSizesRegistry::ASPECT_RATIO_3_4);
        $this->assertEquals('9:16', ImageSizesRegistry::ASPECT_RATIO_9_16);
    }

    private function parseRatio(string $ratio): float
    {
        [$width, $height] = explode(':', $ratio);
        return (float) $width / (float) $height;
    }
}
