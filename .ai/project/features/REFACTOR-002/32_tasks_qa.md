# Tareas QA - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Resumen

| Métrica | Valor |
|---------|-------|
| Total de tareas | 8 |
| Tests Unitarios | 2 tareas |
| Tests de Integración | 2 tareas |
| Tests de Regresión | 2 tareas |
| Tests de Performance | 2 tareas |
| Estimación total | ~2-3 días |

---

## Tests Unitarios

### QA-001: Tests de Domain Layer

**Descripción:** Validar que todos los componentes del Domain Layer funcionan correctamente.

**Archivos a crear:**
- `tests/Unit/Domain/Aggregator/ValueObject/AggregatorContextTest.php`
- `tests/Unit/Domain/Aggregator/ValueObject/AggregatorResultTest.php`
- `tests/Unit/Domain/Aggregator/Exception/AggregatorExceptionTest.php`
- `tests/Unit/Domain/Transformer/ValueObject/TransformationContextTest.php`

**Test Cases:**

```php
// AggregatorContextTest.php
class AggregatorContextTest extends TestCase
{
    /** @test */
    public function it_creates_context_with_required_data(): void
    {
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: ['title' => 'Test']
        );

        $this->assertEquals('4433', $context->getEditorialId());
        $this->assertEquals('news', $context->getEditorialType());
        $this->assertEquals(['title' => 'Test'], $context->getRawData());
    }

    /** @test */
    public function it_is_immutable_when_adding_resolved_data(): void
    {
        $original = new AggregatorContext('1', 'news', []);
        $modified = $original->withResolvedData('tags', ['tag1']);

        $this->assertNotSame($original, $modified);
        $this->assertEmpty($original->getResolvedData());
        $this->assertEquals(['tags' => ['tag1']], $modified->getResolvedData());
    }

    /** @test */
    public function it_returns_null_for_missing_resolved_key(): void
    {
        $context = new AggregatorContext('1', 'news', []);
        $this->assertNull($context->getResolvedDataByKey('nonexistent'));
    }

    /** @test */
    public function it_preserves_existing_data_when_adding_metadata(): void
    {
        $context = new AggregatorContext('1', 'news', ['a' => 1], ['b' => 2], ['c' => 3]);
        $modified = $context->withMetadata('d', 4);

        $this->assertEquals(['c' => 3, 'd' => 4], $modified->getMetadata());
    }
}
```

```php
// AggregatorResultTest.php
class AggregatorResultTest extends TestCase
{
    /** @test */
    public function it_creates_success_result(): void
    {
        $result = AggregatorResult::success('tag', ['data'], 0.05);

        $this->assertEquals('tag', $result->getAggregatorName());
        $this->assertEquals(['data'], $result->getData());
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getError());
        $this->assertEquals(0.05, $result->getExecutionTime());
    }

    /** @test */
    public function it_creates_failure_result(): void
    {
        $result = AggregatorResult::failure('tag', 'Timeout', [], 5.0);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Timeout', $result->getError());
        $this->assertEquals([], $result->getData()); // Fallback
    }
}
```

**Acceptance Criteria:**
- [ ] AggregatorContext: 5+ tests
- [ ] AggregatorResult: 4+ tests
- [ ] Excepciones: 3+ tests por tipo
- [ ] TransformationContext: 3+ tests
- [ ] Cobertura > 90%

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Domain/Aggregator/ --coverage-text

# Esperado:
# Classes: 100.00% (X/X)
# Methods: 100.00% (X/X)
# Lines:   > 90.00%
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 4-5 horas

**Dependencias:** BE-001 a BE-005 completados

---

### QA-002: Tests de Application Layer

**Descripción:** Validar registros, resolvedores y pipelines.

**Archivos a crear:**
- `tests/Unit/Application/Aggregator/AggregatorRegistryTest.php`
- `tests/Unit/Application/Aggregator/DependencyResolverTest.php`
- `tests/Unit/Application/Aggregator/AggregatorExecutorTest.php`
- `tests/Unit/Application/Transformer/TransformationPipelineTest.php`

**Test Cases:**

```php
// AggregatorRegistryTest.php
class AggregatorRegistryTest extends TestCase
{
    private AggregatorRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new AggregatorRegistry();
    }

    /** @test */
    public function it_registers_and_retrieves_aggregator(): void
    {
        $aggregator = $this->createMockAggregator('tag', 70);
        $this->registry->register($aggregator);

        $this->assertSame($aggregator, $this->registry->get('tag'));
    }

    /** @test */
    public function it_throws_on_duplicate_registration(): void
    {
        $this->expectException(DuplicateAggregatorException::class);

        $agg1 = $this->createMockAggregator('tag', 70);
        $agg2 = $this->createMockAggregator('tag', 80);

        $this->registry->register($agg1);
        $this->registry->register($agg2);
    }

    /** @test */
    public function it_throws_when_aggregator_not_found(): void
    {
        $this->expectException(AggregatorNotFoundException::class);
        $this->registry->get('nonexistent');
    }

    /** @test */
    public function it_filters_by_context_support(): void
    {
        $supported = $this->createMockAggregator('tag', 70, true);
        $notSupported = $this->createMockAggregator('multimedia', 90, false);

        $this->registry->register($supported);
        $this->registry->register($notSupported);

        $context = new AggregatorContext('1', 'news', []);
        $result = $this->registry->getForContext($context);

        $this->assertCount(1, $result);
        $this->assertEquals('tag', $result[0]->getName());
    }

    /** @test */
    public function it_sorts_by_priority_descending(): void
    {
        $low = $this->createMockAggregator('low', 10, true);
        $high = $this->createMockAggregator('high', 90, true);
        $medium = $this->createMockAggregator('medium', 50, true);

        $this->registry->register($low);
        $this->registry->register($high);
        $this->registry->register($medium);

        $context = new AggregatorContext('1', 'news', []);
        $result = $this->registry->getForContext($context);

        $this->assertEquals(['high', 'medium', 'low'], array_map(
            fn($a) => $a->getName(),
            $result
        ));
    }

    private function createMockAggregator(string $name, int $priority, bool $supports = true): AggregatorInterface
    {
        $mock = $this->createMock(AggregatorInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('getPriority')->willReturn($priority);
        $mock->method('supports')->willReturn($supports);
        $mock->method('getDependencies')->willReturn([]);
        return $mock;
    }
}
```

```php
// DependencyResolverTest.php
class DependencyResolverTest extends TestCase
{
    private DependencyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DependencyResolver();
    }

    /** @test */
    public function it_resolves_aggregators_without_dependencies(): void
    {
        $a = $this->mockAggregator('a', 50, []);
        $b = $this->mockAggregator('b', 70, []);

        $batches = $this->resolver->resolve([$a, $b]);

        $this->assertCount(1, $batches); // All in one batch
        $this->assertCount(2, $batches[0]);
    }

    /** @test */
    public function it_creates_separate_batches_for_dependencies(): void
    {
        $a = $this->mockAggregator('a', 50, []);
        $b = $this->mockAggregator('b', 70, ['a']); // b depends on a

        $batches = $this->resolver->resolve([$a, $b]);

        $this->assertCount(2, $batches);
        $this->assertEquals('a', $batches[0][0]->getName());
        $this->assertEquals('b', $batches[1][0]->getName());
    }

    /** @test */
    public function it_detects_simple_cycle(): void
    {
        $this->expectException(CircularDependencyException::class);

        $a = $this->mockAggregator('a', 50, ['b']);
        $b = $this->mockAggregator('b', 50, ['a']);

        $this->resolver->resolve([$a, $b]);
    }

    /** @test */
    public function it_detects_complex_cycle(): void
    {
        $this->expectException(CircularDependencyException::class);

        $a = $this->mockAggregator('a', 50, ['c']);
        $b = $this->mockAggregator('b', 50, ['a']);
        $c = $this->mockAggregator('c', 50, ['b']);

        $this->resolver->resolve([$a, $b, $c]);
    }

    /** @test */
    public function it_throws_when_dependency_not_found(): void
    {
        $this->expectException(AggregatorNotFoundException::class);

        $a = $this->mockAggregator('a', 50, ['nonexistent']);

        $this->resolver->resolve([$a]);
    }

    /** @test */
    public function it_sorts_within_batch_by_priority(): void
    {
        $a = $this->mockAggregator('a', 30, []);
        $b = $this->mockAggregator('b', 90, []);
        $c = $this->mockAggregator('c', 60, []);

        $batches = $this->resolver->resolve([$a, $b, $c]);

        $names = array_map(fn($agg) => $agg->getName(), $batches[0]);
        $this->assertEquals(['b', 'c', 'a'], $names);
    }
}
```

**Acceptance Criteria:**
- [ ] AggregatorRegistry: 8+ tests
- [ ] DependencyResolver: 10+ tests
- [ ] AggregatorExecutor: 12+ tests
- [ ] TransformationPipeline: 6+ tests
- [ ] Cobertura > 85%

**Verificación:**
```bash
./vendor/bin/phpunit tests/Unit/Application/ --coverage-text
```

**Estimación:**
- Complejidad: L (Large)
- Tiempo: 6-8 horas

**Dependencias:** BE-006 a BE-011 completados

---

## Tests de Integración

### QA-003: Tests de Agregadores Concretos

**Descripción:** Validar que los agregadores concretos funcionan con clientes HTTP mockeados.

**Archivos a crear:**
- `tests/Integration/Aggregator/TagAggregatorTest.php`
- `tests/Integration/Aggregator/MultimediaAggregatorTest.php`
- `tests/Integration/Aggregator/BodyTagAggregatorTest.php`

**Test Cases:**

```php
// TagAggregatorTest.php
class TagAggregatorTest extends KernelTestCase
{
    /** @test */
    public function it_aggregates_tags_from_client(): void
    {
        $mockClient = $this->createMock(QueryTagClientInterface::class);
        $mockClient->method('findTagByIdAsync')
            ->willReturnCallback(fn($id) => Utils::fulfilled(new Tag($id, "Tag $id")));

        $aggregator = new TagAggregator($mockClient);
        $context = new AggregatorContext('1', 'news', ['tags' => ['t1', 't2']]);

        $promise = $aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_returns_empty_array_when_no_tags(): void
    {
        $mockClient = $this->createMock(QueryTagClientInterface::class);
        $aggregator = new TagAggregator($mockClient);
        $context = new AggregatorContext('1', 'news', ['tags' => []]);

        $promise = $aggregator->aggregate($context);
        $result = $promise->wait();

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_does_not_support_context_without_tags(): void
    {
        $mockClient = $this->createMock(QueryTagClientInterface::class);
        $aggregator = new TagAggregator($mockClient);
        $context = new AggregatorContext('1', 'news', []);

        $this->assertFalse($aggregator->supports($context));
    }

    /** @test */
    public function it_handles_client_failure_gracefully(): void
    {
        $mockClient = $this->createMock(QueryTagClientInterface::class);
        $mockClient->method('findTagByIdAsync')
            ->willReturn(Utils::rejected(new \Exception('Service unavailable')));

        $aggregator = new TagAggregator($mockClient);
        $context = new AggregatorContext('1', 'news', ['tags' => ['t1']]);

        $promise = $aggregator->aggregate($context);

        // Should not throw, should use fallback
        $this->expectException(\Exception::class);
        $promise->wait();
    }
}
```

**Acceptance Criteria:**
- [ ] TagAggregator: 5+ tests
- [ ] MultimediaAggregator: 6+ tests
- [ ] BodyTagAggregator: 8+ tests (más complejo)
- [ ] Tests cubren casos de error

**Verificación:**
```bash
./vendor/bin/phpunit tests/Integration/Aggregator/
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 4-5 horas

**Dependencias:** BE-014 a BE-018 completados

---

### QA-004: Tests del Pipeline Completo

**Descripción:** Validar el flujo completo de orquestación.

**Archivos a crear:**
- `tests/Integration/Orchestration/OrchestrationPipelineTest.php`

**Test Cases:**

```php
// OrchestrationPipelineTest.php
class OrchestrationPipelineTest extends KernelTestCase
{
    private OrchestrationPipeline $pipeline;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->pipeline = self::getContainer()->get(OrchestrationPipeline::class);
    }

    /** @test */
    public function it_executes_full_pipeline(): void
    {
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: [
                'title' => 'Test Article',
                'tags' => ['tag1'],
                'body' => ['bodyElements' => []],
                'signatures' => [],
            ]
        );

        $result = $this->pipeline->execute($context);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    /** @test */
    public function it_handles_aggregator_failure(): void
    {
        // Configure un agregador para fallar
        $context = new AggregatorContext(
            editorialId: '4433',
            editorialType: 'news',
            rawData: [
                'tags' => ['nonexistent-tag'],
            ]
        );

        $result = $this->pipeline->execute($context);

        // Debe completar con fallback values
        $this->assertIsArray($result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertEquals([], $result['tags']); // Fallback
    }

    /** @test */
    public function it_dispatches_orchestration_completed_event(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(OrchestrationCompletedEvent::class));

        // Inyectar mock...
    }
}
```

**Acceptance Criteria:**
- [ ] Pipeline completo funciona
- [ ] Errores se manejan gracefully
- [ ] Eventos se disparan correctamente
- [ ] Respuesta tiene estructura esperada

**Verificación:**
```bash
./vendor/bin/phpunit tests/Integration/Orchestration/
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** BE-011, BE-019 completados

---

## Tests de Regresión

### QA-005: Tests de Compatibilidad de API

**Descripción:** Verificar que las respuestas de API no cambian.

**Archivos a crear:**
- `tests/Regression/EditorialResponseTest.php`
- `tests/fixtures/expected_editorial_4433.json`

**Test Cases:**

```php
// EditorialResponseTest.php
class EditorialResponseTest extends WebTestCase
{
    /** @test */
    public function editorial_response_structure_unchanged(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/editorials/4433');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        // Verificar estructura
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('section', $data);
        $this->assertArrayHasKey('multimedia', $data);
        $this->assertArrayHasKey('tags', $data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('signatures', $data);
    }

    /** @test */
    public function editorial_response_matches_expected_fixture(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/editorials/4433');
        $actual = json_decode($client->getResponse()->getContent(), true);

        $expected = json_decode(
            file_get_contents(__DIR__ . '/../fixtures/expected_editorial_4433.json'),
            true
        );

        // Comparar campos críticos (ignorar timestamps)
        $this->assertEquals($expected['id'], $actual['id']);
        $this->assertEquals($expected['title'], $actual['title']);
        $this->assertEquals(
            array_column($expected['tags'], 'id'),
            array_column($actual['tags'], 'id')
        );
    }

    /** @test */
    public function all_body_element_types_render_correctly(): void
    {
        // Test con editorial que tiene todos los tipos de body elements
        $client = static::createClient();
        $client->request('GET', '/api/v1/editorials/test-all-body-types');

        $data = json_decode($client->getResponse()->getContent(), true);
        $bodyTypes = array_column($data['body'], 'type');

        $expectedTypes = [
            'paragraph',
            'subHead',
            'picture',
            'video',
            'list',
            'html',
        ];

        foreach ($expectedTypes as $type) {
            $this->assertContains($type, $bodyTypes, "Missing body type: $type");
        }
    }
}
```

**Acceptance Criteria:**
- [ ] Estructura de respuesta no cambia
- [ ] Todos los campos requeridos presentes
- [ ] Tipos de body elements funcionan
- [ ] Fixtures actualizados si es necesario

**Verificación:**
```bash
./vendor/bin/phpunit tests/Regression/

# Comparar con snapshot
php bin/console app:compare-api-snapshot 4433
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** BE-019 completado, Deploy a staging

---

### QA-006: Tests de Contrato con Clientes

**Descripción:** Validar que los contratos con aplicaciones móviles se mantienen.

**Archivos a crear:**
- `tests/Contract/MobileAppContractTest.php`

**Test Cases:**

```php
class MobileAppContractTest extends WebTestCase
{
    private array $requiredFields = [
        'id',
        'title',
        'headline',
        'url',
        'publishedAt',
        'section' => ['id', 'name', 'url'],
        'multimedia' => ['main'],
        'tags',
        'body',
        'signatures',
    ];

    /** @test */
    public function response_contains_all_required_fields(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/editorials/4433');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertContractFields($data, $this->requiredFields);
    }

    /** @test */
    public function field_types_are_correct(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/editorials/4433');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsString($data['id']);
        $this->assertIsString($data['title']);
        $this->assertIsArray($data['tags']);
        $this->assertIsArray($data['body']);

        foreach ($data['tags'] as $tag) {
            $this->assertIsString($tag['id']);
            $this->assertIsString($tag['name']);
        }
    }

    private function assertContractFields(array $data, array $fields, string $path = ''): void
    {
        foreach ($fields as $key => $value) {
            $fieldPath = $path ? "$path.$key" : $key;

            if (is_array($value)) {
                $this->assertArrayHasKey($key, $data, "Missing: $fieldPath");
                $this->assertContractFields($data[$key], $value, $fieldPath);
            } else {
                $this->assertArrayHasKey($value, $data, "Missing: $path.$value");
            }
        }
    }
}
```

**Acceptance Criteria:**
- [ ] Todos los campos requeridos presentes
- [ ] Tipos de datos correctos
- [ ] No hay campos removidos

**Verificación:**
```bash
./vendor/bin/phpunit tests/Contract/
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 2-3 horas

**Dependencias:** QA-005 completado

---

## Tests de Performance

### QA-007: Benchmarks de Agregación

**Descripción:** Medir y comparar tiempos de ejecución antes/después del refactoring.

**Archivos a crear:**
- `tests/Performance/AggregationBenchmarkTest.php`

**Test Cases:**

```php
class AggregationBenchmarkTest extends KernelTestCase
{
    /** @test */
    public function parallel_aggregation_is_faster_than_sequential(): void
    {
        $pipeline = self::getContainer()->get(OrchestrationPipeline::class);

        $context = $this->createTestContext();

        // Warm up
        $pipeline->execute($context);

        // Measure
        $times = [];
        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);
            $pipeline->execute($context);
            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);

        // Assert under threshold
        $this->assertLessThan(0.5, $avgTime, "Average time: {$avgTime}s exceeds 500ms");
    }

    /** @test */
    public function aggregation_overhead_is_minimal(): void
    {
        // Medir overhead de la nueva arquitectura
        $executor = self::getContainer()->get(AggregatorExecutor::class);

        $context = new AggregatorContext('1', 'news', []);
        $emptyAggregators = [];

        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $executor->execute($context);
        }
        $totalTime = microtime(true) - $start;

        $avgOverhead = $totalTime / 100;

        // Overhead should be < 1ms
        $this->assertLessThan(0.001, $avgOverhead);
    }

    private function createTestContext(): AggregatorContext
    {
        return new AggregatorContext(
            '4433',
            'news',
            [
                'tags' => ['t1', 't2', 't3'],
                'body' => ['bodyElements' => [
                    ['type' => 'paragraph', 'text' => 'Test'],
                ]],
                'signatures' => [['journalistId' => 'j1']],
                'multimedia' => ['mainPhotoId' => 'p1'],
            ]
        );
    }
}
```

**Acceptance Criteria:**
- [ ] Tiempo promedio < 500ms
- [ ] Overhead de arquitectura < 5ms
- [ ] Paralelo más rápido que secuencial

**Verificación:**
```bash
./vendor/bin/phpunit tests/Performance/ --testdox
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** BE-019 completado

---

### QA-008: Tests de Carga

**Descripción:** Validar comportamiento bajo carga.

**Archivos a crear:**
- `tests/Load/ApiLoadTest.php`
- Script de carga (usando Artillery, k6, o similar)

**Test Cases:**

```yaml
# load-test.yml (Artillery)
config:
  target: 'http://localhost:8000'
  phases:
    - duration: 60
      arrivalRate: 10
    - duration: 60
      arrivalRate: 50
    - duration: 60
      arrivalRate: 100

scenarios:
  - name: "Get Editorial"
    flow:
      - get:
          url: "/api/v1/editorials/4433"
          expect:
            - statusCode: 200
            - contentType: application/json
```

```bash
#!/bin/bash
# run-load-test.sh

echo "Running load test..."
artillery run load-test.yml --output report.json

echo "Generating report..."
artillery report report.json --output report.html

echo "Checking thresholds..."
# p95 latency should be < 1s
p95=$(jq '.aggregate.latency.p95' report.json)
if (( $(echo "$p95 > 1000" | bc -l) )); then
    echo "FAIL: p95 latency ($p95 ms) exceeds 1000ms"
    exit 1
fi

echo "PASS: All thresholds met"
```

**Acceptance Criteria:**
- [ ] p95 latency < 1000ms bajo 100 req/s
- [ ] Error rate < 1%
- [ ] No memory leaks

**Verificación:**
```bash
./tests/Load/run-load-test.sh
```

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 3-4 horas

**Dependencias:** Deploy a staging

---

## Resumen de Estimaciones

| Tarea | Tiempo |
|-------|--------|
| QA-001: Tests Domain Layer | 4-5 horas |
| QA-002: Tests Application Layer | 6-8 horas |
| QA-003: Tests Agregadores | 4-5 horas |
| QA-004: Tests Pipeline | 3-4 horas |
| QA-005: Tests Regresión | 3-4 horas |
| QA-006: Tests Contrato | 2-3 horas |
| QA-007: Benchmarks | 3-4 horas |
| QA-008: Tests Carga | 3-4 horas |
| **Total** | **~28-37 horas (~3-4 días)** |

---

## Cobertura de Tests Esperada

| Componente | Cobertura Mínima |
|------------|------------------|
| Domain Layer | 95% |
| Application Layer | 85% |
| Infrastructure/Aggregators | 80% |
| Infrastructure/Transformers | 75% |
| **Total Proyecto** | **80%** |

---

## Orden de Ejecución

```
Durante desarrollo (paralelo con Backend):
├── QA-001: Tests Domain Layer
└── QA-002: Tests Application Layer

Después de implementación:
├── QA-003: Tests Agregadores
├── QA-004: Tests Pipeline
├── QA-005: Tests Regresión
└── QA-006: Tests Contrato

Después de deploy a staging:
├── QA-007: Benchmarks
└── QA-008: Tests Carga
```

---

**Próximo paso**: 35_dependencies.md (Mapa de Dependencias)
