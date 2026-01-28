<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Infrastructure\Service\URLGenerationServiceInterface;
use App\Orchestrator\Context\OrchestrationContext;

final readonly class EditorialResponseBuilder implements ResponseBuilderInterface
{
    public function __construct(
        private URLGenerationServiceInterface $urlGenerator,
    ) {}

    public function build(OrchestrationContext $context): array
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return [];
        }

        $section = $context->getSection();

        $response = [
            'id' => $editorial['id'] ?? $context->getEditorialId(),
            'title' => $editorial['title'] ?? '',
            'headline' => $editorial['headline'] ?? $editorial['title'] ?? '',
            'subheadline' => $editorial['subheadline'] ?? '',
            'standfirst' => $editorial['standfirst'] ?? '',
            'publishedAt' => $editorial['publishedAt'] ?? null,
            'updatedAt' => $editorial['updatedAt'] ?? null,
        ];

        // Add URLs
        if ($section !== null) {
            $response['url'] = $this->urlGenerator->generateEditorialUrl($editorial, $section);
            $response['section'] = $this->buildSection($section);
        }

        // Add multimedia
        if ($context->hasMultimedia()) {
            $response['multimedia'] = $this->buildMultimedia($context->getMultimedia());
        }

        // Add journalists
        if ($context->hasJournalists()) {
            $response['signatures'] = $this->buildJournalists($context->getJournalists(), $section);
        }

        // Add tags
        if ($context->hasTags()) {
            $response['tags'] = $this->buildTags($context->getTags(), $section);
        }

        // Add body
        if ($context->hasBody()) {
            $response['body'] = $context->getBody();
        }

        // Add inserted news
        if ($context->hasInsertedNews()) {
            $response['insertedNews'] = $context->getInsertedNews();
        }

        // Add recommended news
        if ($context->hasRecommendedNews()) {
            $response['recommendedNews'] = $this->buildRecommendedNews($context->getRecommendedNews(), $section);
        }

        return $response;
    }

    private function buildSection(?array $section): ?array
    {
        if ($section === null) {
            return null;
        }

        return [
            'id' => $section['id'] ?? '',
            'name' => $section['name'] ?? '',
            'url' => $this->urlGenerator->generateSectionUrl($section),
        ];
    }

    private function buildMultimedia(array $multimedia): array
    {
        $result = [];
        foreach ($multimedia as $key => $media) {
            $result[$key] = [
                'id' => $media['id'] ?? '',
                'type' => $media['type'] ?? '',
                'url' => $media['url'] ?? '',
                'caption' => $media['caption'] ?? '',
                'credit' => $media['credit'] ?? '',
            ];
        }
        return $result;
    }

    private function buildJournalists(array $journalists, ?array $section): array
    {
        $result = [];
        foreach ($journalists as $journalist) {
            $item = [
                'id' => $journalist['id'] ?? '',
                'name' => $journalist['name'] ?? $journalist['fullName'] ?? '',
                'role' => $journalist['role'] ?? '',
            ];

            if ($section !== null) {
                $item['url'] = $this->urlGenerator->generateJournalistUrl($journalist, $section);
            }

            $result[] = $item;
        }
        return $result;
    }

    private function buildTags(array $tags, ?array $section): array
    {
        $result = [];
        foreach ($tags as $tag) {
            $item = [
                'id' => $tag['id'] ?? '',
                'name' => $tag['name'] ?? '',
            ];

            if ($section !== null) {
                $item['url'] = $this->urlGenerator->generateTagUrl($tag, $section);
            }

            $result[] = $item;
        }
        return $result;
    }

    private function buildRecommendedNews(array $news, ?array $section): array
    {
        $result = [];
        foreach ($news as $item) {
            $newsItem = [
                'id' => $item['id'] ?? '',
                'title' => $item['title'] ?? '',
                'headline' => $item['headline'] ?? $item['title'] ?? '',
            ];

            if ($section !== null) {
                $newsItem['url'] = $this->urlGenerator->generateEditorialUrl($item, $section);
            }

            $result[] = $newsItem;
        }
        return $result;
    }
}
