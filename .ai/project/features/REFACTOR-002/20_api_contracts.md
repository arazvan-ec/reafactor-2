# API Contracts - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Nota Importante

Este feature es un **refactoring interno**. Los **endpoints HTTP externos no cambian**.
Este documento define los **contratos internos** entre los componentes del sistema de agregadores.

---

## Contratos de Interfaces PHP

### 1. AggregatorInterface (Base)

```php
namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Base contract for all data aggregators
 */
interface AggregatorInterface
{
    /**
     * Unique name identifier for this aggregator
     *
     * @example "tag", "multimedia", "journalist", "bodyTag"
     * @return string
     */
    public function getName(): string;

    /**
     * Execution priority (higher number = executes first in its batch)
     *
     * Priority levels:
     * - 100+: Critical aggregators (bodyTag with dependencies)
     * - 70-99: High priority (tag, multimedia)
     * - 40-69: Medium priority (journalist, section)
     * - 0-39: Low priority (recommendations)
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Names of aggregators this one depends on
     *
     * Aggregators with dependencies will execute AFTER their dependencies complete.
     * Circular dependencies will throw CircularDependencyException.
     *
     * @example ["multimedia"] for bodyTag that needs resolved multimedia
     * @return string[]
     */
    public function getDependencies(): array;

    /**
     * Whether this aggregator should execute for the given context
     *
     * Return false to skip execution (e.g., if no tags in editorial)
     *
     * @param AggregatorContext $context
     * @return bool
     */
    public function supports(AggregatorContext $context): bool;
}
```

---

### 2. AsyncAggregatorInterface

```php
namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Contract for asynchronous aggregators that return Promises
 *
 * Use this for aggregators that make HTTP calls to external services.
 */
interface AsyncAggregatorInterface extends AggregatorInterface
{
    /**
     * Execute aggregation asynchronously
     *
     * The Promise MUST resolve to an AggregatorResult:
     * - On success: AggregatorResult::success($name, $data, $time)
     * - On failure: AggregatorResult::failure($name, $error, $fallback, $time)
     *
     * @param AggregatorContext $context
     * @return PromiseInterface<AggregatorResult>
     */
    public function aggregate(AggregatorContext $context): PromiseInterface;

    /**
     * Timeout in milliseconds for this aggregator
     *
     * If exceeded, the aggregator will fail and use fallback value.
     *
     * @return int Timeout in ms (e.g., 3000 = 3 seconds)
     */
    public function getTimeout(): int;

    /**
     * Fallback value when aggregation fails
     *
     * This value is used when:
     * - HTTP request fails (timeout, 5xx, network error)
     * - Response parsing fails
     * - Any exception is thrown
     *
     * @return mixed Typically [] or null
     */
    public function getFallback(): mixed;
}
```

---

### 3. SyncAggregatorInterface

```php
namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;

/**
 * Contract for synchronous aggregators
 *
 * Use this for aggregators that don't make external HTTP calls
 * or process data already available in context.
 */
interface SyncAggregatorInterface extends AggregatorInterface
{
    /**
     * Execute aggregation synchronously
     *
     * @param AggregatorContext $context
     * @return AggregatorResult
     */
    public function aggregate(AggregatorContext $context): AggregatorResult;
}
```

---

### 4. JsonTransformerInterface

```php
namespace App\Domain\Transformer\Contract;

use App\Domain\Transformer\ValueObject\TransformationContext;

/**
 * Contract for transforming domain data to JSON-serializable format
 */
interface JsonTransformerInterface
{
    /**
     * The type of data this transformer handles
     *
     * This is used for auto-registration and lookup.
     *
     * @example "tag", "multimedia", "paragraph", "bodyTagPicture"
     * @return string
     */
    public function getType(): string;

    /**
     * Transform data to JSON-serializable array
     *
     * @param mixed $data Domain object or raw data
     * @param TransformationContext $context Access to other results
     * @return array JSON-serializable structure
     */
    public function transform(mixed $data, TransformationContext $context): array;

    /**
     * Whether this transformer supports the given data
     *
     * @param mixed $data
     * @return bool
     */
    public function supports(mixed $data): bool;
}
```

---

### 5. AggregatorRegistry Contract

```php
namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Registry for aggregator instances
 */
interface AggregatorRegistryInterface
{
    /**
     * Register an aggregator
     *
     * @param AggregatorInterface $aggregator
     * @throws DuplicateAggregatorException if name already registered
     */
    public function register(AggregatorInterface $aggregator): void;

    /**
     * Get aggregator by name
     *
     * @param string $name
     * @return AggregatorInterface
     * @throws AggregatorNotFoundException if not found
     */
    public function get(string $name): AggregatorInterface;

    /**
     * Check if aggregator exists
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get all registered aggregators
     *
     * @return AggregatorInterface[]
     */
    public function getAll(): array;

    /**
     * Get aggregators that support given context, sorted by priority
     *
     * @param AggregatorContext $context
     * @return AggregatorInterface[] Sorted by priority descending
     */
    public function getForContext(AggregatorContext $context): array;
}
```

---

### 6. AggregatorExecutor Contract

```php
namespace App\Application\Aggregator;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;

/**
 * Executor for running aggregators with dependency resolution
 */
interface AggregatorExecutorInterface
{
    /**
     * Execute all applicable aggregators for the context
     *
     * Process:
     * 1. Get applicable aggregators from registry
     * 2. Resolve dependencies (topological sort)
     * 3. Execute in batches (parallel when possible)
     * 4. Return all results
     *
     * @param AggregatorContext $context
     * @return array<string, AggregatorResult> Keyed by aggregator name
     */
    public function execute(AggregatorContext $context): array;
}
```

---

### 7. DependencyResolver Contract

```php
namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Exception\CircularDependencyException;

/**
 * Resolver for aggregator dependencies
 */
interface DependencyResolverInterface
{
    /**
     * Resolve dependencies and return execution batches
     *
     * Batches are groups of aggregators that can run in parallel.
     * Batches execute sequentially (batch 0 before batch 1, etc).
     *
     * @param AggregatorInterface[] $aggregators
     * @return AggregatorInterface[][] Array of batches
     * @throws CircularDependencyException if circular dependencies detected
     */
    public function resolve(array $aggregators): array;

    /**
     * Validate that all dependencies exist
     *
     * @param AggregatorInterface[] $aggregators
     * @throws AggregatorNotFoundException if dependency not found
     */
    public function validateDependencies(array $aggregators): void;
}
```

---

### 8. TransformationPipeline Contract

```php
namespace App\Application\Transformer;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;

/**
 * Pipeline for transforming aggregator results to JSON
 */
interface TransformationPipelineInterface
{
    /**
     * Transform all aggregator results to JSON format
     *
     * @param array<string, AggregatorResult> $results
     * @param AggregatorContext $context
     * @return array<string, array> Transformed data keyed by aggregator name
     */
    public function transform(array $results, AggregatorContext $context): array;
}
```

---

### 9. OrchestrationPipeline Contract

```php
namespace App\Application\Orchestration;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

/**
 * Main orchestration pipeline
 */
interface OrchestrationPipelineInterface
{
    /**
     * Execute full orchestration: aggregate -> transform -> build response
     *
     * @param AggregatorContext $context
     * @return array Final JSON-ready response
     */
    public function execute(AggregatorContext $context): array;
}
```

---

## Contratos de Eventos

### AggregatorStartedEvent

```php
namespace App\Domain\Aggregator\Event;

/**
 * Dispatched when an aggregator starts execution
 */
final readonly class AggregatorStartedEvent
{
    public function __construct(
        public string $aggregatorName,
        public string $editorialId,
        public float $timestamp
    ) {}
}
```

**Uso:**
```php
$this->eventDispatcher->dispatch(new AggregatorStartedEvent(
    'tag',
    '4433',
    microtime(true)
));
```

---

### AggregatorCompletedEvent

```php
namespace App\Domain\Aggregator\Event;

/**
 * Dispatched when an aggregator completes (success or failure)
 */
final readonly class AggregatorCompletedEvent
{
    public function __construct(
        public string $aggregatorName,
        public string $editorialId,
        public bool $success,
        public float $executionTime,
        public ?string $error = null
    ) {}
}
```

**Uso:**
```php
$this->eventDispatcher->dispatch(new AggregatorCompletedEvent(
    'tag',
    '4433',
    true,
    0.045,
    null
));
```

---

### OrchestrationCompletedEvent

```php
namespace App\Domain\Aggregator\Event;

/**
 * Dispatched when full orchestration completes
 */
final readonly class OrchestrationCompletedEvent
{
    public function __construct(
        public string $editorialId,
        public int $aggregatorCount,
        public int $successCount,
        public int $failureCount,
        public float $totalTime
    ) {}
}
```

---

## Contratos de PHP Attributes

### AsAggregator

```php
namespace App\Infrastructure\Attribute;

/**
 * Marks a class as an aggregator for auto-registration
 *
 * @example
 * #[AsAggregator(name: 'tag', priority: 70, timeout: 3000)]
 * class TagAggregator implements AsyncAggregatorInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAggregator
{
    /**
     * @param string $name Unique aggregator name
     * @param int $priority Execution priority (higher = first)
     * @param int $timeout Timeout in milliseconds
     * @param string[] $dependencies Names of required aggregators
     */
    public function __construct(
        public string $name,
        public int $priority = 50,
        public int $timeout = 5000,
        public array $dependencies = []
    ) {}
}
```

---

### AsJsonTransformer

```php
namespace App\Infrastructure\Attribute;

/**
 * Marks a class as a JSON transformer for auto-registration
 *
 * @example
 * #[AsJsonTransformer(type: 'tag')]
 * class TagJsonTransformer implements JsonTransformerInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsJsonTransformer
{
    /**
     * @param string $type Data type this transformer handles
     */
    public function __construct(
        public string $type
    ) {}
}
```

---

## Contratos de Configuración (services.yaml)

### Tags de Servicio

```yaml
# Auto-registration tags
services:
  _defaults:
    autoconfigure: true

  # Aggregators auto-tagged with app.aggregator
  App\Infrastructure\Aggregator\:
    resource: '../src/Infrastructure/Aggregator/'
    tags: ['app.aggregator']

  # Transformers auto-tagged with app.json_transformer
  App\Infrastructure\Transformer\:
    resource: '../src/Infrastructure/Transformer/'
    tags: ['app.json_transformer']
```

---

## Contratos de Excepciones

### Exception Hierarchy

```
AggregatorException (base)
├── AggregatorNotFoundException
│   Message: "Aggregator '{name}' not found in registry"
│   Context: name
│
├── AggregatorTimeoutException
│   Message: "Aggregator '{name}' timed out after {timeout}ms"
│   Context: name, timeout, editorialId
│
├── CircularDependencyException
│   Message: "Circular dependency detected: {cycle}"
│   Context: cycle (array of aggregator names)
│
├── DuplicateAggregatorException
│   Message: "Aggregator '{name}' is already registered"
│   Context: name
│
└── AggregatorValidationException
    Message: "Aggregator validation failed: {reason}"
    Context: aggregatorName, reason
```

---

## Contratos HTTP Internos (Clientes Existentes)

Los agregadores usan los clientes HTTP existentes. No se crean nuevos endpoints.

### QueryTagClient (existente)

```php
interface QueryTagClientInterface
{
    /**
     * @param string $tagId
     * @return Tag|null
     */
    public function findTagById(string $tagId): ?Tag;

    /**
     * @param string[] $tagIds
     * @return Tag[]
     */
    public function findTagsByIds(array $tagIds): array;
}
```

### QueryMultimediaClient (existente)

```php
interface QueryMultimediaClientInterface
{
    /**
     * @param string $multimediaId
     * @param bool $async If true, returns Promise
     * @return Multimedia|PromiseInterface
     */
    public function findMultimediaById(string $multimediaId, bool $async = false);

    /**
     * @param string $photoId
     * @return MultimediaPhoto|null
     */
    public function findPhotoById(string $photoId): ?MultimediaPhoto;
}
```

---

## Flujo de Llamadas (Sequence)

```
Controller
    │
    │ new AggregatorContext(editorialId, type, rawData)
    ▼
OrchestrationPipeline::execute(context)
    │
    │ registry.getForContext(context)
    ▼
AggregatorRegistry
    │
    │ returns [TagAggregator, MultimediaAggregator, ...]
    ▼
DependencyResolver::resolve(aggregators)
    │
    │ returns [[batch0], [batch1], ...]
    ▼
AggregatorExecutor::executeBatch(batch, context)
    │
    ├──► TagAggregator::aggregate(context) → Promise<AggregatorResult>
    ├──► MultimediaAggregator::aggregate(context) → Promise<AggregatorResult>
    └──► JournalistAggregator::aggregate(context) → Promise<AggregatorResult>
    │
    │ Utils::settle(promises)->wait()
    ▼
AggregatorResult[] (all results)
    │
    ▼
TransformationPipeline::transform(results, context)
    │
    ├──► TagJsonTransformer::transform(tagData)
    ├──► MultimediaJsonTransformer::transform(multimediaData)
    └──► ...
    │
    ▼
array (transformed JSON data)
    │
    ▼
ResponseBuilder::build(transformedData, context)
    │
    ▼
array (final JSON response)
```

---

## Validación de Contratos

### Pre-condiciones

| Método | Pre-condición |
|--------|---------------|
| `AggregatorRegistry::get()` | Aggregator must be registered |
| `DependencyResolver::resolve()` | No circular dependencies |
| `AsyncAggregator::aggregate()` | Context must have required rawData |
| `JsonTransformer::transform()` | Data must be of expected type |

### Post-condiciones

| Método | Post-condición |
|--------|----------------|
| `AggregatorRegistry::register()` | Aggregator is retrievable by name |
| `AggregatorExecutor::execute()` | All applicable aggregators executed |
| `OrchestrationPipeline::execute()` | Returns valid JSON-serializable array |

### Invariantes

- AggregatorContext es inmutable
- AggregatorResult es inmutable
- Un agregador con dependencias nunca ejecuta antes que sus dependencias
- Agregadores en el mismo batch pueden ejecutarse en cualquier orden

---

**Próximo paso**: 30_tasks_backend.md (Tareas Backend Detalladas)
