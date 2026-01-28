# Tareas Backend - REFACTOR-001

> **Proyecto:** SNAAPI Refactoring
> **Fecha:** 2026-01-28
> **Total estimado:** 19 tareas, ~8-10 días

---

## Resumen Ejecutivo

| Sprint | Tareas | Estimación | Foco |
|--------|--------|------------|------|
| Sprint 1 | BE-001 a BE-008 | 4-5 días | ImageSizesRegistry + Resolvers base |
| Sprint 2 | BE-009 a BE-015 | 3-4 días | Facade + URLService |
| Sprint 3 | BE-016 a BE-019 | 1-2 días | Exceptions + Cleanup |

---

## Sprint 1: Fundamentos

### BE-001: Crear ImageSizesRegistry

**Descripción:** Centralizar todas las constantes de tamaños de imagen en un único Registry.

**Archivos a crear:**
- `src/Infrastructure/Registry/ImageSizesRegistry.php`
- `tests/Infrastructure/Registry/ImageSizesRegistryTest.php`

**Archivos a modificar:**
- (Ninguno todavía - solo creación)

**Implementación:**

```php
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
```

**Test:**
```php
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

    private function parseRatio(string $ratio): float
    {
        [$width, $height] = explode(':', $ratio);
        return (float) $width / (float) $height;
    }
}
```

**Acceptance Criteria:**
- [ ] ImageSizesRegistry creado con todos los aspect ratios
- [ ] Método getSizesForRatio() funciona correctamente
- [ ] Método getSize() funciona correctamente
- [ ] Excepción InvalidAspectRatioException lanzada para ratios inválidos
- [ ] Tests unitarios completos (10+ tests)
- [ ] PHPStan level 9 pasa

**Verificación:**
```bash
# Crear archivos
php bin/phpunit tests/Infrastructure/Registry/ImageSizesRegistryTest.php

# Verificar PHPStan
vendor/bin/phpstan analyse src/Infrastructure/Registry/

# Expected: 0 errors, all tests passing
```

**Estimación:** Complejidad S, 2-3 horas

**Dependencias:** Ninguna (primera tarea)

---

### BE-002: Crear Exception Hierarchy

**Descripción:** Crear jerarquía de excepciones tipadas para reemplazar `\Throwable` genérico.

**Archivos a crear:**
- `src/Domain/Exception/SnaApiException.php`
- `src/Domain/Exception/ResourceNotFoundException.php`
- `src/Domain/Exception/EditorialNotFoundException.php`
- `src/Domain/Exception/InvalidAspectRatioException.php`
- `src/Domain/Exception/ServiceUnavailableException.php`
- `tests/Domain/Exception/SnaApiExceptionTest.php`

**Implementación:**

```php
// src/Domain/Exception/SnaApiException.php
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

    public function toArray(): array
    {
        return [
            'error' => static::class,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ];
    }
}
```

```php
// src/Domain/Exception/InvalidAspectRatioException.php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidAspectRatioException extends SnaApiException
{
    public static function create(string $ratio, array $supportedRatios): self
    {
        $exception = new self(sprintf(
            'Invalid aspect ratio "%s". Supported: %s',
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

```php
// src/Domain/Exception/EditorialNotFoundException.php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class EditorialNotFoundException extends SnaApiException
{
    public static function withId(string $id): self
    {
        $exception = new self(sprintf('Editorial with ID "%s" not found', $id));
        $exception->context = ['editorial_id' => $id];
        return $exception;
    }
}
```

```php
// src/Domain/Exception/ServiceUnavailableException.php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ServiceUnavailableException extends SnaApiException
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

**Acceptance Criteria:**
- [ ] SnaApiException base creada
- [ ] InvalidAspectRatioException creada
- [ ] EditorialNotFoundException creada
- [ ] ServiceUnavailableException creada
- [ ] Todas las excepciones tienen método factory estático
- [ ] Context array funciona correctamente
- [ ] Tests unitarios para cada excepción

**Verificación:**
```bash
php bin/phpunit tests/Domain/Exception/
vendor/bin/phpstan analyse src/Domain/Exception/
```

**Estimación:** Complejidad S, 2 horas

**Dependencias:** Ninguna

---

### BE-003: Crear DataResolverInterface

**Descripción:** Crear interface base para todos los resolvers.

**Archivos a crear:**
- `src/Orchestrator/Resolver/Interface/DataResolverInterface.php`
- `src/Orchestrator/Resolver/Interface/ResolverRegistryInterface.php`

**Implementación:**

```php
// src/Orchestrator/Resolver/Interface/DataResolverInterface.php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver\Interface;

use App\Orchestrator\Context\OrchestrationContext;

interface DataResolverInterface
{
    /**
     * Resolve data and populate the orchestration context.
     */
    public function resolve(OrchestrationContext $context): void;

    /**
     * Check if this resolver supports the given context.
     */
    public function supports(OrchestrationContext $context): bool;

    /**
     * Get resolver priority (higher = executed first).
     * Default priorities:
     * - Section: 100
     * - Multimedia: 90
     * - Journalist: 80
     * - Tag: 70
     * - InsertedNews: 60
     * - RecommendedNews: 50
     */
    public function getPriority(): int;
}
```

```php
// src/Orchestrator/Resolver/Interface/ResolverRegistryInterface.php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver\Interface;

use App\Orchestrator\Context\OrchestrationContext;

interface ResolverRegistryInterface
{
    public function addResolver(DataResolverInterface $resolver): void;

    /**
     * @return DataResolverInterface[]
     */
    public function getResolvers(): array;

    /**
     * @return DataResolverInterface[]
     */
    public function getResolversFor(OrchestrationContext $context): array;
}
```

**Acceptance Criteria:**
- [ ] DataResolverInterface creada con 3 métodos
- [ ] ResolverRegistryInterface creada
- [ ] PHPDoc completo
- [ ] PHPStan level 9 pasa

**Verificación:**
```bash
vendor/bin/phpstan analyse src/Orchestrator/Resolver/Interface/
```

**Estimación:** Complejidad S, 1 hora

**Dependencias:** Ninguna

---

### BE-004: Crear OrchestrationContext

**Descripción:** Crear clase de contexto que fluye a través de todos los resolvers.

**Archivos a crear:**
- `src/Orchestrator/Context/OrchestrationContext.php`
- `src/Orchestrator/Context/OrchestrationContextFactory.php`
- `tests/Orchestrator/Context/OrchestrationContextTest.php`

**Implementación:**

```php
// src/Orchestrator/Context/OrchestrationContext.php
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

    public function setJournalists(array $journalists): void
    {
        $this->journalists = $journalists;
    }

    public function getJournalists(): array
    {
        return $this->journalists;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }

    public function addInsertedNews(array $news): void
    {
        $this->insertedNews[] = $news;
    }

    public function getInsertedNews(): array
    {
        return $this->insertedNews;
    }

    public function addRecommendedNews(array $news): void
    {
        $this->recommendedNews[] = $news;
    }

    public function getRecommendedNews(): array
    {
        return $this->recommendedNews;
    }
}
```

```php
// src/Orchestrator/Context/OrchestrationContextFactory.php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Context;

use Symfony\Component\HttpFoundation\Request;

final readonly class OrchestrationContextFactory
{
    public function __construct(
        private string $extension,
    ) {}

    public function createFromRequest(Request $request): OrchestrationContext
    {
        $editorialId = $request->attributes->get('id', '');
        $siteId = $request->attributes->get('siteId', '');

        return new OrchestrationContext(
            editorialId: $editorialId,
            siteId: $siteId,
            extension: $this->extension,
        );
    }

    public function create(string $editorialId, string $siteId): OrchestrationContext
    {
        return new OrchestrationContext(
            editorialId: $editorialId,
            siteId: $siteId,
            extension: $this->extension,
        );
    }
}
```

**Acceptance Criteria:**
- [ ] OrchestrationContext creado con todos los campos
- [ ] OrchestrationContextFactory creado
- [ ] Getters/setters funcionan correctamente
- [ ] hasEditorial(), hasSection() funcionan
- [ ] Tests unitarios completos

**Verificación:**
```bash
php bin/phpunit tests/Orchestrator/Context/
vendor/bin/phpstan analyse src/Orchestrator/Context/
```

**Estimación:** Complejidad S, 2 horas

**Dependencias:** Ninguna

---

### BE-005: Crear ResolverRegistry

**Descripción:** Implementar registry que administra los resolvers.

**Archivos a crear:**
- `src/Orchestrator/Resolver/ResolverRegistry.php`
- `tests/Orchestrator/Resolver/ResolverRegistryTest.php`

**Implementación:**

```php
// src/Orchestrator/Resolver/ResolverRegistry.php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use App\Orchestrator\Resolver\Interface\ResolverRegistryInterface;

final class ResolverRegistry implements ResolverRegistryInterface
{
    /** @var DataResolverInterface[] */
    private array $resolvers = [];

    private bool $sorted = false;

    public function addResolver(DataResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
        $this->sorted = false;
    }

    public function getResolvers(): array
    {
        $this->sortResolvers();
        return $this->resolvers;
    }

    public function getResolversFor(OrchestrationContext $context): array
    {
        $this->sortResolvers();

        return array_filter(
            $this->resolvers,
            static fn(DataResolverInterface $resolver) => $resolver->supports($context)
        );
    }

    private function sortResolvers(): void
    {
        if ($this->sorted) {
            return;
        }

        usort(
            $this->resolvers,
            static fn(DataResolverInterface $a, DataResolverInterface $b) => $b->getPriority() <=> $a->getPriority()
        );

        $this->sorted = true;
    }
}
```

**Test:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use App\Orchestrator\Resolver\ResolverRegistry;
use PHPUnit\Framework\TestCase;

final class ResolverRegistryTest extends TestCase
{
    public function testAddAndGetResolvers(): void
    {
        $registry = new ResolverRegistry();
        $resolver = $this->createMock(DataResolverInterface::class);
        $resolver->method('getPriority')->willReturn(50);

        $registry->addResolver($resolver);

        $this->assertCount(1, $registry->getResolvers());
    }

    public function testResolversAreSortedByPriority(): void
    {
        $registry = new ResolverRegistry();

        $lowPriority = $this->createMock(DataResolverInterface::class);
        $lowPriority->method('getPriority')->willReturn(10);

        $highPriority = $this->createMock(DataResolverInterface::class);
        $highPriority->method('getPriority')->willReturn(100);

        $medPriority = $this->createMock(DataResolverInterface::class);
        $medPriority->method('getPriority')->willReturn(50);

        $registry->addResolver($lowPriority);
        $registry->addResolver($highPriority);
        $registry->addResolver($medPriority);

        $resolvers = $registry->getResolvers();

        $this->assertSame($highPriority, $resolvers[0]);
        $this->assertSame($medPriority, $resolvers[1]);
        $this->assertSame($lowPriority, $resolvers[2]);
    }

    public function testGetResolversForFiltersUnsupported(): void
    {
        $registry = new ResolverRegistry();
        $context = new OrchestrationContext('id', 'site', 'com');

        $supported = $this->createMock(DataResolverInterface::class);
        $supported->method('getPriority')->willReturn(50);
        $supported->method('supports')->willReturn(true);

        $unsupported = $this->createMock(DataResolverInterface::class);
        $unsupported->method('getPriority')->willReturn(50);
        $unsupported->method('supports')->willReturn(false);

        $registry->addResolver($supported);
        $registry->addResolver($unsupported);

        $resolvers = $registry->getResolversFor($context);

        $this->assertCount(1, $resolvers);
    }
}
```

**Acceptance Criteria:**
- [ ] ResolverRegistry implementa ResolverRegistryInterface
- [ ] Resolvers se ordenan por prioridad (descendente)
- [ ] getResolversFor() filtra por supports()
- [ ] Tests unitarios completos

**Verificación:**
```bash
php bin/phpunit tests/Orchestrator/Resolver/ResolverRegistryTest.php
vendor/bin/phpstan analyse src/Orchestrator/Resolver/ResolverRegistry.php
```

**Estimación:** Complejidad S, 2 horas

**Dependencias:** BE-003, BE-004

---

### BE-006: Crear SectionResolver

**Descripción:** Primer resolver concreto - resuelve datos de sección.

**Archivos a crear:**
- `src/Orchestrator/Resolver/SectionResolver.php`
- `tests/Orchestrator/Resolver/SectionResolverTest.php`

**Implementación:**

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver;

use App\Orchestrator\Context\OrchestrationContext;
use App\Orchestrator\Resolver\Interface\DataResolverInterface;
use Ec\Section\Client\QuerySectionClient;
use Psr\Log\LoggerInterface;

final readonly class SectionResolver implements DataResolverInterface
{
    public function __construct(
        private QuerySectionClient $sectionClient,
        private LoggerInterface $logger,
    ) {}

    public function resolve(OrchestrationContext $context): void
    {
        $editorial = $context->getEditorial();
        if ($editorial === null) {
            return;
        }

        $sectionId = $editorial['sectionId'] ?? null;
        if ($sectionId === null) {
            $this->logger->warning('Editorial has no sectionId', [
                'editorial_id' => $context->getEditorialId(),
            ]);
            return;
        }

        try {
            $section = $this->sectionClient->findById($sectionId);
            $context->setSection($section);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to resolve section', [
                'section_id' => $sectionId,
                'editorial_id' => $context->getEditorialId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function supports(OrchestrationContext $context): bool
    {
        return $context->hasEditorial();
    }

    public function getPriority(): int
    {
        return 100; // Highest - section needed for URLs
    }
}
```

**Acceptance Criteria:**
- [ ] SectionResolver implementa DataResolverInterface
- [ ] Resuelve sección desde editorial
- [ ] Logging correcto en errores
- [ ] Prioridad 100 (máxima)
- [ ] Tests con mocks

**Verificación:**
```bash
php bin/phpunit tests/Orchestrator/Resolver/SectionResolverTest.php
```

**Estimación:** Complejidad S, 2 horas

**Dependencias:** BE-003, BE-004

---

### BE-007: Crear MultimediaResolver

**Descripción:** Resolver para multimedia (fotos, videos, widgets).

**Archivos a crear:**
- `src/Orchestrator/Resolver/MultimediaResolver.php`
- `tests/Orchestrator/Resolver/MultimediaResolverTest.php`

**Implementación:**

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
                $context->addMultimedia($key, $multimedia);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to resolve multimedia', [
                    'multimedia_id' => $id,
                    'key' => $key,
                    'editorial_id' => $context->getEditorialId(),
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

        if (!empty($editorial['multimediaId'])) {
            $ids['main'] = $editorial['multimediaId'];
        }

        if (!empty($editorial['openingMultimediaId'])) {
            $ids['opening'] = $editorial['openingMultimediaId'];
        }

        if (!empty($editorial['metaImageId'])) {
            $ids['meta'] = $editorial['metaImageId'];
        }

        return $ids;
    }
}
```

**Acceptance Criteria:**
- [ ] MultimediaResolver implementa DataResolverInterface
- [ ] Extrae multimedia IDs correctamente
- [ ] Maneja errores sin detener flujo
- [ ] Prioridad 90
- [ ] Tests con mocks para diferentes escenarios

**Verificación:**
```bash
php bin/phpunit tests/Orchestrator/Resolver/MultimediaResolverTest.php
```

**Estimación:** Complejidad M, 3 horas

**Dependencias:** BE-003, BE-004

---

### BE-008: Crear JournalistResolver

**Descripción:** Resolver para periodistas/autores.

**Archivos a crear:**
- `src/Orchestrator/Resolver/JournalistResolver.php`
- `tests/Orchestrator/Resolver/JournalistResolverTest.php`

**Implementación similar a MultimediaResolver pero con:**
- QueryJournalistClient
- JournalistFactory
- Prioridad 80

**Acceptance Criteria:**
- [ ] JournalistResolver implementa DataResolverInterface
- [ ] Usa JournalistFactory para crear objetos
- [ ] Prioridad 80
- [ ] Tests completos

**Estimación:** Complejidad S, 2 horas

**Dependencias:** BE-003, BE-004

---

## Sprint 2: Facade y Servicios

### BE-009: Crear TagResolver

**Descripción:** Resolver para etiquetas.

**Archivos a crear:**
- `src/Orchestrator/Resolver/TagResolver.php`
- `tests/Orchestrator/Resolver/TagResolverTest.php`

**Prioridad:** 70

**Estimación:** Complejidad S, 2 horas

**Dependencias:** BE-003, BE-004

---

### BE-010: Crear InsertedNewsResolver

**Descripción:** Resolver para noticias insertadas en el body.

**Archivos a crear:**
- `src/Orchestrator/Resolver/InsertedNewsResolver.php`
- `tests/Orchestrator/Resolver/InsertedNewsResolverTest.php`

**Prioridad:** 60

**Estimación:** Complejidad M, 3 horas

**Dependencias:** BE-003, BE-004

---

### BE-011: Crear RecommendedNewsResolver

**Descripción:** Resolver para noticias recomendadas.

**Archivos a crear:**
- `src/Orchestrator/Resolver/RecommendedNewsResolver.php`
- `tests/Orchestrator/Resolver/RecommendedNewsResolverTest.php`

**Prioridad:** 50

**Estimación:** Complejidad M, 3 horas

**Dependencias:** BE-003, BE-004

---

### BE-012: Crear URLGenerationService

**Descripción:** Centralizar generación de URLs.

**Archivos a crear:**
- `src/Infrastructure/Service/URLGenerationService.php`
- `src/Infrastructure/Service/URLGenerationServiceInterface.php`
- `tests/Infrastructure/Service/URLGenerationServiceTest.php`

**Implementación:**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

final readonly class URLGenerationService implements URLGenerationServiceInterface
{
    public function __construct(
        private string $extension,
    ) {}

    public function generateEditorialUrl(array $editorial, array $section): string
    {
        $subdomain = ($section['isSubdomainBlog'] ?? false) ? 'blog' : 'www';
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
        $subdomain = ($section['isSubdomainBlog'] ?? false) ? 'blog' : 'www';
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
        $subdomain = ($section['isSubdomainBlog'] ?? false) ? 'blog' : 'www';
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
        $siteId = $section['siteId'] ?? '';
        $alias = $journalist['alias'] ?? $journalist['urlAlias'] ?? '';

        return sprintf(
            'https://www.%s.%s/autor/%s',
            $siteId,
            $this->extension,
            ltrim($alias, '/')
        );
    }
}
```

**Acceptance Criteria:**
- [ ] URLGenerationService creado
- [ ] Todos los métodos de generación de URL
- [ ] Maneja casos edge (subdomain blog, paths vacíos)
- [ ] Tests unitarios completos

**Estimación:** Complejidad M, 3 horas

**Dependencias:** Ninguna

---

### BE-013: Crear ResponseBuilder

**Descripción:** Builder para construir respuesta JSON final.

**Archivos a crear:**
- `src/Application/Response/ResponseBuilderInterface.php`
- `src/Application/Response/EditorialResponseBuilder.php`
- `tests/Application/Response/EditorialResponseBuilderTest.php`

**Estimación:** Complejidad M, 4 horas

**Dependencias:** BE-004

---

### BE-014: Crear ResolverCompilerPass

**Descripción:** Compiler pass para registrar resolvers automáticamente.

**Archivos a crear:**
- `src/DependencyInjection/Compiler/ResolverCompilerPass.php`
- `tests/DependencyInjection/Compiler/ResolverCompilerPassTest.php`

**Implementación:**

```php
<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Orchestrator\Resolver\ResolverRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ResolverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ResolverRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(ResolverRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('app.data_resolver');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addResolver', [new Reference($id)]);
        }
    }
}
```

**Acceptance Criteria:**
- [ ] CompilerPass registra todos los servicios taggeados
- [ ] Tests verifican registro correcto

**Estimación:** Complejidad S, 2 horas

**Dependencias:** BE-005

---

### BE-015: Crear EditorialOrchestratorFacade

**Descripción:** La pieza central - Facade que reemplaza el God Class.

**Archivos a crear:**
- `src/Orchestrator/Chain/EditorialOrchestratorFacade.php`
- `tests/Orchestrator/Chain/EditorialOrchestratorFacadeTest.php`

**Implementación:**

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain;

use App\Application\Response\ResponseBuilderInterface;
use App\Orchestrator\Context\OrchestrationContextFactory;
use App\Orchestrator\Resolver\Interface\ResolverRegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

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

        foreach ($this->resolverRegistry->getResolversFor($context) as $resolver) {
            try {
                $resolver->resolve($context);
            } catch (\Throwable $e) {
                $this->logger->error('Resolver failed', [
                    'resolver' => $resolver::class,
                    'editorial_id' => $context->getEditorialId(),
                    'error' => $e->getMessage(),
                ]);
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

**Acceptance Criteria:**
- [ ] EditorialOrchestratorFacade creado
- [ ] Solo 4 dependencias (vs 19 originales)
- [ ] < 50 líneas de código
- [ ] Tests de integración completos
- [ ] Feature flag para migración gradual

**Verificación:**
```bash
php bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorFacadeTest.php
wc -l src/Orchestrator/Chain/EditorialOrchestratorFacade.php
# Expected: < 50 lines
```

**Estimación:** Complejidad L, 6 horas

**Dependencias:** BE-005, BE-006 a BE-011, BE-013, BE-014

---

## Sprint 3: Migración y Cleanup

### BE-016: Refactorizar DetailsMultimediaPhotoDataTransformer

**Descripción:** Usar ImageSizesRegistry en lugar de constante local.

**Archivos a modificar:**
- `src/Application/DataTransformer/Apps/Media/DataTransformers/DetailsMultimediaPhotoDataTransformer.php`

**Cambios:**
1. Eliminar constante SIZES_RELATIONS (237 líneas)
2. Importar y usar ImageSizesRegistry
3. Actualizar tests

**Estimación:** Complejidad M, 3 horas

**Dependencias:** BE-001

---

### BE-017: Refactorizar PictureShots

**Descripción:** Usar ImageSizesRegistry en lugar de constante local.

**Archivos a modificar:**
- `src/Infrastructure/Service/PictureShots.php`

**Estimación:** Complejidad S, 2 horas

**Dependencias:** BE-001

---

### BE-018: Refactorizar DetailsAppsDataTransformer

**Descripción:** Usar URLGenerationService en lugar de lógica inline.

**Archivos a modificar:**
- `src/Application/DataTransformer/Apps/DetailsAppsDataTransformer.php`

**Estimación:** Complejidad M, 3 horas

**Dependencias:** BE-012

---

### BE-019: Migrar a EditorialOrchestratorFacade

**Descripción:** Habilitar feature flag y migrar tráfico gradualmente.

**Tareas:**
1. Configurar feature flag
2. A/B test 1% tráfico
3. Monitorear métricas
4. Incrementar gradualmente a 100%
5. Deprecar EditorialOrchestrator legacy

**Estimación:** Complejidad L, 8 horas (incluye monitoreo)

**Dependencias:** BE-015

---

## Resumen de Estimaciones

| ID | Tarea | Complejidad | Horas | Sprint |
|----|-------|-------------|-------|--------|
| BE-001 | ImageSizesRegistry | S | 2-3 | 1 |
| BE-002 | Exception Hierarchy | S | 2 | 1 |
| BE-003 | DataResolverInterface | S | 1 | 1 |
| BE-004 | OrchestrationContext | S | 2 | 1 |
| BE-005 | ResolverRegistry | S | 2 | 1 |
| BE-006 | SectionResolver | S | 2 | 1 |
| BE-007 | MultimediaResolver | M | 3 | 1 |
| BE-008 | JournalistResolver | S | 2 | 1 |
| BE-009 | TagResolver | S | 2 | 2 |
| BE-010 | InsertedNewsResolver | M | 3 | 2 |
| BE-011 | RecommendedNewsResolver | M | 3 | 2 |
| BE-012 | URLGenerationService | M | 3 | 2 |
| BE-013 | ResponseBuilder | M | 4 | 2 |
| BE-014 | ResolverCompilerPass | S | 2 | 2 |
| BE-015 | EditorialOrchestratorFacade | L | 6 | 2 |
| BE-016 | Refactor Photo Transformer | M | 3 | 3 |
| BE-017 | Refactor PictureShots | S | 2 | 3 |
| BE-018 | Refactor DetailsApps | M | 3 | 3 |
| BE-019 | Migración Final | L | 8 | 3 |

**Total: 19 tareas, ~55-60 horas (~8-10 días)**

---

**Documento creado por:** Planner/Architect
**Fecha:** 2026-01-28
**Versión:** 1.0
