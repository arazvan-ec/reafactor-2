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
    public const ASPECT_RATIO_3_2 = '3:2';
    public const ASPECT_RATIO_2_3 = '2:3';

    private const WIDTH = 'width';
    private const HEIGHT = 'height';

    private const SIZES = [
        self::ASPECT_RATIO_4_3 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1080'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '900'],
            '996w'  => [self::WIDTH => '996', self::HEIGHT => '747'],
            '767w'  => [self::WIDTH => '767', self::HEIGHT => '575'],
            '600w'  => [self::WIDTH => '600', self::HEIGHT => '450'],
            '560w'  => [self::WIDTH => '560', self::HEIGHT => '420'],
            '568w'  => [self::WIDTH => '568', self::HEIGHT => '426'],
            '557w'  => [self::WIDTH => '557', self::HEIGHT => '418'],
            '414w'  => [self::WIDTH => '414', self::HEIGHT => '311'],
            '390w'  => [self::WIDTH => '390', self::HEIGHT => '292'],
            '382w'  => [self::WIDTH => '382', self::HEIGHT => '286'],
            '381w'  => [self::WIDTH => '381', self::HEIGHT => '286'],
            '375w'  => [self::WIDTH => '375', self::HEIGHT => '281'],
            '360w'  => [self::WIDTH => '360', self::HEIGHT => '270'],
            '328w'  => [self::WIDTH => '328', self::HEIGHT => '246'],
        ],
        self::ASPECT_RATIO_16_9 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '810'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '675'],
            '996w'  => [self::WIDTH => '996', self::HEIGHT => '560'],
            '972w'  => [self::WIDTH => '972', self::HEIGHT => '547'],
            '720w'  => [self::WIDTH => '720', self::HEIGHT => '405'],
            '640w'  => [self::WIDTH => '640', self::HEIGHT => '360'],
            '600w'  => [self::WIDTH => '600', self::HEIGHT => '338'],
            '568w'  => [self::WIDTH => '568', self::HEIGHT => '320'],
            '414w'  => [self::WIDTH => '414', self::HEIGHT => '233'],
            '390w'  => [self::WIDTH => '390', self::HEIGHT => '219'],
            '382w'  => [self::WIDTH => '382', self::HEIGHT => '215'],
            '375w'  => [self::WIDTH => '375', self::HEIGHT => '211'],
            '360w'  => [self::WIDTH => '360', self::HEIGHT => '203'],
            '328w'  => [self::WIDTH => '328', self::HEIGHT => '185'],
        ],
        self::ASPECT_RATIO_1_1 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1440'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1200'],
            '996w'  => [self::WIDTH => '996', self::HEIGHT => '996'],
            '560w'  => [self::WIDTH => '560', self::HEIGHT => '560'],
            '568w'  => [self::WIDTH => '568', self::HEIGHT => '568'],
            '390w'  => [self::WIDTH => '390', self::HEIGHT => '390'],
            '382w'  => [self::WIDTH => '382', self::HEIGHT => '382'],
            '328w'  => [self::WIDTH => '328', self::HEIGHT => '328'],
        ],
        self::ASPECT_RATIO_3_4 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1920'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1600'],
            '996w'  => [self::WIDTH => '996', self::HEIGHT => '1328'],
            '600w'  => [self::WIDTH => '600', self::HEIGHT => '800'],
            '560w'  => [self::WIDTH => '560', self::HEIGHT => '747'],
            '568w'  => [self::WIDTH => '568', self::HEIGHT => '757'],
            '414w'  => [self::WIDTH => '414', self::HEIGHT => '552'],
            '391w'  => [self::WIDTH => '391', self::HEIGHT => '521'],
            '390w'  => [self::WIDTH => '390', self::HEIGHT => '520'],
            '382w'  => [self::WIDTH => '382', self::HEIGHT => '509'],
            '375w'  => [self::WIDTH => '375', self::HEIGHT => '500'],
            '360w'  => [self::WIDTH => '360', self::HEIGHT => '480'],
            '328w'  => [self::WIDTH => '328', self::HEIGHT => '437'],
            '300w'  => [self::WIDTH => '300', self::HEIGHT => '400'],
        ],
        self::ASPECT_RATIO_9_16 => [
            '1080w' => [self::WIDTH => '1080', self::HEIGHT => '1920'],
            '720w'  => [self::WIDTH => '720', self::HEIGHT => '1280'],
            '480w'  => [self::WIDTH => '480', self::HEIGHT => '854'],
            '360w'  => [self::WIDTH => '360', self::HEIGHT => '640'],
            '270w'  => [self::WIDTH => '270', self::HEIGHT => '480'],
            '180w'  => [self::WIDTH => '180', self::HEIGHT => '320'],
        ],
        self::ASPECT_RATIO_3_2 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '960'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '800'],
            '996w'  => [self::WIDTH => '996', self::HEIGHT => '664'],
            '767w'  => [self::WIDTH => '767', self::HEIGHT => '511'],
            '640w'  => [self::WIDTH => '640', self::HEIGHT => '427'],
            '600w'  => [self::WIDTH => '600', self::HEIGHT => '400'],
            '568w'  => [self::WIDTH => '568', self::HEIGHT => '379'],
            '557w'  => [self::WIDTH => '557', self::HEIGHT => '371'],
            '414w'  => [self::WIDTH => '414', self::HEIGHT => '276'],
            '390w'  => [self::WIDTH => '390', self::HEIGHT => '260'],
            '382w'  => [self::WIDTH => '382', self::HEIGHT => '254'],
            '381w'  => [self::WIDTH => '381', self::HEIGHT => '254'],
            '375w'  => [self::WIDTH => '375', self::HEIGHT => '250'],
            '360w'  => [self::WIDTH => '360', self::HEIGHT => '240'],
            '328w'  => [self::WIDTH => '328', self::HEIGHT => '219'],
            'lo-res' => [self::WIDTH => '48', self::HEIGHT => '32'],
        ],
        self::ASPECT_RATIO_2_3 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '2160'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1800'],
            '996w'  => [self::WIDTH => '996', self::HEIGHT => '1494'],
            '767w'  => [self::WIDTH => '767', self::HEIGHT => '1150'],
            '600w'  => [self::WIDTH => '600', self::HEIGHT => '900'],
            '568w'  => [self::WIDTH => '568', self::HEIGHT => '852'],
            '560w'  => [self::WIDTH => '560', self::HEIGHT => '840'],
            '557w'  => [self::WIDTH => '557', self::HEIGHT => '835'],
            '414w'  => [self::WIDTH => '414', self::HEIGHT => '621'],
            '390w'  => [self::WIDTH => '390', self::HEIGHT => '585'],
            '382w'  => [self::WIDTH => '382', self::HEIGHT => '573'],
            '381w'  => [self::WIDTH => '381', self::HEIGHT => '571'],
            '375w'  => [self::WIDTH => '375', self::HEIGHT => '562'],
            '360w'  => [self::WIDTH => '360', self::HEIGHT => '540'],
            '328w'  => [self::WIDTH => '328', self::HEIGHT => '492'],
            'lo-res' => [self::WIDTH => '48', self::HEIGHT => '72'],
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
