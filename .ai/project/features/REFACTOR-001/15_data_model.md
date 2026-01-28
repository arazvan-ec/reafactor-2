# Modelo de Datos / Estructura de Clases - REFACTOR-001

> **Proyecto:** SNAAPI Refactoring
> **Fecha:** 2026-01-28
> **Autor:** Planner/Architect

---

## 1. OrchestrationContext

**Ubicación:** `src/Orchestrator/Context/OrchestrationContext.php`

**Propósito:** Contenedor de datos que fluye a través de todos los resolvers.

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Context;

/**
 * Immutable context object that holds all data during orchestration.
 * Passed through resolvers and transformers.
 */
final class OrchestrationContext implements OrchestrationContextInterface
{
    private ?array $editorial = null;
    private ?array $section = null;
    private array $multimedia = [];
    private array $multimediaOpening = [];
    private array $journalists = [];
    private array $tags = [];
    private ?array $body = null;
    private array $insertedNews = [];
    private array $recommendedNews = [];
    private array $membershipLinks = [];
    private array $metadata = [];

    public function __construct(
        private readonly string $editorialId,
        private readonly string $siteId,
        private readonly string $extension,
    ) {}

    // Getters inmutables
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

    // Setters con fluent interface
    public function withEditorial(array $editorial): self
    {
        $clone = clone $this;
        $clone->editorial = $editorial;
        return $clone;
    }

    public function withSection(array $section): self
    {
        $clone = clone $this;
        $clone->section = $section;
        return $clone;
    }

    public function withMultimedia(string $key, array $multimedia): self
    {
        $clone = clone $this;
        $clone->multimedia[$key] = $multimedia;
        return $clone;
    }

    public function withJournalists(array $journalists): self
    {
        $clone = clone $this;
        $clone->journalists = $journalists;
        return $clone;
    }

    public function withTags(array $tags): self
    {
        $clone = clone $this;
        $clone->tags = $tags;
        return $clone;
    }

    public function withBody(array $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function withInsertedNews(array $news): self
    {
        $clone = clone $this;
        $clone->insertedNews[] = $news;
        return $clone;
    }

    public function withRecommendedNews(array $news): self
    {
        $clone = clone $this;
        $clone->recommendedNews[] = $news;
        return $clone;
    }

    // Getters
    public function getEditorial(): ?array { return $this->editorial; }
    public function getSection(): ?array { return $this->section; }
    public function getMultimedia(): array { return $this->multimedia; }
    public function getJournalists(): array { return $this->journalists; }
    public function getTags(): array { return $this->tags; }
    public function getBody(): ?array { return $this->body; }
    public function getInsertedNews(): array { return $this->insertedNews; }
    public function getRecommendedNews(): array { return $this->recommendedNews; }

    // Helper methods
    public function hasEditorial(): bool
    {
        return $this->editorial !== null;
    }

    public function hasSection(): bool
    {
        return $this->section !== null;
    }

    public function hasMultimedia(string $key): bool
    {
        return isset($this->multimedia[$key]);
    }
}
```

**Campos:**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| editorialId | string | ID único del editorial (inmutable) |
| siteId | string | ID del sitio (inmutable) |
| extension | string | Extensión URL (inmutable) |
| editorial | ?array | Datos del editorial |
| section | ?array | Datos de la sección |
| multimedia | array | Multimedia indexada por key |
| journalists | array | Lista de periodistas |
| tags | array | Lista de etiquetas |
| body | ?array | Cuerpo transformado |
| insertedNews | array | Noticias insertadas |
| recommendedNews | array | Noticias recomendadas |

---

## 2. ImageSizesRegistry

**Ubicación:** `src/Infrastructure/Registry/ImageSizesRegistry.php`

**Propósito:** Centraliza todas las dimensiones de imágenes (elimina duplicación).

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Registry;

use App\Domain\Exception\InvalidAspectRatioException;

/**
 * Centralized registry for image size configurations.
 * Replaces duplicated SIZES_RELATIONS constants across the codebase.
 *
 * @see DetailsMultimediaPhotoDataTransformer (removed constant)
 * @see PictureShots (removed constant)
 * @see MultimediaTrait (removed constant)
 */
final class ImageSizesRegistry
{
    // Aspect ratio constants
    public const ASPECT_RATIO_4_3 = '4:3';
    public const ASPECT_RATIO_16_9 = '16:9';
    public const ASPECT_RATIO_1_1 = '1:1';
    public const ASPECT_RATIO_3_4 = '3:4';
    public const ASPECT_RATIO_9_16 = '9:16';

    // Size key constants
    private const WIDTH = 'width';
    private const HEIGHT = 'height';

    /**
     * Master definition of all image sizes by aspect ratio.
     * Single source of truth.
     */
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

    /**
     * Get all sizes for a specific aspect ratio.
     *
     * @throws InvalidAspectRatioException
     */
    public static function getSizesForRatio(string $ratio): array
    {
        if (!isset(self::SIZES[$ratio])) {
            throw InvalidAspectRatioException::create($ratio, self::getSupportedRatios());
        }

        return self::SIZES[$ratio];
    }

    /**
     * Get a specific size for a ratio.
     */
    public static function getSize(string $ratio, string $sizeKey): ?array
    {
        return self::SIZES[$ratio][$sizeKey] ?? null;
    }

    /**
     * Get all supported aspect ratios.
     */
    public static function getSupportedRatios(): array
    {
        return array_keys(self::SIZES);
    }

    /**
     * Check if a ratio is supported.
     */
    public static function isRatioSupported(string $ratio): bool
    {
        return isset(self::SIZES[$ratio]);
    }

    /**
     * Get all sizes across all ratios (flattened).
     */
    public static function getAllSizes(): array
    {
        return self::SIZES;
    }

    /**
     * Get the default aspect ratio.
     */
    public static function getDefaultRatio(): string
    {
        return self::ASPECT_RATIO_4_3;
    }
}
```

**Uso:**
```php
// Antes (duplicado en 3 archivos)
private const SIZES_RELATIONS = [/* 200+ líneas */];

// Después (centralizado)
$sizes = ImageSizesRegistry::getSizesForRatio('16:9');
$size = ImageSizesRegistry::getSize('4:3', '1440w');
```

---

## 3. URLGenerationService

**Ubicación:** `src/Infrastructure/Service/URLGenerationService.php`

**Propósito:** Centraliza generación de URLs (elimina duplicación en transformers).

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

/**
 * Centralized URL generation service.
 * Replaces duplicated URL generation logic across transformers.
 */
final readonly class URLGenerationService implements URLGenerationServiceInterface
{
    public function __construct(
        private string $extension,
    ) {}

    public function generateEditorialUrl(array $editorial, array $section): string
    {
        $subdomain = $section['isSubdomainBlog'] ?? false ? 'blog' : 'www';
        $siteId = $section['siteId'] ?? '';
        $urlPath = $editorial['urlPath'] ?? $editorial['path'] ?? '';

        return sprintf(
            'https://%s.%s.%s/%s',
            $subdomain,
            $siteId,
            $this->extension,
            ltrim($urlPath, '/')
        );
    }

    public function generateSectionUrl(array $section): string
    {
        $subdomain = $section['isSubdomainBlog'] ?? false ? 'blog' : 'www';
        $siteId = $section['siteId'] ?? '';
        $urlPath = $section['urlPath'] ?? $section['path'] ?? '';

        return sprintf(
            'https://%s.%s.%s/%s',
            $subdomain,
            $siteId,
            $this->extension,
            ltrim($urlPath, '/')
        );
    }

    public function generateTagUrl(array $tag, array $section): string
    {
        $subdomain = $section['isSubdomainBlog'] ?? false ? 'blog' : 'www';
        $siteId = $section['siteId'] ?? '';
        $tagPath = $tag['urlPath'] ?? $tag['path'] ?? '';

        return sprintf(
            'https://%s.%s.%s/tag/%s',
            $subdomain,
            $siteId,
            $this->extension,
            ltrim($tagPath, '/')
        );
    }

    public function generateJournalistUrl(array $journalist, array $section): string
    {
        $subdomain = 'www';
        $siteId = $section['siteId'] ?? '';
        $alias = $journalist['alias'] ?? $journalist['urlAlias'] ?? '';

        return sprintf(
            'https://%s.%s.%s/autor/%s',
            $subdomain,
            $siteId,
            $this->extension,
            ltrim($alias, '/')
        );
    }

    public function generateMultimediaUrl(string $path): string
    {
        return sprintf('https://media.%s/%s', $this->extension, ltrim($path, '/'));
    }
}
```

---

## 4. Exception Hierarchy

### Base Exception

**Ubicación:** `src/Domain/Exception/SnaApiException.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

abstract class SnaApiException extends \Exception
{
    protected array $context = [];

    public function getContext(): array
    {
        return $this->context;
    }

    public function withContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }
}
```

### Resource Not Found

```php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class EditorialNotFoundException extends SnaApiException
{
    public static function withId(string $id): self
    {
        $exception = new self(sprintf('Editorial with ID "%s" not found', $id));
        $exception->context = ['editorial_id' => $id];
        return $exception;
    }
}
```

### Invalid Aspect Ratio

```php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class InvalidAspectRatioException extends SnaApiException
{
    public static function create(string $ratio, array $supportedRatios): self
    {
        $exception = new self(sprintf(
            'Invalid aspect ratio "%s". Supported ratios: %s',
            $ratio,
            implode(', ', $supportedRatios)
        ));
        $exception->context = [
            'provided_ratio' => $ratio,
            'supported_ratios' => $supportedRatios,
        ];
        return $exception;
    }
}
```

### Service Unavailable

```php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class ServiceUnavailableException extends SnaApiException
{
    public static function forService(string $serviceName, ?\Throwable $previous = null): self
    {
        $exception = new self(
            sprintf('Service "%s" is currently unavailable', $serviceName),
            0,
            $previous
        );
        $exception->context = ['service_name' => $serviceName];
        return $exception;
    }
}
```

---

## 5. Resolver Classes

### MultimediaResolver

**Ubicación:** `src/Orchestrator/Resolver/MultimediaResolver.php`

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Multimedia\Client\QueryMultimediaClient;
use Psr\Log\LoggerInterface;

final readonly class MultimediaResolver implements DataResolverInterface
{
    public function __construct(
        private QueryMultimediaClient $multimediaClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $multimediaIds = $this->extractMultimediaIds($editorial);

        foreach ($multimediaIds as $key => $id) {
            try {
                $multimedia = $this->multimediaClient->findById($id);
                $context->withMultimedia($key, $multimedia);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve multimedia', [
                    'multimedia_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 90;
    }

    private function extractMultimediaIds(array $editorial): array
    {
        $ids = [];

        if (isset($editorial['multimediaId'])) {
            $ids['main'] = $editorial['multimediaId'];
        }

        if (isset($editorial['openingMultimediaId'])) {
            $ids['opening'] = $editorial['openingMultimediaId'];
        }

        return $ids;
    }
}
```

### JournalistResolver

**Ubicación:** `src/Orchestrator/Resolver/JournalistResolver.php`

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Journalist\Client\QueryJournalistClient;
use Ec\Journalist\Factory\JournalistFactory;
use Psr\Log\LoggerInterface;

final readonly class JournalistResolver implements DataResolverInterface
{
    public function __construct(
        private QueryJournalistClient $journalistClient,
        private JournalistFactory $journalistFactory,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $journalistIds = $editorial['journalistIds'] ?? [];
        $journalists = [];

        foreach ($journalistIds as $id) {
            try {
                $journalist = $this->journalistClient->findById($id);
                $journalists[] = $this->journalistFactory->create($journalist);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve journalist', [
                    'journalist_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $context->withJournalists($journalists);
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 80;
    }
}
```

### TagResolver

**Ubicación:** `src/Orchestrator/Resolver/TagResolver.php`

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Tag\Client\QueryTagClient;
use Psr\Log\LoggerInterface;

final readonly class TagResolver implements DataResolverInterface
{
    public function __construct(
        private QueryTagClient $tagClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $tagIds = $editorial['tagIds'] ?? [];
        $tags = [];

        foreach ($tagIds as $id) {
            try {
                $tag = $this->tagClient->findById($id);
                $tags[] = $tag;
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve tag', [
                    'tag_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $context->withTags($tags);
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 70;
    }
}
```

---

## 6. EditorialOrchestratorFacade

**Ubicación:** `src/Orchestrator/Chain/EditorialOrchestratorFacade.php`

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain;

use App\Application\Response\ResponseBuilderInterface;
use App\Orchestrator\Context\OrchestrationContextFactory;
use App\Orchestrator\Resolver\ResolverRegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Facade for editorial orchestration.
 * Coordinates resolvers and builds the final response.
 *
 * This class replaces the monolithic EditorialOrchestrator (536 lines)
 * with a clean facade pattern (~80 lines).
 */
final readonly class EditorialOrchestratorFacade implements EditorialOrchestratorInterface
{
    public function __construct(
        private ResolverRegistryInterface $resolverRegistry,
        private OrchestrationContextFactory $contextFactory,
        private ResponseBuilderInterface $responseBuilder,
        private LoggerInterface $logger,
    ) {}

    public function execute(Request $request): array
    {
        $context = $this->contextFactory->createFromRequest($request);

        $resolvers = $this->resolverRegistry->getResolversFor($context);

        foreach ($resolvers as $resolver) {
            if (!$resolver->supports($context)) {
                continue;
            }

            try {
                $resolver->resolve($context);
            } catch (\Throwable $e) {
                $this->logger->error('Resolver failed', [
                    'resolver' => $resolver::class,
                    'error' => $e->getMessage(),
                    'context' => [
                        'editorial_id' => $context->getEditorialId(),
                        'site_id' => $context->getSiteId(),
                    ],
                ]);
                // Decide: continue or throw based on resolver criticality
            }
        }

        return $this->responseBuilder->build($context);
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }
}
```

---

## Resumen de Cambios de Modelo

| Componente | Líneas Antes | Líneas Después | Reducción |
|------------|--------------|----------------|-----------|
| EditorialOrchestrator | 536 | ~80 (Facade) | -85% |
| SIZES_RELATIONS (total) | ~400 (3x) | ~100 (1x) | -75% |
| URL generation | ~90 (disperso) | ~60 (centralizado) | -33% |
| Exception handling | Genérico | Tipado | +Calidad |

---

**Documento creado por:** Planner/Architect
**Fecha:** 2026-01-28
**Próximo paso:** 30_tasks_backend.md
