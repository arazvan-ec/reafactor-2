# Modelo de Datos - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Nota Importante

Este feature es un **refactoring de arquitectura** y **no modifica la base de datos**. SNAAPI es un API Gateway que no persiste datos localmente; toda la información proviene de microservicios externos.

Este documento define los **Value Objects, DTOs y estructuras de datos en memoria** que forman parte del nuevo sistema de agregadores.

---

## Value Objects del Dominio

### AggregatorContext

**Propósito**: Encapsula toda la información necesaria para ejecutar agregadores.

```php
namespace App\Domain\Aggregator\ValueObject;

/**
 * Immutable context for aggregator execution
 *
 * @property-read string $editorialId     - ID del editorial siendo procesado
 * @property-read string $editorialType   - Tipo de editorial (news, opinion, etc.)
 * @property-read array  $rawData         - Datos crudos del editorial
 * @property-read array  $resolvedData    - Datos ya resueltos por otros agregadores
 * @property-read array  $metadata        - Metadatos de la solicitud
 */
final readonly class AggregatorContext
```

**Estructura JSON de rawData:**
```json
{
  "id": "4433",
  "title": "Breaking News Title",
  "headline": "Subtitle here",
  "body": {
    "bodyElements": [
      {"type": "paragraph", "text": "..."},
      {"type": "bodyTagPicture", "multimediaId": "123"},
      {"type": "bodyTagVideo", "multimediaId": "456"}
    ]
  },
  "tags": ["tag-1", "tag-2", "tag-3"],
  "signatures": [
    {"journalistId": "j-001", "aliasId": "alias-001"}
  ],
  "multimedia": {
    "mainPhotoId": "photo-001",
    "galleryIds": ["photo-002", "photo-003"]
  },
  "section": {
    "id": "section-001",
    "siteId": "site-001"
  },
  "insertedNews": ["news-100", "news-101"],
  "recommendedNews": ["news-200", "news-201"]
}
```

**Estructura JSON de resolvedData (después de agregación):**
```json
{
  "tags": [
    {"id": "tag-1", "name": "Sports", "url": "/tags/sports"},
    {"id": "tag-2", "name": "Football", "url": "/tags/football"}
  ],
  "multimedia": {
    "photo-001": {
      "id": "photo-001",
      "type": "photo",
      "url": "https://...",
      "shots": {...}
    }
  },
  "journalists": [
    {"id": "j-001", "name": "John Doe", "avatar": "https://..."}
  ],
  "section": {
    "id": "section-001",
    "name": "Sports",
    "url": "/sports"
  }
}
```

**Estructura JSON de metadata:**
```json
{
  "requestId": "req-uuid-123",
  "requestedAt": "2026-01-28T10:30:00Z",
  "clientIp": "192.168.1.1",
  "userAgent": "Mozilla/5.0...",
  "siteId": "site-001",
  "locale": "es_ES"
}
```

---

### AggregatorResult

**Propósito**: Encapsula el resultado de la ejecución de un agregador.

```php
namespace App\Domain\Aggregator\ValueObject;

/**
 * Result of aggregator execution
 *
 * @property-read string  $aggregatorName  - Nombre del agregador
 * @property-read mixed   $data            - Datos resultantes
 * @property-read bool    $success         - Si la ejecución fue exitosa
 * @property-read ?string $error           - Mensaje de error si falló
 * @property-read float   $executionTime   - Tiempo de ejecución en segundos
 */
final readonly class AggregatorResult
```

**Ejemplo de Result exitoso:**
```json
{
  "aggregatorName": "tag",
  "data": [
    {"id": "tag-1", "name": "Sports", "url": "/tags/sports"},
    {"id": "tag-2", "name": "Football", "url": "/tags/football"}
  ],
  "success": true,
  "error": null,
  "executionTime": 0.045
}
```

**Ejemplo de Result con error:**
```json
{
  "aggregatorName": "multimedia",
  "data": [],
  "success": false,
  "error": "HTTP 503: Service temporarily unavailable",
  "executionTime": 5.001
}
```

---

### TransformationContext

**Propósito**: Contexto para transformadores JSON con acceso a todos los resultados.

```php
namespace App\Domain\Transformer\ValueObject;

/**
 * Context for JSON transformation
 *
 * @property-read AggregatorContext $aggregatorContext - Contexto original
 * @property-read array             $allResults        - Todos los AggregatorResult
 * @property-read array             $options           - Opciones de transformación
 */
final readonly class TransformationContext
```

**Ejemplo de uso:**
```php
// En un transformador
public function transform(mixed $data, TransformationContext $context): array
{
    // Acceder a datos de otro agregador
    $multimedia = $context->getResult('multimedia')?->getData();

    // Acceder al contexto original
    $siteId = $context->getAggregatorContext()->getMetadata()['siteId'];

    return [...];
}
```

---

## DTOs de Comunicación

### Aggregator Configuration DTO

**Propósito**: Configuración extraída del atributo `#[AsAggregator]`.

```php
namespace App\Infrastructure\Aggregator\DTO;

final readonly class AggregatorConfig
{
    public function __construct(
        public string $name,
        public int $priority,
        public int $timeout,
        public array $dependencies,
        public mixed $fallback = null
    ) {}
}
```

**Ejemplo de configuración:**
```json
{
  "name": "tag",
  "priority": 70,
  "timeout": 3000,
  "dependencies": [],
  "fallback": []
}
```

```json
{
  "name": "bodyTag",
  "priority": 100,
  "timeout": 10000,
  "dependencies": ["multimedia"],
  "fallback": {"bodyElements": []}
}
```

---

### Execution Plan DTO

**Propósito**: Plan de ejecución generado por DependencyResolver.

```php
namespace App\Application\Aggregator\DTO;

final readonly class ExecutionPlan
{
    /**
     * @param array<int, array<AggregatorInterface>> $batches
     */
    public function __construct(
        public array $batches,
        public int $totalAggregators,
        public int $totalBatches
    ) {}
}
```

**Ejemplo de plan:**
```json
{
  "batches": [
    {
      "batchIndex": 0,
      "aggregators": ["tag", "multimedia", "journalist", "section"],
      "canRunInParallel": true
    },
    {
      "batchIndex": 1,
      "aggregators": ["bodyTag"],
      "canRunInParallel": false,
      "dependsOn": ["multimedia"]
    },
    {
      "batchIndex": 2,
      "aggregators": ["insertedNews", "recommendedNews"],
      "canRunInParallel": true
    }
  ],
  "totalAggregators": 7,
  "totalBatches": 3
}
```

---

## Estructuras de Datos de Agregadores

### Tag Aggregator Data

**Input (de rawData):**
```json
{
  "tagIds": ["tag-1", "tag-2", "tag-3"]
}
```

**Output (resolved):**
```json
[
  {
    "id": "tag-1",
    "name": "Sports",
    "slug": "sports",
    "url": "/tags/sports",
    "type": "category"
  },
  {
    "id": "tag-2",
    "name": "Football",
    "slug": "football",
    "url": "/tags/football",
    "type": "topic"
  }
]
```

---

### Multimedia Aggregator Data

**Input (de rawData):**
```json
{
  "multimediaIds": ["photo-001", "video-001", "widget-001"],
  "mainPhotoId": "photo-001"
}
```

**Output (resolved):**
```json
{
  "photo-001": {
    "id": "photo-001",
    "type": "photo",
    "url": "https://static.example.com/photos/001.jpg",
    "width": 1920,
    "height": 1080,
    "caption": "Main photo caption",
    "credit": "Photographer Name",
    "shots": {
      "thumbnail": "https://thumbor.../100x100/...",
      "medium": "https://thumbor.../640x480/...",
      "large": "https://thumbor.../1920x1080/..."
    }
  },
  "video-001": {
    "id": "video-001",
    "type": "video",
    "url": "https://video.example.com/v/001",
    "embedUrl": "https://www.youtube.com/embed/abc123",
    "duration": 185,
    "thumbnail": "https://..."
  },
  "widget-001": {
    "id": "widget-001",
    "type": "widget",
    "html": "<iframe src='...'></iframe>",
    "provider": "twitter"
  }
}
```

---

### Journalist Aggregator Data

**Input (de rawData):**
```json
{
  "signatures": [
    {"journalistId": "j-001", "aliasId": "alias-001"},
    {"journalistId": "j-002", "aliasId": "alias-002"}
  ]
}
```

**Output (resolved):**
```json
[
  {
    "id": "j-001",
    "aliasId": "alias-001",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "avatar": "https://static.../avatars/john.jpg",
    "bio": "Senior sports journalist...",
    "socialLinks": {
      "twitter": "@johndoe",
      "linkedin": "linkedin.com/in/johndoe"
    }
  }
]
```

---

### BodyTag Aggregator Data

**Input (de rawData.body.bodyElements):**
```json
[
  {"type": "paragraph", "text": "First paragraph text..."},
  {"type": "subHead", "text": "Section Title"},
  {"type": "bodyTagPicture", "multimediaId": "photo-002"},
  {"type": "bodyTagVideo", "multimediaId": "video-001"},
  {"type": "bodyTagInsertedNews", "editorialId": "news-100"},
  {"type": "unorderedList", "items": ["Item 1", "Item 2"]},
  {"type": "bodyTagHtml", "html": "<div class='custom'>...</div>"}
]
```

**Output (resolved con nested data):**
```json
[
  {
    "type": "paragraph",
    "content": "First paragraph text...",
    "index": 0
  },
  {
    "type": "subHead",
    "content": "Section Title",
    "level": 2,
    "index": 1
  },
  {
    "type": "picture",
    "multimedia": {
      "id": "photo-002",
      "url": "https://...",
      "shots": {...},
      "caption": "Photo caption"
    },
    "index": 2
  },
  {
    "type": "video",
    "multimedia": {
      "id": "video-001",
      "embedUrl": "https://youtube.com/embed/...",
      "thumbnail": "https://..."
    },
    "index": 3
  },
  {
    "type": "insertedNews",
    "editorial": {
      "id": "news-100",
      "title": "Related Article Title",
      "url": "/article/news-100",
      "thumbnail": "https://..."
    },
    "index": 4
  },
  {
    "type": "list",
    "listType": "unordered",
    "items": ["Item 1", "Item 2"],
    "index": 5
  },
  {
    "type": "html",
    "content": "<div class='custom'>...</div>",
    "sanitized": true,
    "index": 6
  }
]
```

---

### Section Aggregator Data

**Input (de rawData):**
```json
{
  "sectionId": "section-001",
  "siteId": "site-001"
}
```

**Output (resolved):**
```json
{
  "id": "section-001",
  "name": "Sports",
  "slug": "sports",
  "url": "/sports",
  "parentSection": {
    "id": "section-root",
    "name": "Home",
    "url": "/"
  },
  "site": {
    "id": "site-001",
    "name": "Main Site",
    "domain": "example.com"
  }
}
```

---

## Estructuras de Respuesta JSON Final

### Editorial Response (Output del Pipeline)

```json
{
  "id": "4433",
  "type": "news",
  "title": "Breaking News Title",
  "headline": "Subtitle here",
  "publishedAt": "2026-01-28T10:00:00Z",
  "updatedAt": "2026-01-28T12:30:00Z",
  "url": "/article/4433/breaking-news-title",

  "section": {
    "id": "section-001",
    "name": "Sports",
    "url": "/sports"
  },

  "multimedia": {
    "main": {
      "id": "photo-001",
      "type": "photo",
      "shots": {...}
    },
    "gallery": [...]
  },

  "signatures": [
    {
      "id": "j-001",
      "name": "John Doe",
      "avatar": "https://..."
    }
  ],

  "tags": [
    {"id": "tag-1", "name": "Sports", "url": "/tags/sports"},
    {"id": "tag-2", "name": "Football", "url": "/tags/football"}
  ],

  "body": [
    {"type": "paragraph", "content": "..."},
    {"type": "picture", "multimedia": {...}},
    {"type": "video", "multimedia": {...}}
  ],

  "relatedContent": {
    "insertedNews": [...],
    "recommendedNews": [...]
  },

  "_meta": {
    "aggregatorStats": {
      "total": 7,
      "successful": 7,
      "failed": 0,
      "totalTime": 0.234
    }
  }
}
```

---

## Mapeo de Tipos

### Body Element Types Mapping

| Tipo Interno | Tipo JSON Output | Transformador |
|--------------|------------------|---------------|
| `Paragraph` | `paragraph` | `ParagraphTransformer` |
| `SubHead` | `subHead` | `SubHeadTransformer` |
| `BodyTagPicture` | `picture` | `BodyTagPictureTransformer` |
| `BodyTagVideo` | `video` | `BodyTagVideoTransformer` |
| `BodyTagVideoYoutube` | `video` | `BodyTagVideoYoutubeTransformer` |
| `BodyTagInsertedNews` | `insertedNews` | `BodyTagInsertedNewsTransformer` |
| `BodyTagHtml` | `html` | `BodyTagHtmlTransformer` |
| `UnorderedList` | `list` | `ListTransformer` |
| `NumberedList` | `list` | `ListTransformer` |
| `GenericList` | `list` | `ListTransformer` |
| `BodyTagMembershipCard` | `membershipCard` | `BodyTagMembershipTransformer` |

### Multimedia Types Mapping

| Tipo Interno | Tipo JSON Output | Agregador |
|--------------|------------------|-----------|
| `MultimediaPhoto` | `photo` | `MultimediaAggregator` |
| `MultimediaVideo` | `video` | `MultimediaAggregator` |
| `MultimediaEmbedVideo` | `embedVideo` | `MultimediaAggregator` |
| `MultimediaWidget` | `widget` | `MultimediaAggregator` |

---

## Consideraciones de Performance

### Tamaño Estimado de Datos en Memoria

| Componente | Tamaño Típico | Máximo |
|------------|---------------|--------|
| AggregatorContext | ~10 KB | ~50 KB |
| AggregatorResult (por agregador) | ~2-5 KB | ~20 KB |
| TransformationContext | ~30 KB | ~100 KB |
| Response Final | ~20 KB | ~100 KB |

### Claves de Cache (si se implementa)

```
editorial:{id}:context     -> AggregatorContext serializado
editorial:{id}:tag         -> Tag aggregation result
editorial:{id}:multimedia  -> Multimedia aggregation result
editorial:{id}:response    -> Final JSON response
```

---

**Próximo paso**: 20_api_contracts.md (Contratos de API)
