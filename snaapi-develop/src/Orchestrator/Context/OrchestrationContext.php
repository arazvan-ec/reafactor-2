<?php

declare(strict_types=1);

namespace App\Orchestrator\Context;

final class OrchestrationContext
{
    private ?array $editorial = null;
    private ?array $section = null;
    private array $multimedia = [];
    private array $journalists = [];
    private array $tags = [];
    private ?array $body = null;
    private array $insertedNews = [];
    private array $recommendedNews = [];

    public function __construct(
        private readonly string $editorialId,
        private readonly string $siteId,
        private readonly string $extension,
    ) {}

    public function getEditorialId(): string
    {
        return $this->editorialId;
    }

    public function getSiteId(): string
    {
        return $this->siteId;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setEditorial(array $editorial): void
    {
        $this->editorial = $editorial;
    }

    public function getEditorial(): ?array
    {
        return $this->editorial;
    }

    public function hasEditorial(): bool
    {
        return $this->editorial !== null;
    }

    public function setSection(array $section): void
    {
        $this->section = $section;
    }

    public function getSection(): ?array
    {
        return $this->section;
    }

    public function hasSection(): bool
    {
        return $this->section !== null;
    }

    public function addMultimedia(string $key, array $multimedia): void
    {
        $this->multimedia[$key] = $multimedia;
    }

    public function getMultimedia(): array
    {
        return $this->multimedia;
    }

    public function hasMultimedia(): bool
    {
        return !empty($this->multimedia);
    }

    public function setJournalists(array $journalists): void
    {
        $this->journalists = $journalists;
    }

    public function getJournalists(): array
    {
        return $this->journalists;
    }

    public function hasJournalists(): bool
    {
        return !empty($this->journalists);
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function hasTags(): bool
    {
        return !empty($this->tags);
    }

    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }

    public function hasBody(): bool
    {
        return $this->body !== null;
    }

    public function addInsertedNews(array $news): void
    {
        $this->insertedNews[] = $news;
    }

    public function getInsertedNews(): array
    {
        return $this->insertedNews;
    }

    public function hasInsertedNews(): bool
    {
        return !empty($this->insertedNews);
    }

    public function addRecommendedNews(array $news): void
    {
        $this->recommendedNews[] = $news;
    }

    public function getRecommendedNews(): array
    {
        return $this->recommendedNews;
    }

    public function hasRecommendedNews(): bool
    {
        return !empty($this->recommendedNews);
    }
}
