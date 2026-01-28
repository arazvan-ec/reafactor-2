<?php

namespace App\Infrastructure\Service;

use App\Infrastructure\Registry\ImageSizesRegistry;
use Ec\Editorial\Domain\Model\Body\AbstractPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureDefault;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class PictureShots
{
    private const WIDTH = 'width';
    private const HEIGHT = 'height';

    /**
     * Mapping from picture orientation to aspect ratio.
     * Uses centralized ImageSizesRegistry constants.
     */
    private const ORIENTATION_TO_RATIO = [
        AbstractPicture::ORIENTATION_SQUARE => ImageSizesRegistry::ASPECT_RATIO_1_1,
        AbstractPicture::ORIENTATION_PORTRAIT => ImageSizesRegistry::ASPECT_RATIO_3_4,
        AbstractPicture::ORIENTATION_LANDSCAPE => ImageSizesRegistry::ASPECT_RATIO_4_3,
        AbstractPicture::ORIENTATION_LANDSCAPE_3_2 => ImageSizesRegistry::ASPECT_RATIO_3_2,
        AbstractPicture::ORIENTATION_PORTRAIT_2_3 => ImageSizesRegistry::ASPECT_RATIO_2_3,
    ];

    public function __construct(
        private Thumbor $thumbor,
    ) {
    }

    private function retrieveAspectRatio(string $orientation): string
    {
        return self::ORIENTATION_TO_RATIO[$orientation] ?? ImageSizesRegistry::ASPECT_RATIO_16_9;
    }

    /**
     * @return array<string, string>
     */
    private function retrieveAllShotsByAspectRatio(string $fileName, BodyTagPictureDefault $bodytag): array
    {
        $shots = [];
        $aspectRatio = $this->retrieveAspectRatio($bodytag->orientation());
        $sizes = ImageSizesRegistry::getSizesForRatio($aspectRatio);

        foreach ($sizes as $viewport => $sizeValues) {
            $shots[$viewport] = $this->thumbor->retriveCropBodyTagPicture(
                $fileName,
                $sizeValues[self::WIDTH],
                $sizeValues[self::HEIGHT],
                $bodytag->topX(),
                $bodytag->topY(),
                $bodytag->bottomX(),
                $bodytag->bottomY()
            );
        }

        return $shots;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array|string[]
     */
    public function retrieveShotsByPhotoId(array $resolveData, BodyTagPictureDefault $bodyTagPicture): array
    {
        $photoFile = $this->retrievePhotoFile($resolveData, $bodyTagPicture);
        if ($photoFile) {
            return $this->retrieveAllShotsByAspectRatio($photoFile, $bodyTagPicture);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $resolveData
     */
    private function retrievePhotoFile(array $resolveData, BodyTagPictureDefault $bodyTagPicture): string
    {
        $photoFile = '';

        if (!isset($resolveData['photoFromBodyTags'])) {
            return $photoFile;
        }
        /** @var array<string, string> $photoFromBodyTags */
        $photoFromBodyTags = $resolveData['photoFromBodyTags'];

        if (isset($photoFromBodyTags[$bodyTagPicture->id()->id()])) {
            return $photoFromBodyTags[$bodyTagPicture->id()->id()]->file(); // @phpstan-ignore method.nonObject
        }

        return $photoFile;
    }
}
