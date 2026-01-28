# Tareas Backend - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Resumen

| Métrica | Valor |
|---------|-------|
| Total de tareas | 19 |
| Domain Layer | 5 tareas |
| Application Layer | 6 tareas |
| Infrastructure Layer | 8 tareas |
| Estimación total | ~5-6 días |

---

## Domain Layer Tasks

### BE-001: Crear Contratos de Agregadores

**Descripción:** Implementar las interfaces base para el sistema de agregadores en el Domain Layer.

**Archivos:**
- Crear: `src/Domain/Aggregator/Contract/AggregatorInterface.php`
- Crear: `src/Domain/Aggregator/Contract/AsyncAggregatorInterface.php`
- Crear: `src/Domain/Aggregator/Contract/SyncAggregatorInterface.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;

interface AggregatorInterface
{
    public function getName(): string;
    public function getPriority(): int;

    /** @return string[] */
    public function getDependencies(): array;

    public function supports(AggregatorContext $context): bool;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use GuzzleHttp\Promise\PromiseInterface;

interface AsyncAggregatorInterface extends AggregatorInterface
{
    public function aggregate(AggregatorContext $context): PromiseInterface;
    public function getTimeout(): int;
    public function getFallback(): mixed;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Contract;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;

interface SyncAggregatorInterface extends AggregatorInterface
{
    public function aggregate(AggregatorContext $context): AggregatorResult;
}
```

**Acceptance Criteria:**
- [ ] `AggregatorInterface` define métodos base
- [ ] `AsyncAggregatorInterface` extiende base y añade Promise support
- [ ] `SyncAggregatorInterface` extiende base para sync
- [ ] Todas las interfaces usan tipos estrictos
- [ ] PHPDoc completo para cada método

**Verificación:**
```bash
# Verificar sintaxis
php -l src/Domain/Aggregator/Contract/*.php

# Verificar que no hay dependencias de infraestructura
grep -r "Infrastructure" src/Domain/Aggregator/Contract/ && echo "ERROR: Domain depends on Infrastructure" || echo "OK"

# PHPStan nivel 8
./vendor/bin/phpstan analyse src/Domain/Aggregator/Contract/ --level 8
```

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 1-2 horas

**Dependencias:** Ninguna

---

### BE-002: Crear Value Objects de Agregación

**Descripción:** Implementar los Value Objects inmutables para contexto y resultados de agregación.

**Archivos:**
- Crear: `src/Domain/Aggregator/ValueObject/AggregatorContext.php`
- Crear: `src/Domain/Aggregator/ValueObject/AggregatorResult.php`
- Crear: `src/Domain/Transformer/ValueObject/TransformationContext.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\ValueObject;

final readonly class AggregatorContext
{
    /**
     * @param array<string, mixed> $rawData
     * @param array<string, mixed> $resolvedData
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $editorialId,
        private string $editorialType,
        private array $rawData,
        private array $resolvedData = [],
        private array $metadata = []
    ) {}

    public function getEditorialId(): string
    {
        return $this->editorialId;
    }

    public function getEditorialType(): string
    {
        return $this->editorialType;
    }

    /** @return array<string, mixed> */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /** @return array<string, mixed> */
    public function getResolvedData(): array
    {
        return $this->resolvedData;
    }

    public function getResolvedDataByKey(string $key): mixed
    {
        return $this->resolvedData[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

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

```php
<?php

declare(strict_types=1);

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

    public function getAggregatorName(): string
    {
        return $this->aggregatorName;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

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

**Acceptance Criteria:**
- [ ] `AggregatorContext` es inmutable (readonly)
- [ ] `AggregatorContext::withResolvedData()` retorna nueva instancia
- [ ] `AggregatorResult` tiene factory methods `success()` y `failure()`
- [ ] Todos los VOs usan PHP 8.1+ readonly
- [ ] Tests unitarios para inmutabilidad

**Verificación:**
```bash
# Tests unitarios
./vendor/bin/phpunit tests/Unit/Domain/Aggregator/ValueObject/

# Verificar readonly
grep -c "readonly class" src/Domain/Aggregator/ValueObject/*.php
# Esperado: 2 (o más)
```

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 2-3 horas

**Dependencias:** Ninguna

---

### BE-003: Crear Excepciones del Dominio

**Descripción:** Implementar la jerarquía de excepciones para el sistema de agregadores.

**Archivos:**
- Crear: `src/Domain/Aggregator/Exception/AggregatorException.php`
- Crear: `src/Domain/Aggregator/Exception/AggregatorNotFoundException.php`
- Crear: `src/Domain/Aggregator/Exception/AggregatorTimeoutException.php`
- Crear: `src/Domain/Aggregator/Exception/CircularDependencyException.php`
- Crear: `src/Domain/Aggregator/Exception/DuplicateAggregatorException.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

class AggregatorException extends \RuntimeException
{
    protected array $context = [];

    public function getContext(): array
    {
        return $this->context;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

final class AggregatorNotFoundException extends AggregatorException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf("Aggregator '%s' not found in registry", $name));
        $this->context = ['aggregator_name' => $name];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Exception;

final class CircularDependencyException extends AggregatorException
{
    /**
     * @param string[] $cycle
     */
    public function __construct(array $cycle)
    {
        parent::__construct(sprintf(
            "Circular dependency detected: %s",
            implode(' -> ', $cycle)
        ));
        $this->context = ['cycle' => $cycle];
    }
}
```

**Acceptance Criteria:**
- [ ] Jerarquía de excepciones implementada
- [ ] Cada excepción tiene contexto para debugging
- [ ] Mensajes de error descriptivos
- [ ] Tests para cada tipo de excepción

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Domain/Aggregator/Exception/
```

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 1-2 horas

**Dependencias:** Ninguna

---

### BE-004: Crear Eventos del Dominio

**Descripción:** Implementar eventos para observabilidad del sistema de agregadores.

**Archivos:**
- Crear: `src/Domain/Aggregator/Event/AggregatorStartedEvent.php`
- Crear: `src/Domain/Aggregator/Event/AggregatorCompletedEvent.php`
- Crear: `src/Domain/Aggregator/Event/OrchestrationCompletedEvent.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Event;

final readonly class AggregatorStartedEvent
{
    public function __construct(
        public string $aggregatorName,
        public string $editorialId,
        public float $timestamp
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Event;

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

```php
<?php

declare(strict_types=1);

namespace App\Domain\Aggregator\Event;

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

**Acceptance Criteria:**
- [ ] Eventos son readonly
- [ ] Eventos tienen propiedades públicas (simpler access)
- [ ] Eventos son self-documenting

**Verificación:**
```bash
php -l src/Domain/Aggregator/Event/*.php
./vendor/bin/phpstan analyse src/Domain/Aggregator/Event/ --level 8
```

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 1 hora

**Dependencias:** Ninguna

---

### BE-005: Crear Contrato de Transformadores

**Descripción:** Implementar la interfaz para transformadores JSON.

**Archivos:**
- Crear: `src/Domain/Transformer/Contract/JsonTransformerInterface.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Transformer\Contract;

use App\Domain\Transformer\ValueObject\TransformationContext;

interface JsonTransformerInterface
{
    public function getType(): string;

    /**
     * @return array<string, mixed>
     */
    public function transform(mixed $data, TransformationContext $context): array;

    public function supports(mixed $data): bool;
}
```

**Acceptance Criteria:**
- [ ] Interfaz define contrato claro
- [ ] PHPDoc documenta tipos de retorno
- [ ] Sin dependencias de infraestructura

**Verificación:**
```bash
php -l src/Domain/Transformer/Contract/JsonTransformerInterface.php
```

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 30 minutos

**Dependencias:** BE-002 (TransformationContext)

---

## Application Layer Tasks

### BE-006: Implementar AggregatorRegistry

**Descripción:** Crear el registro central de agregadores con soporte para filtrado por contexto.

**Archivos:**
- Crear: `src/Application/Aggregator/AggregatorRegistry.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Exception\AggregatorNotFoundException;
use App\Domain\Aggregator\Exception\DuplicateAggregatorException;
use App\Domain\Aggregator\ValueObject\AggregatorContext;

final class AggregatorRegistry
{
    /** @var array<string, AggregatorInterface> */
    private array $aggregators = [];

    public function register(AggregatorInterface $aggregator): void
    {
        $name = $aggregator->getName();

        if (isset($this->aggregators[$name])) {
            throw new DuplicateAggregatorException($name);
        }

        $this->aggregators[$name] = $aggregator;
    }

    public function get(string $name): AggregatorInterface
    {
        if (!isset($this->aggregators[$name])) {
            throw new AggregatorNotFoundException($name);
        }

        return $this->aggregators[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->aggregators[$name]);
    }

    /** @return AggregatorInterface[] */
    public function getAll(): array
    {
        return array_values($this->aggregators);
    }

    /**
     * @return AggregatorInterface[]
     */
    public function getForContext(AggregatorContext $context): array
    {
        $applicable = array_filter(
            $this->aggregators,
            static fn(AggregatorInterface $a): bool => $a->supports($context)
        );

        uasort(
            $applicable,
            static fn(AggregatorInterface $a, AggregatorInterface $b): int =>
                $b->getPriority() <=> $a->getPriority()
        );

        return array_values($applicable);
    }
}
```

**Acceptance Criteria:**
- [ ] Registro por nombre único
- [ ] Excepción si duplicado
- [ ] Filtrado por contexto funciona
- [ ] Ordenación por prioridad (desc)
- [ ] 10+ tests unitarios

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Application/Aggregator/AggregatorRegistryTest.php

# Ejemplo de tests esperados:
# ✓ Can register aggregator
# ✓ Throws exception on duplicate name
# ✓ Returns aggregator by name
# ✓ Throws exception when not found
# ✓ Filters by context
# ✓ Sorts by priority descending
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** BE-001, BE-003

---

### BE-007: Implementar DependencyResolver

**Descripción:** Implementar el resolvedor de dependencias con detección de ciclos.

**Archivos:**
- Crear: `src/Application/Aggregator/DependencyResolver.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Exception\AggregatorNotFoundException;
use App\Domain\Aggregator\Exception\CircularDependencyException;

final class DependencyResolver
{
    /**
     * @param AggregatorInterface[] $aggregators
     * @return AggregatorInterface[][] Batches
     */
    public function resolve(array $aggregators): array
    {
        $graph = $this->buildGraph($aggregators);
        $this->validateDependencies($aggregators, $graph);
        $this->detectCycles($graph);

        return $this->topologicalSort($graph, $aggregators);
    }

    /**
     * @param AggregatorInterface[] $aggregators
     * @return array<string, string[]>
     */
    private function buildGraph(array $aggregators): array
    {
        $graph = [];
        foreach ($aggregators as $aggregator) {
            $graph[$aggregator->getName()] = $aggregator->getDependencies();
        }
        return $graph;
    }

    /**
     * @param AggregatorInterface[] $aggregators
     * @param array<string, string[]> $graph
     */
    private function validateDependencies(array $aggregators, array $graph): void
    {
        $names = array_map(
            static fn(AggregatorInterface $a): string => $a->getName(),
            $aggregators
        );

        foreach ($graph as $name => $deps) {
            foreach ($deps as $dep) {
                if (!in_array($dep, $names, true)) {
                    throw new AggregatorNotFoundException($dep);
                }
            }
        }
    }

    /**
     * @param array<string, string[]> $graph
     */
    private function detectCycles(array $graph): void
    {
        $visited = [];
        $recStack = [];

        foreach (array_keys($graph) as $node) {
            if ($this->hasCycle($node, $graph, $visited, $recStack)) {
                throw new CircularDependencyException(array_keys($recStack));
            }
        }
    }

    /**
     * @param array<string, string[]> $graph
     * @param array<string, bool> $visited
     * @param array<string, bool> $recStack
     */
    private function hasCycle(
        string $node,
        array $graph,
        array &$visited,
        array &$recStack
    ): bool {
        if (isset($recStack[$node])) {
            return true;
        }

        if (isset($visited[$node])) {
            return false;
        }

        $visited[$node] = true;
        $recStack[$node] = true;

        foreach ($graph[$node] ?? [] as $neighbor) {
            if ($this->hasCycle($neighbor, $graph, $visited, $recStack)) {
                return true;
            }
        }

        unset($recStack[$node]);
        return false;
    }

    /**
     * @param array<string, string[]> $graph
     * @param AggregatorInterface[] $aggregators
     * @return AggregatorInterface[][]
     */
    private function topologicalSort(array $graph, array $aggregators): array
    {
        $aggregatorMap = [];
        foreach ($aggregators as $aggregator) {
            $aggregatorMap[$aggregator->getName()] = $aggregator;
        }

        $inDegree = [];
        foreach ($graph as $name => $deps) {
            $inDegree[$name] = count($deps);
        }

        $batches = [];

        while (!empty($inDegree)) {
            // Get all nodes with no dependencies
            $batch = [];
            foreach ($inDegree as $name => $degree) {
                if ($degree === 0) {
                    $batch[] = $aggregatorMap[$name];
                }
            }

            if (empty($batch)) {
                throw new CircularDependencyException(array_keys($inDegree));
            }

            // Sort batch by priority
            usort(
                $batch,
                static fn(AggregatorInterface $a, AggregatorInterface $b): int =>
                    $b->getPriority() <=> $a->getPriority()
            );

            $batches[] = $batch;

            // Remove processed and update degrees
            foreach ($batch as $aggregator) {
                $name = $aggregator->getName();
                unset($inDegree[$name]);

                foreach ($inDegree as $otherName => &$degree) {
                    if (in_array($name, $graph[$otherName] ?? [], true)) {
                        $degree--;
                    }
                }
            }
        }

        return $batches;
    }
}
```

**Acceptance Criteria:**
- [ ] Detecta dependencias circulares
- [ ] Ordena topológicamente (Kahn's algorithm)
- [ ] Agrupa en batches paralelos
- [ ] Valida que dependencias existen
- [ ] 15+ tests unitarios

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Application/Aggregator/DependencyResolverTest.php

# Tests esperados:
# ✓ Resolves simple graph without dependencies
# ✓ Resolves graph with one dependency
# ✓ Resolves graph with multiple dependencies
# ✓ Creates parallel batches
# ✓ Detects simple cycle (A -> B -> A)
# ✓ Detects complex cycle (A -> B -> C -> A)
# ✓ Throws when dependency not found
# ✓ Sorts batches by priority
```

**Estimación:**
- Complejidad: L (Large)
- Tiempo: 4-5 horas

**Dependencias:** BE-001, BE-003

---

### BE-008: Implementar AggregatorExecutor

**Descripción:** Implementar el ejecutor que coordina la ejecución de agregadores.

**Archivos:**
- Crear: `src/Application/Aggregator/AggregatorExecutor.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\Contract\SyncAggregatorInterface;
use App\Domain\Aggregator\Event\AggregatorCompletedEvent;
use App\Domain\Aggregator\Event\AggregatorStartedEvent;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use GuzzleHttp\Promise\Utils;
use Psr\EventDispatcher\EventDispatcherInterface;

final class AggregatorExecutor
{
    public function __construct(
        private readonly AggregatorRegistry $registry,
        private readonly DependencyResolver $dependencyResolver,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @return array<string, AggregatorResult>
     */
    public function execute(AggregatorContext $context): array
    {
        $aggregators = $this->registry->getForContext($context);

        if (empty($aggregators)) {
            return [];
        }

        $batches = $this->dependencyResolver->resolve($aggregators);
        $results = [];

        foreach ($batches as $batch) {
            $batchResults = $this->executeBatch($batch, $context);

            foreach ($batchResults as $name => $result) {
                $results[$name] = $result;
                // Update context with resolved data for next batch
                $context = $context->withResolvedData($name, $result->getData());
            }
        }

        return $results;
    }

    /**
     * @param AggregatorInterface[] $aggregators
     * @return array<string, AggregatorResult>
     */
    private function executeBatch(array $aggregators, AggregatorContext $context): array
    {
        $promises = [];
        $syncResults = [];

        foreach ($aggregators as $aggregator) {
            $this->dispatchStartEvent($aggregator, $context);

            if ($aggregator instanceof AsyncAggregatorInterface) {
                $promises[$aggregator->getName()] = $this->executeAsync($aggregator, $context);
            } elseif ($aggregator instanceof SyncAggregatorInterface) {
                $syncResults[$aggregator->getName()] = $this->executeSync($aggregator, $context);
            }
        }

        $asyncResults = $this->resolvePromises($promises);

        return array_merge($syncResults, $asyncResults);
    }

    private function executeAsync(
        AsyncAggregatorInterface $aggregator,
        AggregatorContext $context
    ): \GuzzleHttp\Promise\PromiseInterface {
        $startTime = microtime(true);
        $name = $aggregator->getName();

        return $aggregator->aggregate($context)
            ->then(
                function (mixed $data) use ($name, $startTime): AggregatorResult {
                    return AggregatorResult::success(
                        $name,
                        $data,
                        microtime(true) - $startTime
                    );
                },
                function (\Throwable $e) use ($aggregator, $startTime): AggregatorResult {
                    return AggregatorResult::failure(
                        $aggregator->getName(),
                        $e->getMessage(),
                        $aggregator->getFallback(),
                        microtime(true) - $startTime
                    );
                }
            );
    }

    private function executeSync(
        SyncAggregatorInterface $aggregator,
        AggregatorContext $context
    ): AggregatorResult {
        $startTime = microtime(true);

        try {
            return $aggregator->aggregate($context);
        } catch (\Throwable $e) {
            return AggregatorResult::failure(
                $aggregator->getName(),
                $e->getMessage(),
                null,
                microtime(true) - $startTime
            );
        }
    }

    /**
     * @param array<string, \GuzzleHttp\Promise\PromiseInterface> $promises
     * @return array<string, AggregatorResult>
     */
    private function resolvePromises(array $promises): array
    {
        if (empty($promises)) {
            return [];
        }

        $settled = Utils::settle($promises)->wait();
        $results = [];

        foreach ($settled as $name => $outcome) {
            $result = $outcome['value'];
            $results[$name] = $result;
            $this->dispatchCompletedEvent($result);
        }

        return $results;
    }

    private function dispatchStartEvent(AggregatorInterface $aggregator, AggregatorContext $context): void
    {
        $this->eventDispatcher->dispatch(new AggregatorStartedEvent(
            $aggregator->getName(),
            $context->getEditorialId(),
            microtime(true)
        ));
    }

    private function dispatchCompletedEvent(AggregatorResult $result): void
    {
        $this->eventDispatcher->dispatch(new AggregatorCompletedEvent(
            $result->getAggregatorName(),
            '', // Will be filled from context in real implementation
            $result->isSuccess(),
            $result->getExecutionTime(),
            $result->getError()
        ));
    }
}
```

**Acceptance Criteria:**
- [ ] Ejecuta batches secuencialmente
- [ ] Ejecuta agregadores dentro de batch en paralelo
- [ ] Maneja errores gracefully
- [ ] Actualiza contexto entre batches
- [ ] Emite eventos de inicio/fin
- [ ] 12+ tests unitarios

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Application/Aggregator/AggregatorExecutorTest.php

# Tests esperados:
# ✓ Executes empty aggregators list
# ✓ Executes single sync aggregator
# ✓ Executes single async aggregator
# ✓ Executes multiple aggregators in parallel
# ✓ Executes batches sequentially
# ✓ Handles async failure with fallback
# ✓ Handles sync failure
# ✓ Updates context between batches
# ✓ Dispatches start event
# ✓ Dispatches completed event
```

**Estimación:**
- Complejidad: L (Large)
- Tiempo: 5-6 horas

**Dependencias:** BE-001, BE-002, BE-003, BE-004, BE-006, BE-007

---

### BE-009: Implementar TransformerRegistry

**Descripción:** Crear el registro para transformadores JSON.

**Archivos:**
- Crear: `src/Application/Transformer/TransformerRegistry.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Application\Transformer;

use App\Domain\Transformer\Contract\JsonTransformerInterface;
use App\Domain\Transformer\Exception\TransformerNotFoundException;

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
        if (!isset($this->transformers[$type])) {
            throw new TransformerNotFoundException($type);
        }

        return $this->transformers[$type];
    }

    public function has(string $type): bool
    {
        return isset($this->transformers[$type]);
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

**Acceptance Criteria:**
- [ ] Registro por tipo
- [ ] Búsqueda por data soportada
- [ ] Excepción si no encontrado

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Application/Transformer/TransformerRegistryTest.php
```

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 2 horas

**Dependencias:** BE-005

---

### BE-010: Implementar TransformationPipeline

**Descripción:** Crear el pipeline de transformación a JSON.

**Archivos:**
- Crear: `src/Application/Transformer/TransformationPipeline.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Application\Transformer;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Domain\Transformer\ValueObject\TransformationContext;

final class TransformationPipeline
{
    public function __construct(
        private readonly TransformerRegistry $registry
    ) {}

    /**
     * @param array<string, AggregatorResult> $results
     * @return array<string, mixed>
     */
    public function transform(array $results, AggregatorContext $context): array
    {
        $transformationContext = new TransformationContext($context, $results);
        $output = [];

        foreach ($results as $name => $result) {
            if (!$result->isSuccess()) {
                $output[$name] = $result->getData(); // Fallback data
                continue;
            }

            $data = $result->getData();
            $transformer = $this->registry->getForData($data);

            if ($transformer !== null) {
                $output[$name] = $transformer->transform($data, $transformationContext);
            } else {
                $output[$name] = $data; // Pass through as-is
            }
        }

        return $output;
    }
}
```

**Acceptance Criteria:**
- [ ] Transforma resultados exitosos
- [ ] Usa fallback para fallos
- [ ] Pass-through si no hay transformer

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Application/Transformer/TransformationPipelineTest.php
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3 horas

**Dependencias:** BE-002, BE-005, BE-009

---

### BE-011: Implementar OrchestrationPipeline

**Descripción:** Crear el pipeline principal que coordina todo el proceso.

**Archivos:**
- Crear: `src/Application/Orchestration/OrchestrationPipeline.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Application\Orchestration;

use App\Application\Aggregator\AggregatorExecutor;
use App\Application\Transformer\TransformationPipeline;
use App\Domain\Aggregator\Event\OrchestrationCompletedEvent;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use Psr\EventDispatcher\EventDispatcherInterface;

final class OrchestrationPipeline
{
    public function __construct(
        private readonly AggregatorExecutor $aggregatorExecutor,
        private readonly TransformationPipeline $transformationPipeline,
        private readonly ResponseBuilder $responseBuilder,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(AggregatorContext $context): array
    {
        $startTime = microtime(true);

        // Phase 1: Aggregate
        $results = $this->aggregatorExecutor->execute($context);

        // Phase 2: Transform
        $transformed = $this->transformationPipeline->transform($results, $context);

        // Phase 3: Build Response
        $response = $this->responseBuilder->build($transformed, $context);

        // Dispatch completion event
        $successCount = count(array_filter($results, static fn($r) => $r->isSuccess()));
        $failureCount = count($results) - $successCount;

        $this->eventDispatcher->dispatch(new OrchestrationCompletedEvent(
            $context->getEditorialId(),
            count($results),
            $successCount,
            $failureCount,
            microtime(true) - $startTime
        ));

        return $response;
    }
}
```

**Acceptance Criteria:**
- [ ] Coordina agregación → transformación → respuesta
- [ ] Emite evento de completado
- [ ] Retorna array JSON-serializable

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Application/Orchestration/OrchestrationPipelineTest.php
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** BE-004, BE-008, BE-010

---

## Infrastructure Layer Tasks

### BE-012: Crear PHP Attributes para Auto-Registro

**Descripción:** Implementar attributes para marcar agregadores y transformadores.

**Archivos:**
- Crear: `src/Infrastructure/Attribute/AsAggregator.php`
- Crear: `src/Infrastructure/Attribute/AsJsonTransformer.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAggregator
{
    /**
     * @param string[] $dependencies
     */
    public function __construct(
        public readonly string $name,
        public readonly int $priority = 50,
        public readonly int $timeout = 5000,
        public readonly array $dependencies = []
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsJsonTransformer
{
    public function __construct(
        public readonly string $type
    ) {}
}
```

**Acceptance Criteria:**
- [ ] Attributes funcionan con PHP 8.1+
- [ ] Reflection puede leer valores

**Verificación:**
```bash
php -l src/Infrastructure/Attribute/*.php
```

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 1 hora

**Dependencias:** Ninguna

---

### BE-013: Crear Compiler Passes

**Descripción:** Implementar compiler passes para auto-registro de agregadores y transformadores.

**Archivos:**
- Crear: `src/Infrastructure/DependencyInjection/Compiler/AggregatorCompilerPass.php`
- Crear: `src/Infrastructure/DependencyInjection/Compiler/JsonTransformerCompilerPass.php`
- Modificar: `src/Kernel.php` (registrar compiler passes)

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Compiler;

use App\Application\Aggregator\AggregatorRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AggregatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AggregatorRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(AggregatorRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('app.aggregator');

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
```

**Acceptance Criteria:**
- [ ] Compiler pass registra servicios tagueados
- [ ] Kernel registra los compiler passes
- [ ] Tests de integración pasan

**Verificación:**
```bash
# Clear cache and verify services registered
php bin/console cache:clear
php bin/console debug:container AggregatorRegistry
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 2-3 horas

**Dependencias:** BE-006, BE-009, BE-012

---

### BE-014: Implementar TagAggregator

**Descripción:** Migrar la lógica de TagResolver al nuevo sistema de agregadores.

**Archivos:**
- Crear: `src/Infrastructure/Aggregator/TagAggregator.php`
- Crear: `src/Infrastructure/Transformer/TagJsonTransformer.php`

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Aggregator;

use App\Domain\Aggregator\Contract\AsyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Infrastructure\Attribute\AsAggregator;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;

#[AsAggregator(name: 'tag', priority: 70, timeout: 3000)]
final class TagAggregator implements AsyncAggregatorInterface
{
    public function __construct(
        private readonly QueryTagClientInterface $tagClient
    ) {}

    public function getName(): string
    {
        return 'tag';
    }

    public function getPriority(): int
    {
        return 70;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getTimeout(): int
    {
        return 3000;
    }

    public function getFallback(): array
    {
        return [];
    }

    public function supports(AggregatorContext $context): bool
    {
        $rawData = $context->getRawData();
        return !empty($rawData['tags']);
    }

    public function aggregate(AggregatorContext $context): PromiseInterface
    {
        $tagIds = $context->getRawData()['tags'] ?? [];

        if (empty($tagIds)) {
            return Utils::fulfilled([]);
        }

        $promises = array_map(
            fn(string $tagId) => $this->tagClient->findTagByIdAsync($tagId),
            $tagIds
        );

        return Utils::all($promises)
            ->then(fn(array $tags) => array_filter($tags));
    }
}
```

**Acceptance Criteria:**
- [ ] Implementa AsyncAggregatorInterface
- [ ] Usa attribute #[AsAggregator]
- [ ] Carga tags en paralelo
- [ ] Fallback es array vacío
- [ ] Tests unitarios con mocks

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Infrastructure/Aggregator/TagAggregatorTest.php
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** BE-001, BE-012

---

### BE-015: Implementar MultimediaAggregator

**Descripción:** Migrar la lógica de MultimediaResolver al nuevo sistema.

**Archivos:**
- Crear: `src/Infrastructure/Aggregator/MultimediaAggregator.php`
- Crear: `src/Infrastructure/Transformer/MultimediaJsonTransformer.php`

**Implementación similar a BE-014 pero para multimedia.**

**Acceptance Criteria:**
- [ ] Implementa AsyncAggregatorInterface
- [ ] Maneja photos, videos, widgets
- [ ] Carga en paralelo
- [ ] Fallback es array vacío

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** BE-001, BE-012

---

### BE-016: Implementar JournalistAggregator

**Descripción:** Migrar la lógica de JournalistResolver al nuevo sistema.

**Archivos:**
- Crear: `src/Infrastructure/Aggregator/JournalistAggregator.php`
- Crear: `src/Infrastructure/Transformer/JournalistJsonTransformer.php`

**Acceptance Criteria:**
- [ ] Implementa AsyncAggregatorInterface
- [ ] Carga journalists por aliasId
- [ ] Tests unitarios

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 2-3 horas

**Dependencias:** BE-001, BE-012

---

### BE-017: Implementar SectionAggregator

**Descripción:** Migrar la lógica de SectionResolver al nuevo sistema.

**Archivos:**
- Crear: `src/Infrastructure/Aggregator/SectionAggregator.php`
- Crear: `src/Infrastructure/Transformer/SectionJsonTransformer.php`

**Acceptance Criteria:**
- [ ] Puede ser Sync o Async según necesidad
- [ ] Carga sección con site info

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 2 horas

**Dependencias:** BE-001, BE-012

---

### BE-018: Implementar BodyTagAggregator (Complejo)

**Descripción:** Implementar el agregador más complejo que procesa body elements con datos nested.

**Archivos:**
- Crear: `src/Infrastructure/Aggregator/BodyTagAggregator.php`
- Crear: `src/Infrastructure/Transformer/BodyTagJsonTransformer.php`
- Crear: `src/Infrastructure/Transformer/BodyElement/ParagraphTransformer.php`
- Crear: `src/Infrastructure/Transformer/BodyElement/SubHeadTransformer.php`
- Crear: `src/Infrastructure/Transformer/BodyElement/BodyTagPictureTransformer.php`
- (Más transformadores de body elements...)

**Implementación:**
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Aggregator;

use App\Domain\Aggregator\Contract\SyncAggregatorInterface;
use App\Domain\Aggregator\ValueObject\AggregatorContext;
use App\Domain\Aggregator\ValueObject\AggregatorResult;
use App\Infrastructure\Attribute\AsAggregator;

#[AsAggregator(name: 'bodyTag', priority: 100, dependencies: ['multimedia'])]
final class BodyTagAggregator implements SyncAggregatorInterface
{
    public function __construct(
        private readonly BodyElementTransformerHandler $transformerHandler
    ) {}

    public function getName(): string
    {
        return 'bodyTag';
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function getDependencies(): array
    {
        return ['multimedia']; // Needs multimedia resolved first
    }

    public function supports(AggregatorContext $context): bool
    {
        $rawData = $context->getRawData();
        return isset($rawData['body']['bodyElements']);
    }

    public function aggregate(AggregatorContext $context): AggregatorResult
    {
        $startTime = microtime(true);
        $bodyElements = $context->getRawData()['body']['bodyElements'] ?? [];
        $resolvedMultimedia = $context->getResolvedDataByKey('multimedia') ?? [];

        $transformedElements = [];
        foreach ($bodyElements as $index => $element) {
            try {
                $transformed = $this->transformerHandler->transform(
                    $element,
                    $resolvedMultimedia,
                    $context
                );
                $transformed['index'] = $index;
                $transformedElements[] = $transformed;
            } catch (\Throwable $e) {
                // Log error but continue with other elements
                continue;
            }
        }

        return AggregatorResult::success(
            $this->getName(),
            $transformedElements,
            microtime(true) - $startTime
        );
    }
}
```

**Acceptance Criteria:**
- [ ] Depende de multimedia (ejecuta después)
- [ ] Procesa todos los tipos de body elements
- [ ] Usa multimedia ya resuelto del contexto
- [ ] Graceful degradation en errores
- [ ] 20+ tests unitarios

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Infrastructure/Aggregator/BodyTagAggregatorTest.php
./vendor/bin/phpunit tests/Unit/Infrastructure/Transformer/BodyElement/
```

**Estimación:**
- Complejidad: L (Large)
- Tiempo: 6-8 horas

**Dependencias:** BE-001, BE-012, BE-015

---

### BE-019: Integrar con EditorialOrchestrator Existente

**Descripción:** Modificar el `EditorialOrchestrator` existente para usar el nuevo sistema de agregadores.

**Archivos:**
- Modificar: `src/Orchestrator/Chain/EditorialOrchestrator.php`

**Implementación:**
```php
<?php

// Modificar EditorialOrchestrator para usar OrchestrationPipeline

final class EditorialOrchestrator implements OrchestratorChain
{
    public function __construct(
        private readonly OrchestrationPipeline $orchestrationPipeline,
        private readonly OrchestrationContextFactory $contextFactory,
        // ... otros deps
    ) {}

    public function execute(Request $request, Editorial $editorial): array
    {
        // Crear contexto desde editorial
        $context = $this->contextFactory->createFromEditorial($editorial, $request);

        // Delegar al nuevo pipeline
        return $this->orchestrationPipeline->execute($context);
    }

    // ... resto de métodos legacy para compatibilidad
}
```

**Acceptance Criteria:**
- [ ] EditorialOrchestrator usa nuevo pipeline
- [ ] Tests de integración pasan
- [ ] API responses no cambian
- [ ] Backwards compatible

**Verificación:**
```bash
# Tests de integración
./vendor/bin/phpunit tests/Integration/

# Tests de contrato API
./vendor/bin/phpunit tests/Api/

# Comparar responses antes/después
curl http://localhost/api/editorials/4433 | diff - expected_response.json
```

**Estimación:**
- Complejidad: L (Large)
- Tiempo: 4-5 horas

**Dependencias:** BE-011, BE-014, BE-015, BE-016, BE-017, BE-018

---

## Resumen de Estimaciones

| Layer | Tareas | Tiempo Estimado |
|-------|--------|-----------------|
| Domain | 5 | ~6-8 horas |
| Application | 6 | ~18-24 horas |
| Infrastructure | 8 | ~22-28 horas |
| **Total** | **19** | **~46-60 horas (~5-7 días)** |

---

## Orden de Ejecución Recomendado

```
Semana 1:
├── BE-001: Contratos de Agregadores
├── BE-002: Value Objects
├── BE-003: Excepciones
├── BE-004: Eventos
├── BE-005: Contrato Transformadores
├── BE-006: AggregatorRegistry
└── BE-007: DependencyResolver

Semana 2:
├── BE-008: AggregatorExecutor
├── BE-009: TransformerRegistry
├── BE-010: TransformationPipeline
├── BE-011: OrchestrationPipeline
├── BE-012: PHP Attributes
└── BE-013: Compiler Passes

Semana 3:
├── BE-014: TagAggregator
├── BE-015: MultimediaAggregator
├── BE-016: JournalistAggregator
├── BE-017: SectionAggregator
├── BE-018: BodyTagAggregator
└── BE-019: Integración con EditorialOrchestrator
```

---

**Próximo paso**: 31_tasks_frontend.md (Tareas Frontend)
