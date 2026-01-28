# Arquitectura de Refactorización - REFACTOR-001

> **Proyecto:** SNAAPI Refactoring
> **Fecha:** 2026-01-28
> **Autor:** Planner/Architect

---

## Visión General

### Arquitectura Actual (Problemática)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        EditorialOrchestrator                        │
│                         (GOD CLASS - 536 líneas)                    │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    19 DEPENDENCIAS                           │   │
│  │  QueryLegacyClient, QueryEditorialClient, QuerySectionClient │   │
│  │  QueryMultimediaClient, AppsDataTransformer, QueryTagClient  │   │
│  │  BodyDataTransformer, UriFactoryInterface, QueryMembership   │   │
│  │  LoggerInterface, JournalistsDataTransformer, QueryJournalist│   │
│  │  JournalistFactory, MultimediaDataTransformer, Standfirst... │   │
│  │  RecommendedEditorialsDataTransformer, QueryMultimediaOpening│   │
│  │  MediaDataTransformerHandler, MultimediaOrchestratorHandler  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  execute() ─────────────────────────────────────────────────────    │
│  │  180 líneas de código                                            │
│  │  Múltiples responsabilidades mezcladas                           │
│  │  Loops anidados complejos                                        │
│  │  Try-catch genéricos                                             │
│  └──────────────────────────────────────────────────────────────    │
└─────────────────────────────────────────────────────────────────────┘
```

### Arquitectura Objetivo (Clean)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    EditorialOrchestratorFacade                      │
│                    (FACADE - ~100 líneas, 5 deps)                   │
│                                                                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ ResolverRegistry │  │ TransformerChain │  │ ResponseBuilder  │  │
│  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘  │
│           │                     │                     │             │
└───────────┼─────────────────────┼─────────────────────┼─────────────┘
            │                     │                     │
            ▼                     ▼                     ▼
┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐
│ MultimediaResolver│  │BodyDataTransformer│  │ EditorialResponse │
│ JournalistResolver│  │ AppsDataTransformer│  │     (DTO)         │
│ TagResolver       │  │MultimediaTransform │  │                   │
│ InsertedResolver  │  │                    │  │                   │
│ RecommendedResolv │  │                    │  │                   │
└───────────────────┘  └───────────────────┘  └───────────────────┘
```

---

## Patrones de Diseño a Aplicar

### 1. Facade Pattern (EditorialOrchestrator)

**Problema:** Clase monolítica con 19 dependencias y múltiples responsabilidades.

**Solución:** Crear una fachada que orqueste componentes especializados.

```php
// ANTES (19 deps)
class EditorialOrchestrator {
    public function __construct(
        QueryLegacyClient $queryLegacyClient,
        QueryEditorialClient $queryEditorialClient,
        // ... 17 más ...
    ) {}
}

// DESPUÉS (5 deps)
class EditorialOrchestratorFacade implements EditorialOrchestratorInterface {
    public function __construct(
        private readonly ResolverRegistryInterface $resolverRegistry,
        private readonly DataTransformerChainInterface $transformerChain,
        private readonly ResponseBuilderInterface $responseBuilder,
        private readonly OrchestrationContextFactory $contextFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(Request $request): array
    {
        $context = $this->contextFactory->create($request);

        foreach ($this->resolverRegistry->getResolvers() as $resolver) {
            $resolver->resolve($context);
        }

        $transformedData = $this->transformerChain->transform($context);

        return $this->responseBuilder->build($transformedData);
    }
}
```

### 2. Registry Pattern (Resolvers)

**Problema:** Lógica de resolución dispersa en un solo método gigante.

**Solución:** Registry que administra resolvers especializados.

```php
interface DataResolverInterface {
    public function resolve(OrchestrationContext $context): void;
    public function supports(OrchestrationContext $context): bool;
    public function getPriority(): int;
}

class ResolverRegistry implements ResolverRegistryInterface {
    /** @var DataResolverInterface[] */
    private array $resolvers = [];

    public function addResolver(DataResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
        usort($this->resolvers, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
    }

    public function getResolvers(): array
    {
        return $this->resolvers;
    }
}
```

**CompilerPass para registro dinámico:**
```php
class ResolverCompilerPass implements CompilerPassInterface {
    public function process(ContainerBuilder $container): void
    {
        $registry = $container->findDefinition(ResolverRegistry::class);
        $resolvers = $container->findTaggedServiceIds('app.data_resolver');

        foreach ($resolvers as $id => $tags) {
            $registry->addMethodCall('addResolver', [new Reference($id)]);
        }
    }
}
```

### 3. Strategy Pattern (Ya existente - Mejorar)

**Actual:** DataTransformers usan Strategy correctamente.

**Mejora:** Agregar interface base y mejorar typing.

```php
interface DataTransformerInterface {
    public function transform(mixed $data, TransformationContext $context): mixed;
    public function supports(string $type): bool;
}

// Mantener estructura actual pero con interface explícita
class BodyTagPictureDataTransformer implements DataTransformerInterface {
    public function transform(mixed $data, TransformationContext $context): array
    {
        // Implementación existente
    }

    public function supports(string $type): bool
    {
        return $type === 'picture';
    }
}
```

### 4. Singleton/Registry Pattern (ImageSizes)

**Problema:** SIZES_RELATIONS duplicado en 3 lugares.

**Solución:** Centralizar en un Registry singleton.

```php
final class ImageSizesRegistry {
    public const ASPECT_RATIO_4_3 = '4:3';
    public const ASPECT_RATIO_16_9 = '16:9';
    public const ASPECT_RATIO_1_1 = '1:1';
    public const ASPECT_RATIO_3_4 = '3:4';
    public const ASPECT_RATIO_9_16 = '9:16';

    private const SIZES = [
        self::ASPECT_RATIO_4_3 => [
            '1440w' => ['width' => 1440, 'height' => 1080],
            '1200w' => ['width' => 1200, 'height' => 900],
            '1024w' => ['width' => 1024, 'height' => 768],
            '800w'  => ['width' => 800, 'height' => 600],
            '640w'  => ['width' => 640, 'height' => 480],
            '480w'  => ['width' => 480, 'height' => 360],
            '320w'  => ['width' => 320, 'height' => 240],
            '160w'  => ['width' => 160, 'height' => 120],
        ],
        self::ASPECT_RATIO_16_9 => [
            '1920w' => ['width' => 1920, 'height' => 1080],
            '1280w' => ['width' => 1280, 'height' => 720],
            '854w'  => ['width' => 854, 'height' => 480],
            '640w'  => ['width' => 640, 'height' => 360],
            '426w'  => ['width' => 426, 'height' => 240],
        ],
        // ... otros ratios
    ];

    public static function getSizesForRatio(string $ratio): array
    {
        return self::SIZES[$ratio] ?? throw new InvalidAspectRatioException($ratio);
    }

    public static function getAllSizes(): array
    {
        return self::SIZES;
    }

    public static function getSupportedRatios(): array
    {
        return array_keys(self::SIZES);
    }
}
```

### 5. Builder Pattern (Response Construction)

**Problema:** Construcción de respuesta compleja mezclada con lógica de negocio.

**Solución:** Builder dedicado para construcción de respuestas.

```php
class EditorialResponseBuilder implements ResponseBuilderInterface {
    public function build(OrchestrationContext $context): array
    {
        return (new EditorialResponseDTO())
            ->withEditorial($context->getEditorial())
            ->withSection($context->getSection())
            ->withMultimedia($context->getMultimedia())
            ->withJournalists($context->getJournalists())
            ->withTags($context->getTags())
            ->withBody($context->getBody())
            ->withRecommended($context->getRecommended())
            ->toArray();
    }
}
```

---

## Estructura de Directorios Objetivo

```
src/
├── Application/
│   ├── DataTransformer/           # Ya existe - mantener
│   │   ├── Interface/             # NUEVO: Interfaces
│   │   │   └── DataTransformerInterface.php
│   │   └── ...
│   └── Response/                  # NUEVO: Response builders
│       ├── EditorialResponseBuilder.php
│       └── EditorialResponseDTO.php
│
├── Domain/                        # NUEVO: Domain logic
│   ├── Exception/                 # Exception hierarchy
│   │   ├── SnaApiException.php
│   │   ├── EditorialNotFoundException.php
│   │   ├── ServiceUnavailableException.php
│   │   └── InvalidAspectRatioException.php
│   └── ValueObject/               # Value Objects
│       └── AspectRatio.php
│
├── Infrastructure/
│   ├── Registry/                  # NUEVO: Registries
│   │   └── ImageSizesRegistry.php
│   ├── Service/
│   │   ├── URLGenerationService.php    # NUEVO: Centraliza URLs
│   │   ├── PromiseResolver.php         # NUEVO: Promise handling
│   │   └── ...
│   └── ...
│
├── Orchestrator/
│   ├── Chain/
│   │   ├── EditorialOrchestratorFacade.php  # NUEVO: Facade
│   │   ├── EditorialOrchestrator.php        # LEGACY: Deprecar
│   │   └── ...
│   ├── Context/                    # NUEVO: Orchestration context
│   │   ├── OrchestrationContext.php
│   │   └── OrchestrationContextFactory.php
│   └── Resolver/                   # NUEVO: Specialized resolvers
│       ├── Interface/
│       │   └── DataResolverInterface.php
│       ├── MultimediaResolver.php
│       ├── JournalistResolver.php
│       ├── TagResolver.php
│       ├── InsertedNewsResolver.php
│       ├── RecommendedNewsResolver.php
│       └── ResolverRegistry.php
│
└── DependencyInjection/
    └── Compiler/
        └── ResolverCompilerPass.php  # NUEVO: Registra resolvers
```

---

## Interfaces Principales

### DataResolverInterface

```php
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
     */
    public function getPriority(): int;
}
```

### ResolverRegistryInterface

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Resolver\Interface;

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

### OrchestrationContextInterface

```php
<?php

declare(strict_types=1);

namespace App\Orchestrator\Context;

interface OrchestrationContextInterface
{
    public function getEditorialId(): string;
    public function getSiteId(): string;

    public function setEditorial(array $editorial): void;
    public function getEditorial(): ?array;

    public function setSection(array $section): void;
    public function getSection(): ?array;

    public function addMultimedia(string $key, array $multimedia): void;
    public function getMultimedia(): array;

    public function setJournalists(array $journalists): void;
    public function getJournalists(): array;

    public function setTags(array $tags): void;
    public function getTags(): array;

    public function setBody(array $body): void;
    public function getBody(): ?array;

    public function addInsertedNews(array $news): void;
    public function getInsertedNews(): array;

    public function addRecommendedNews(array $news): void;
    public function getRecommendedNews(): array;
}
```

### ResponseBuilderInterface

```php
<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Orchestrator\Context\OrchestrationContext;

interface ResponseBuilderInterface
{
    public function build(OrchestrationContext $context): array;
}
```

### URLGenerationServiceInterface

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

interface URLGenerationServiceInterface
{
    public function generateEditorialUrl(
        array $editorial,
        array $section
    ): string;

    public function generateSectionUrl(array $section): string;

    public function generateTagUrl(
        array $tag,
        array $section
    ): string;

    public function generateJournalistUrl(
        array $journalist,
        array $section
    ): string;
}
```

---

## Diagrama de Clases (Resolvers)

```
                    ┌─────────────────────────┐
                    │ DataResolverInterface   │
                    │ <<interface>>           │
                    ├─────────────────────────┤
                    │ +resolve(context): void │
                    │ +supports(ctx): bool    │
                    │ +getPriority(): int     │
                    └───────────┬─────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│MultimediaResolver│   │JournalistResolver│   │   TagResolver   │
├─────────────────┤   ├─────────────────┤   ├─────────────────┤
│-multimediaClient│   │-journalistClient│   │  -tagClient     │
│-logger          │   │-journalistFactory│  │  -logger        │
├─────────────────┤   ├─────────────────┤   ├─────────────────┤
│+resolve()       │   │+resolve()       │   │ +resolve()      │
│+supports()      │   │+supports()      │   │ +supports()     │
│+getPriority():90│   │+getPriority():80│   │ +getPriority():70│
└─────────────────┘   └─────────────────┘   └─────────────────┘

        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
┌─────────────────┐   ┌──────────────────┐  ┌─────────────────┐
│InsertedResolver │   │RecommendedResolver│  │ SectionResolver │
├─────────────────┤   ├──────────────────┤  ├─────────────────┤
│-editorialClient │   │-editorialClient  │  │ -sectionClient  │
├─────────────────┤   ├──────────────────┤  ├─────────────────┤
│+resolve()       │   │+resolve()        │  │ +resolve()      │
│+supports()      │   │+supports()       │  │ +supports()     │
│+getPriority():60│   │+getPriority():50 │  │ +getPriority():100│
└─────────────────┘   └──────────────────┘  └─────────────────┘
```

---

## Diagrama de Secuencia (Request Flow)

```
    Client              Facade              Registry            Resolvers           Builder
       │                   │                   │                    │                  │
       │  GET /editorial   │                   │                    │                  │
       │──────────────────>│                   │                    │                  │
       │                   │                   │                    │                  │
       │                   │  createContext()  │                    │                  │
       │                   │─────────────────> │                    │                  │
       │                   │   context         │                    │                  │
       │                   │<─────────────────-│                    │                  │
       │                   │                   │                    │                  │
       │                   │  getResolvers()   │                    │                  │
       │                   │──────────────────>│                    │                  │
       │                   │   resolvers[]     │                    │                  │
       │                   │<──────────────────│                    │                  │
       │                   │                   │                    │                  │
       │                   │                   │  resolve(context)  │                  │
       │                   │──────────────────────────────────────>│                  │
       │                   │                   │                    │                  │
       │                   │                   │ (for each resolver)│                  │
       │                   │──────────────────────────────────────>│                  │
       │                   │                   │                    │                  │
       │                   │                   │                    │  build(context) │
       │                   │────────────────────────────────────────────────────────>│
       │                   │                   │                    │      response   │
       │                   │<────────────────────────────────────────────────────────│
       │                   │                   │                    │                  │
       │     JSON response │                   │                    │                  │
       │<──────────────────│                   │                    │                  │
```

---

## Exception Hierarchy

```
                    ┌─────────────────────┐
                    │     \Exception      │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │   SnaApiException   │
                    │     (abstract)      │
                    └──────────┬──────────┘
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
        ▼                      ▼                      ▼
┌───────────────────┐ ┌───────────────────┐ ┌───────────────────┐
│ResourceNotFound   │ │ServiceException   │ │ ValidationException│
│   Exception       │ │   (abstract)      │ │   (abstract)       │
└───────┬───────────┘ └───────┬───────────┘ └───────┬───────────┘
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────────┐ ┌───────────────────┐ ┌───────────────────┐
│EditorialNotFound  │ │ServiceUnavailable │ │InvalidAspectRatio │
│SectionNotFound    │ │TimeoutException   │ │InvalidEditorialType│
│MultimediaNotFound │ │                   │ │                    │
└───────────────────┘ └───────────────────┘ └───────────────────┘
```

---

## Configuración de Servicios (YAML)

```yaml
# config/services.yaml

services:
    # Resolver Registry
    App\Orchestrator\Resolver\ResolverRegistry:
        tags: ['app.resolver_registry']

    # Individual Resolvers (auto-tagged)
    App\Orchestrator\Resolver\SectionResolver:
        tags:
            - { name: 'app.data_resolver', priority: 100 }

    App\Orchestrator\Resolver\MultimediaResolver:
        tags:
            - { name: 'app.data_resolver', priority: 90 }

    App\Orchestrator\Resolver\JournalistResolver:
        tags:
            - { name: 'app.data_resolver', priority: 80 }

    App\Orchestrator\Resolver\TagResolver:
        tags:
            - { name: 'app.data_resolver', priority: 70 }

    App\Orchestrator\Resolver\InsertedNewsResolver:
        tags:
            - { name: 'app.data_resolver', priority: 60 }

    App\Orchestrator\Resolver\RecommendedNewsResolver:
        tags:
            - { name: 'app.data_resolver', priority: 50 }

    # Facade
    App\Orchestrator\Chain\EditorialOrchestratorFacade:
        arguments:
            $resolverRegistry: '@App\Orchestrator\Resolver\ResolverRegistry'
            $transformerChain: '@App\Application\DataTransformer\BodyDataTransformer'
            $responseBuilder: '@App\Application\Response\EditorialResponseBuilder'

    # URL Service
    App\Infrastructure\Service\URLGenerationService:
        arguments:
            $extension: '%env(URL_EXTENSION)%'

    # Alias for backwards compatibility
    App\Orchestrator\Chain\EditorialOrchestratorInterface:
        alias: App\Orchestrator\Chain\EditorialOrchestratorFacade
```

---

## Feature Flags para Migración

```php
// config/packages/feature_flags.yaml
parameters:
    feature_flags:
        use_new_orchestrator: '%env(bool:FF_NEW_ORCHESTRATOR)%'
        use_image_sizes_registry: '%env(bool:FF_IMAGE_SIZES_REGISTRY)%'
        use_url_service: '%env(bool:FF_URL_SERVICE)%'
```

```php
// Uso en código
class EditorialController {
    public function show(
        string $id,
        EditorialOrchestratorInterface $newOrchestrator,
        EditorialOrchestrator $legacyOrchestrator,
        FeatureFlags $flags
    ): Response {
        $orchestrator = $flags->isEnabled('use_new_orchestrator')
            ? $newOrchestrator
            : $legacyOrchestrator;

        return $this->json($orchestrator->execute($request));
    }
}
```

---

## Métricas de Arquitectura Objetivo

| Métrica | Actual | Objetivo | Mejora |
|---------|--------|----------|--------|
| Dependencias EditorialOrchestrator | 19 | 5 | -74% |
| Líneas EditorialOrchestrator | 536 | ~100 | -81% |
| Complejidad ciclomática máx | 45 | <15 | -67% |
| Clases con >200 líneas | 6 | 0 | -100% |
| Interfaces definidas | 3 | 15+ | +400% |
| Duplicación código | 3 lugares | 1 lugar | -67% |

---

## Próximos Documentos

1. **15_data_model.md** - Estructura de clases detallada
2. **30_tasks_backend.md** - Tareas específicas para cada refactor
3. **32_tasks_qa.md** - Casos de test para validar

---

**Documento creado por:** Planner/Architect
**Fecha:** 2026-01-28
**Versión:** 1.0
