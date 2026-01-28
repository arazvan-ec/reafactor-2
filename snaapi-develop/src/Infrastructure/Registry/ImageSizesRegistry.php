<?php

declare(strict_types=1);

namespace App\Infrastructure\Registry;

use App\Domain\Exception\InvalidAspectRatioException;

final class ImageSizesRegistry
{
    public const ASPECT_RATIO_4_3 = '4:3';
    public const ASPECT_RATIO_16_9 = '16:9';
    public const ASPECT_RATIO_1_1 = '1:1';
    public const ASPECT_RATIO_3_4 = '3:4';
    public const ASPECT_RATIO_9_16 = '9:16';

    private const WIDTH = 'width';
    private const HEIGHT = 'height';

    private const SIZES = [
        self::ASPECT_RATIO_4_3 => [
            '1440w' => [self::WIDTH => 1440, self::HEIGHT => 1080],
            '1200w' => [self::WIDTH => 1200, self::HEIGHT => 900],
            '1024w' => [self::WIDTH => 1024, self::HEIGHT => 768],
            '800w'  => [self::WIDTH => 800, self::HEIGHT => 600],
            '640w'  => [self::WIDTH => 640, self::HEIGHT => 480],
            '480w'  => [self::WIDTH => 480, self::HEIGHT => 360],
            '320w'  => [self::WIDTH => 320, self::HEIGHT => 240],
            '240w'  => [self::WIDTH => 240, self::HEIGHT => 180],
            '160w'  => [self::WIDTH => 160, self::HEIGHT => 120],
            '120w'  => [self::WIDTH => 120, self::HEIGHT => 90],
            '80w'   => [self::WIDTH => 80, self::HEIGHT => 60],
        ],
        self::ASPECT_RATIO_16_9 => [
            '1920w' => [self::WIDTH => 1920, self::HEIGHT => 1080],
            '1600w' => [self::WIDTH => 1600, self::HEIGHT => 900],
            '1280w' => [self::WIDTH => 1280, self::HEIGHT => 720],
            '1024w' => [self::WIDTH => 1024, self::HEIGHT => 576],
            '854w'  => [self::WIDTH => 854, self::HEIGHT => 480],
            '640w'  => [self::WIDTH => 640, self::HEIGHT => 360],
            '480w'  => [self::WIDTH => 480, self::HEIGHT => 270],
            '426w'  => [self::WIDTH => 426, self::HEIGHT => 240],
            '320w'  => [self::WIDTH => 320, self::HEIGHT => 180],
            '256w'  => [self::WIDTH => 256, self::HEIGHT => 144],
        ],
        self::ASPECT_RATIO_1_1 => [
            '1080w' => [self::WIDTH => 1080, self::HEIGHT => 1080],
            '800w'  => [self::WIDTH => 800, self::HEIGHT => 800],
            '640w'  => [self::WIDTH => 640, self::HEIGHT => 640],
            '480w'  => [self::WIDTH => 480, self::HEIGHT => 480],
            '320w'  => [self::WIDTH => 320, self::HEIGHT => 320],
            '240w'  => [self::WIDTH => 240, self::HEIGHT => 240],
            '160w'  => [self::WIDTH => 160, self::HEIGHT => 160],
            '120w'  => [self::WIDTH => 120, self::HEIGHT => 120],
        ],
        self::ASPECT_RATIO_3_4 => [
            '1080w' => [self::WIDTH => 1080, self::HEIGHT => 1440],
            '900w'  => [self::WIDTH => 900, self::HEIGHT => 1200],
            '768w'  => [self::WIDTH => 768, self::HEIGHT => 1024],
            '600w'  => [self::WIDTH => 600, self::HEIGHT => 800],
            '480w'  => [self::WIDTH => 480, self::HEIGHT => 640],
            '360w'  => [self::WIDTH => 360, self::HEIGHT => 480],
            '240w'  => [self::WIDTH => 240, self::HEIGHT => 320],
        ],
        self::ASPECT_RATIO_9_16 => [
            '1080w' => [self::WIDTH => 1080, self::HEIGHT => 1920],
            '720w'  => [self::WIDTH => 720, self::HEIGHT => 1280],
            '480w'  => [self::WIDTH => 480, self::HEIGHT => 854],
            '360w'  => [self::WIDTH => 360, self::HEIGHT => 640],
            '270w'  => [self::WIDTH => 270, self::HEIGHT => 480],
            '180w'  => [self::WIDTH => 180, self::HEIGHT => 320],
        ],
    ];

    public static function getSizesForRatio(string $ratio): array
    {
        if (!isset(self::SIZES[$ratio])) {
            throw InvalidAspectRatioException::create($ratio, self::getSupportedRatios());
        }
        return self::SIZES[$ratio];
    }

    public static function getSize(string $ratio, string $sizeKey): ?array
    {
        return self::SIZES[$ratio][$sizeKey] ?? null;
    }

    public static function getSupportedRatios(): array
    {
        return array_keys(self::SIZES);
    }

    public static function isRatioSupported(string $ratio): bool
    {
        return isset(self::SIZES[$ratio]);
    }

    public static function getAllSizes(): array
    {
        return self::SIZES;
    }

    public static function getDefaultRatio(): string
    {
        return self::ASPECT_RATIO_4_3;
    }
}
