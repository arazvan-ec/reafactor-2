# Arquitectura - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Arquitectura DDD

### Visión General

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              INFRASTRUCTURE LAYER                            │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐              │
│  │  Controllers    │  │  HTTP Clients   │  │ Compiler Passes │              │
│  │  (Thin)         │  │  (Guzzle)       │  │ (Auto-wiring)   │              │
│  └────────┬────────┘  └────────┬────────┘  └─────────────────┘              │
│           │                    │                                             │
└───────────┼────────────────────┼────────────────────────────────────────────┘
            │                    │
            ▼                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                              APPLICATION LAYER                               │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    OrchestrationPipeline                             │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐│   │
│  │  │ Aggregator  │  │ Aggregator  │  │ Aggregator  │  │ Aggregator  ││   │
│  │  │ Registry    │  │ Executor    │  │ Context     │  │ Result      ││   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘│   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    TransformationPipeline                            │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                  │   │
│  │  │ Transformer │  │ Transformer │  │ Response    │                  │   │
│  │  │ Registry    │  │ Executor    │  │ Builder     │                  │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘                  │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
            │                                       │
            ▼                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                               DOMAIN LAYER                                   │
│                                                                             │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐          │
│  │   Aggregator     │  │   Transformer    │  │   Value Objects  │          │
│  │   Contracts      │  │   Contracts      │  │   (Context, etc) │          │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘          │
│                                                                             │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐          │
│  │   Exceptions     │  │   Events         │  │   Enums          │          │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Domain Layer

### Contratos de Agregadores

#### AggregatorInterface
```php
namespace App\Domain\Aggregator\Contract;

interface AggregatorInterface
{
    /**
     * Unique identifier for this aggregator
     */
    public function getName(): string;

    /**
     * Execution priority (higher = executes first)
     */
    public function getPriority(): int;

    /**
     * List of aggregator names this depends on
     * @return string[]
     */
    public function getDependencies(): array;

    /**
     * Whether this aggregator should run for given context
     */
    public function supports(AggregatorContext $context): bool;
}
```

#### AsyncAggregatorInterface
```php
namespace App\Domain\Aggregator\Contract;

use GuzzleHttp\Promise\PromiseInterface;

interface AsyncAggregatorInterface extends AggregatorInterface
{
    /**
     * Execute aggregation and return a Promise
     */
    public function aggregate(AggregatorContext $context): PromiseInterface;

    /**
     * Timeout in milliseconds for this aggregator
     */
    public function getTimeout(): int;

    /**
     * Fallback value when aggregation fails
     */
    public function getFallback(): mixed;
}
```

#### SyncAggregatorInterface
```php
namespace App\Domain\Aggregator\Contract;

interface SyncAggregatorInterface extends AggregatorInterface
{
    /**
     * Execute aggregation synchronously
     */
    public function aggregate(AggregatorContext $context): AggregatorResult;
}
```

### Value Objects

#### AggregatorContext
```php
namespace App\Domain\Aggregator\ValueObject;

final readonly class AggregatorContext
{
    public function __construct(
        private string $editorialId,
        private string $editorialType,
        private array $rawData,
        private array $resolvedData = [],
        private array $metadata = []
    ) {}

    public function getEditorialId(): string { return $this->editorialId; }
    public function getEditorialType(): string { return $this->editorialType; }
    public function getRawData(): array { return $this->rawData; }
    public function getResolvedData(): array { return $this->resolvedData; }
    public function getMetadata(): array { return $this->metadata; }

    public function withResolvedData(string $key, mixed $data): self
    {
        return new self(
            $this->editorialId,
            $this->editorialType,
            $this->rawData,
            array_merge($this->resolvedData, [$key => $data]),
            $this->metadata
        );
    }

    public function withMetadata(string $key, mixed $value): self
    {
        return new self(
            $this->editorialId,
            $this->editorialType,
            $this->rawData,
            $this->resolvedData,
            array_merge($this->metadata, [$key => $value])
        );
    }
}
```

#### AggregatorResult
```php
namespace App\Domain\Aggregator\ValueObject;

final readonly class AggregatorResult
{
    public function __construct(
        private string $aggregatorName,
        private mixed $data,
        private bool $success,
        private ?string $error = null,
        private float $executionTime = 0.0
    ) {}

    public function getAggregatorName(): string { return $this->aggregatorName; }
    public function getData(): mixed { return $this->data; }
    public function isSuccess(): bool { return $this->success; }
    public function getError(): ?string { return $this->error; }
    public function getExecutionTime(): float { return $this->executionTime; }

    public static function success(string $name, mixed $data, float $time): self
    {
        return new self($name, $data, true, null, $time);
    }

    public static function failure(string $name, string $error, mixed $fallback, float $time): self
    {
        return new self($name, $fallback, false, $error, $time);
    }
}
```

### Contratos de Transformadores

#### JsonTransformerInterface
```php
namespace App\Domain\Transformer\Contract;

interface JsonTransformerInterface
{
    /**
     * The type of data this transformer handles
     */
    public function getType(): string;

    /**
     * Transform domain data to JSON-serializable array
     */
    public function transform(mixed $data, TransformationContext $context): array;

    /**
     * Whether this transformer supports the given data
     */
    public function supports(mixed $data): bool;
}
```

#### TransformationContext
```php
namespace App\Domain\Transformer\ValueObject;

final readonly class TransformationContext
{
    public function __construct(
        private AggregatorContext $aggregatorContext,
        private array $allResults,
        private array $options = []
    ) {}

    public function getAggregatorContext(): AggregatorContext
    {
        return $this->aggregatorContext;
    }

    public function getAllResults(): array { return $this->allResults; }
    public function getOptions(): array { return $this->options; }

    public function getResult(string $aggregatorName): ?AggregatorResult
    {
        return $this->allResults[$aggregatorName] ?? null;
    }
}
```

### Excepciones del Dominio

```php
namespace App\Domain\Aggregator\Exception;

class AggregatorException extends \RuntimeException {}
class AggregatorNotFoundException extends AggregatorException {}
class AggregatorTimeoutException extends AggregatorException {}
class CircularDependencyException extends AggregatorException {}
class AggregatorValidationException extends AggregatorException {}
```

### Eventos del Dominio

```php
namespace App\Domain\Aggregator\Event;

final readonly class AggregatorStartedEvent
{
    public function __construct(
        public string $aggregatorName,
        public string $editorialId,
        public float $timestamp
    ) {}
}

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

## Application Layer

### Orquestación

#### AggregatorRegistry
```php
namespace App\Application\Aggregator;

final class AggregatorRegistry
{
    /** @var array<string, AggregatorInterface> */
    private array $aggregators = [];

    public function register(AggregatorInterface $aggregator): void
    {
        $this->aggregators[$aggregator->getName()] = $aggregator;
    }

    public function get(string $name): AggregatorInterface
    {
        return $this->aggregators[$name]
            ?? throw new AggregatorNotFoundException($name);
    }

    /**
     * @return AggregatorInterface[]
     */
    public function getAll(): array
    {
        return $this->aggregators;
    }

    /**
     * Get aggregators that support this context, sorted by priority
     * @return AggregatorInterface[]
     */
    public function getForContext(AggregatorContext $context): array
    {
        $applicable = array_filter(
            $this->aggregators,
            fn($a) => $a->supports($context)
        );

        uasort($applicable, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        return array_values($applicable);
    }
}
```

#### AggregatorExecutor
```php
namespace App\Application\Aggregator;

use GuzzleHttp\Promise\Utils;

final class AggregatorExecutor
{
    public function __construct(
        private AggregatorRegistry $registry,
        private DependencyResolver $dependencyResolver,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Execute all applicable aggregators for the context
     * @return AggregatorResult[]
     */
    public function execute(AggregatorContext $context): array
    {
        $aggregators = $this->registry->getForContext($context);
        $executionPlan = $this->dependencyResolver->resolve($aggregators);

        $results = [];

        foreach ($executionPlan as $batch) {
            $batchResults = $this->executeBatch($batch, $context, $results);
            $results = array_merge($results, $batchResults);
        }

        return $results;
    }

    /**
     * Execute a batch of aggregators in parallel
     */
    private function executeBatch(
        array $aggregators,
        AggregatorContext $context,
        array $previousResults
    ): array {
        $promises = [];
        $syncResults = [];

        foreach ($aggregators as $aggregator) {
            $this->dispatchStartEvent($aggregator, $context);

            if ($aggregator instanceof AsyncAggregatorInterface) {
                $promises[$aggregator->getName()] = $this->wrapWithTimeout(
                    $aggregator->aggregate($context),
                    $aggregator->getTimeout(),
                    $aggregator->getFallback(),
                    $aggregator->getName()
                );
            } else {
                $start = microtime(true);
                try {
                    $result = $aggregator->aggregate($context);
                    $syncResults[$aggregator->getName()] = $result;
                } catch (\Throwable $e) {
                    $syncResults[$aggregator->getName()] = AggregatorResult::failure(
                        $aggregator->getName(),
                        $e->getMessage(),
                        null,
                        microtime(true) - $start
                    );
                }
            }
        }

        // Wait for all async promises
        $asyncResults = $this->resolvePromises($promises);

        return array_merge($syncResults, $asyncResults);
    }

    private function resolvePromises(array $promises): array
    {
        if (empty($promises)) {
            return [];
        }

        $settled = Utils::settle($promises)->wait();
        $results = [];

        foreach ($settled as $name => $outcome) {
            if ($outcome['state'] === 'fulfilled') {
                $results[$name] = $outcome['value'];
            } else {
                $results[$name] = $outcome['value']; // Already wrapped as failure
            }

            $this->dispatchCompletedEvent($results[$name]);
        }

        return $results;
    }
}
```

#### DependencyResolver
```php
namespace App\Application\Aggregator;

final class DependencyResolver
{
    /**
     * Resolve dependencies and return execution batches
     * @param AggregatorInterface[] $aggregators
     * @return AggregatorInterface[][] Batches to execute sequentially
     */
    public function resolve(array $aggregators): array
    {
        $graph = $this->buildGraph($aggregators);
        $this->detectCycles($graph);

        return $this->topologicalSort($graph, $aggregators);
    }

    private function buildGraph(array $aggregators): array
    {
        $graph = [];
        foreach ($aggregators as $aggregator) {
            $graph[$aggregator->getName()] = $aggregator->getDependencies();
        }
        return $graph;
    }

    private function detectCycles(array $graph): void
    {
        // Tarjan's algorithm for cycle detection
        $visited = [];
        $recStack = [];

        foreach (array_keys($graph) as $node) {
            if ($this->hasCycle($node, $graph, $visited, $recStack)) {
                throw new CircularDependencyException(
                    "Circular dependency detected involving: " . implode(', ', $recStack)
                );
            }
        }
    }

    private function topologicalSort(array $graph, array $aggregators): array
    {
        // Kahn's algorithm for topological sort with batching
        $inDegree = [];
        $aggregatorMap = [];

        foreach ($aggregators as $aggregator) {
            $name = $aggregator->getName();
            $inDegree[$name] = count($aggregator->getDependencies());
            $aggregatorMap[$name] = $aggregator;
        }

        $batches = [];

        while (!empty($inDegree)) {
            // Get all nodes with no dependencies (in-degree 0)
            $batch = [];
            foreach ($inDegree as $name => $degree) {
                if ($degree === 0) {
                    $batch[] = $aggregatorMap[$name];
                }
            }

            if (empty($batch)) {
                throw new CircularDependencyException("Unable to resolve dependencies");
            }

            // Sort batch by priority
            usort($batch, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
            $batches[] = $batch;

            // Remove processed nodes and update in-degrees
            foreach ($batch as $aggregator) {
                unset($inDegree[$aggregator->getName()]);

                foreach ($inDegree as $name => &$degree) {
                    if (in_array($aggregator->getName(), $graph[$name] ?? [])) {
                        $degree--;
                    }
                }
            }
        }

        return $batches;
    }
}
```

### Transformación

#### TransformerRegistry
```php
namespace App\Application\Transformer;

final class TransformerRegistry
{
    /** @var array<string, JsonTransformerInterface> */
    private array $transformers = [];

    public function register(JsonTransformerInterface $transformer): void
    {
        $this->transformers[$transformer->getType()] = $transformer;
    }

    public function get(string $type): JsonTransformerInterface
    {
        return $this->transformers[$type]
            ?? throw new TransformerNotFoundException($type);
    }

    public function getForData(mixed $data): ?JsonTransformerInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($data)) {
                return $transformer;
            }
        }
        return null;
    }
}
```

#### TransformationPipeline
```php
namespace App\Application\Transformer;

final class TransformationPipeline
{
    public function __construct(
        private TransformerRegistry $registry
    ) {}

    /**
     * Transform all aggregator results to JSON-ready format
     * @param AggregatorResult[] $results
     */
    public function transform(
        array $results,
        AggregatorContext $context
    ): array {
        $transformationContext = new TransformationContext($context, $results);
        $output = [];

        foreach ($results as $result) {
            if (!$result->isSuccess()) {
                continue; // Skip failed aggregations
            }

            $transformer = $this->registry->getForData($result->getData());
            if ($transformer) {
                $output[$result->getAggregatorName()] = $transformer->transform(
                    $result->getData(),
                    $transformationContext
                );
            } else {
                // Fallback: use data as-is
                $output[$result->getAggregatorName()] = $result->getData();
            }
        }

        return $output;
    }
}
```

### Orquestación Principal

#### OrchestrationPipeline
```php
namespace App\Application\Orchestration;

final class OrchestrationPipeline
{
    public function __construct(
        private AggregatorExecutor $aggregatorExecutor,
        private TransformationPipeline $transformationPipeline,
        private ResponseBuilder $responseBuilder,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function execute(AggregatorContext $context): array
    {
        $startTime = microtime(true);

        // Phase 1: Execute all aggregators
        $results = $this->aggregatorExecutor->execute($context);

        // Phase 2: Transform results to JSON
        $transformed = $this->transformationPipeline->transform($results, $context);

        // Phase 3: Build final response
        $response = $this->responseBuilder->build($transformed, $context);

        // Dispatch completion event
        $this->eventDispatcher->dispatch(new OrchestrationCompletedEvent(
            $context->getEditorialId(),
            count($results),
            count(array_filter($results, fn($r) => $r->isSuccess())),
            count(array_filter($results, fn($r) => !$r->isSuccess())),
            microtime(true) - $startTime
        ));

        return $response;
    }
}
```

---

## Infrastructure Layer

### Compiler Passes

#### AggregatorCompilerPass
```php
namespace App\Infrastructure\DependencyInjection\Compiler;

final class AggregatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AggregatorRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(AggregatorRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('app.aggregator');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
```

#### TransformerCompilerPass
```php
namespace App\Infrastructure\DependencyInjection\Compiler;

final class TransformerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(TransformerRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(TransformerRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('app.json_transformer');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
```

### PHP Attributes para Auto-Registro

#### AsAggregator
```php
namespace App\Infrastructure\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAggregator
{
    public function __construct(
        public string $name,
        public int $priority = 50,
        public int $timeout = 5000,
        public array $dependencies = []
    ) {}
}
```

#### AsJsonTransformer
```php
namespace App\Infrastructure\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsJsonTransformer
{
    public function __construct(
        public string $type
    ) {}
}
```

---

## Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            HTTP Request                                      │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         EditorialController                                  │
│  - Delega a OrchestrationPipeline                                           │
│  - Retorna JsonResponse                                                      │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        OrchestrationPipeline                                 │
│                                                                             │
│  ┌───────────────────────┐    ┌───────────────────────┐                    │
│  │   AggregatorContext   │───▶│   AggregatorExecutor  │                    │
│  │   (Input VO)          │    │                       │                    │
│  └───────────────────────┘    └───────────┬───────────┘                    │
│                                           │                                 │
│                                           ▼                                 │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                        DependencyResolver                            │   │
│  │   - Builds dependency graph                                          │   │
│  │   - Detects cycles                                                   │   │
│  │   - Creates execution batches                                        │   │
│  └──────────────────────────────────┬──────────────────────────────────┘   │
│                                     │                                       │
│                                     ▼                                       │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      Parallel Execution                              │   │
│  │                                                                      │   │
│  │   Batch 1 (no dependencies):                                         │   │
│  │   ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐               │   │
│  │   │   Tag    │ │Multimedia│ │Journalist│ │ Section  │               │   │
│  │   │Aggregator│ │Aggregator│ │Aggregator│ │Aggregator│               │   │
│  │   └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘               │   │
│  │        │            │            │            │                      │   │
│  │        └────────────┴────────────┴────────────┘                      │   │
│  │                            │                                         │   │
│  │                   Promise::settle()                                  │   │
│  │                            │                                         │   │
│  │                            ▼                                         │   │
│  │   Batch 2 (depends on multimedia):                                   │   │
│  │   ┌──────────────────┐                                               │   │
│  │   │  BodyTag         │                                               │   │
│  │   │  Aggregator      │                                               │   │
│  │   └────────┬─────────┘                                               │   │
│  │            │                                                         │   │
│  └────────────┼─────────────────────────────────────────────────────────┘   │
│               │                                                             │
│               ▼                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    TransformationPipeline                            │   │
│  │                                                                      │   │
│  │   ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │   │
│  │   │    Tag       │  │  Multimedia  │  │   BodyTag    │              │   │
│  │   │ Transformer  │  │ Transformer  │  │ Transformer  │              │   │
│  │   └──────────────┘  └──────────────┘  └──────────────┘              │   │
│  │                                                                      │   │
│  └────────────────────────────────┬────────────────────────────────────┘   │
│                                   │                                         │
│                                   ▼                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                       ResponseBuilder                                │   │
│  │   - Combines all transformed data                                    │   │
│  │   - Builds final JSON structure                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                            JSON Response                                     │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Dependencias entre Componentes

```
Domain Layer (no dependencies)
    │
    ├── Contract/AggregatorInterface
    ├── Contract/AsyncAggregatorInterface
    ├── Contract/SyncAggregatorInterface
    ├── Contract/JsonTransformerInterface
    ├── ValueObject/AggregatorContext
    ├── ValueObject/AggregatorResult
    ├── ValueObject/TransformationContext
    ├── Exception/*
    └── Event/*

Application Layer (depends on Domain)
    │
    ├── Aggregator/AggregatorRegistry
    ├── Aggregator/AggregatorExecutor
    ├── Aggregator/DependencyResolver
    ├── Transformer/TransformerRegistry
    ├── Transformer/TransformationPipeline
    └── Orchestration/OrchestrationPipeline

Infrastructure Layer (depends on Application & Domain)
    │
    ├── Aggregators/TagAggregator (implements AsyncAggregatorInterface)
    ├── Aggregators/MultimediaAggregator
    ├── Aggregators/BodyTagAggregator
    ├── Transformers/TagJsonTransformer
    ├── Transformers/MultimediaJsonTransformer
    ├── Compiler/AggregatorCompilerPass
    ├── Compiler/TransformerCompilerPass
    └── Attribute/AsAggregator, AsJsonTransformer
```

---

## Patrones de Diseño Aplicados

| Patrón | Uso | Componente |
|--------|-----|------------|
| **Strategy** | Diferentes agregadores para diferentes datos | `AsyncAggregator`, `SyncAggregator` |
| **Registry** | Registro central de agregadores y transformadores | `AggregatorRegistry`, `TransformerRegistry` |
| **Pipeline** | Ejecución secuencial de fases | `OrchestrationPipeline` |
| **Builder** | Construcción de respuestas JSON | `ResponseBuilder` |
| **Decorator** | Timeout wrapper para promesas | `wrapWithTimeout()` |
| **Observer** | Eventos de inicio/fin de agregación | Domain Events |
| **Chain of Responsibility** | Transformadores que procesan si pueden | `TransformerRegistry.getForData()` |
| **Dependency Injection** | Auto-wiring de agregadores | Compiler Passes |

---

## Estructura de Directorios Propuesta

```
src/
├── Domain/
│   └── Aggregator/
│       ├── Contract/
│       │   ├── AggregatorInterface.php
│       │   ├── AsyncAggregatorInterface.php
│       │   ├── SyncAggregatorInterface.php
│       │   └── JsonTransformerInterface.php
│       ├── ValueObject/
│       │   ├── AggregatorContext.php
│       │   ├── AggregatorResult.php
│       │   └── TransformationContext.php
│       ├── Exception/
│       │   ├── AggregatorException.php
│       │   ├── AggregatorNotFoundException.php
│       │   ├── AggregatorTimeoutException.php
│       │   └── CircularDependencyException.php
│       └── Event/
│           ├── AggregatorStartedEvent.php
│           ├── AggregatorCompletedEvent.php
│           └── OrchestrationCompletedEvent.php
│
├── Application/
│   ├── Aggregator/
│   │   ├── AggregatorRegistry.php
│   │   ├── AggregatorExecutor.php
│   │   └── DependencyResolver.php
│   ├── Transformer/
│   │   ├── TransformerRegistry.php
│   │   └── TransformationPipeline.php
│   └── Orchestration/
│       ├── OrchestrationPipeline.php
│       └── ResponseBuilder.php
│
└── Infrastructure/
    ├── Aggregator/
    │   ├── TagAggregator.php
    │   ├── MultimediaAggregator.php
    │   ├── JournalistAggregator.php
    │   ├── SectionAggregator.php
    │   ├── InsertedNewsAggregator.php
    │   ├── RecommendedNewsAggregator.php
    │   └── BodyTagAggregator.php
    ├── Transformer/
    │   ├── TagJsonTransformer.php
    │   ├── MultimediaJsonTransformer.php
    │   ├── JournalistJsonTransformer.php
    │   ├── BodyTagJsonTransformer.php
    │   └── BodyElement/
    │       ├── ParagraphTransformer.php
    │       ├── SubHeadTransformer.php
    │       ├── BodyTagPictureTransformer.php
    │       └── ... (30+ transformers)
    ├── Attribute/
    │   ├── AsAggregator.php
    │   └── AsJsonTransformer.php
    └── DependencyInjection/
        └── Compiler/
            ├── AggregatorCompilerPass.php
            └── TransformerCompilerPass.php
```

---

**Próximo paso**: 15_data_model.md (Modelo de Datos)
