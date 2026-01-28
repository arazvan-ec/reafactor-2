# Tareas QA - REFACTOR-001

> **Proyecto:** SNAAPI Refactoring
> **Fecha:** 2026-01-28
> **Total estimado:** 8 tareas, ~2-3 días

---

## Resumen

Las tareas de QA se centran en **validar que la refactorización no introduce regresiones** y que los nuevos componentes funcionan correctamente.

---

## QA-001: Validar ImageSizesRegistry

**Descripción:** Verificar que ImageSizesRegistry devuelve los mismos valores que las constantes originales.

**Precondiciones:**
- BE-001 completada
- Tests de BE-001 pasando

**Casos de Test:**

| ID | Caso | Input | Expected | Status |
|----|------|-------|----------|--------|
| QA-001-1 | Ratio 4:3 tiene 11 tamaños | `getSizesForRatio('4:3')` | count = 11 | ⏳ |
| QA-001-2 | Ratio 16:9 tiene 10 tamaños | `getSizesForRatio('16:9')` | count = 10 | ⏳ |
| QA-001-3 | 1440w en 4:3 = 1440x1080 | `getSize('4:3', '1440w')` | [1440, 1080] | ⏳ |
| QA-001-4 | 1920w en 16:9 = 1920x1080 | `getSize('16:9', '1920w')` | [1920, 1080] | ⏳ |
| QA-001-5 | Ratio inválido lanza excepción | `getSizesForRatio('invalid')` | Exception | ⏳ |
| QA-001-6 | Todos los ratios tienen aspect correcto | Loop all sizes | Ratio matches | ⏳ |

**Comandos de verificación:**
```bash
# Ejecutar tests específicos
php bin/phpunit tests/Infrastructure/Registry/ImageSizesRegistryTest.php --testdox

# Verificar cobertura
php bin/phpunit tests/Infrastructure/Registry/ --coverage-text
```

**Acceptance Criteria:**
- [ ] Todos los tests pasan
- [ ] Cobertura > 95%
- [ ] Valores idénticos a constantes originales

**Estimación:** 2 horas

---

## QA-002: Validar Exception Hierarchy

**Descripción:** Verificar que las excepciones se comportan correctamente.

**Casos de Test:**

| ID | Caso | Input | Expected |
|----|------|-------|----------|
| QA-002-1 | InvalidAspectRatioException tiene context | create('bad', [...]) | context['provided_ratio'] = 'bad' |
| QA-002-2 | EditorialNotFoundException tiene ID | withId('123') | context['editorial_id'] = '123' |
| QA-002-3 | ServiceUnavailableException tiene servicio | forService('test') | context['service_name'] = 'test' |
| QA-002-4 | Todas heredan de SnaApiException | instanceof check | true |

**Comandos de verificación:**
```bash
php bin/phpunit tests/Domain/Exception/ --testdox
```

**Acceptance Criteria:**
- [ ] Todas las excepciones tienen factory methods
- [ ] Context array funciona
- [ ] toArray() serializa correctamente

**Estimación:** 1 hora

---

## QA-003: Validar Resolvers Individualmente

**Descripción:** Probar cada resolver de forma aislada con mocks.

**Precondiciones:**
- BE-006 a BE-011 completadas

**Casos de Test por Resolver:**

### SectionResolver
| ID | Caso | Expected |
|----|------|----------|
| QA-003-1 | Editorial sin sectionId no resuelve | context.section = null |
| QA-003-2 | Editorial con sectionId resuelve | context.section = section |
| QA-003-3 | Error de cliente se loguea | logger.warning called |

### MultimediaResolver
| ID | Caso | Expected |
|----|------|----------|
| QA-003-4 | Editorial sin multimedia IDs no resuelve | context.multimedia = [] |
| QA-003-5 | Editorial con multimediaId resuelve main | context.multimedia['main'] exists |
| QA-003-6 | Editorial con openingMultimediaId resuelve opening | context.multimedia['opening'] exists |
| QA-003-7 | Error parcial continúa con otros | Partial results |

### JournalistResolver
| ID | Caso | Expected |
|----|------|----------|
| QA-003-8 | Editorial sin journalistIds no resuelve | context.journalists = [] |
| QA-003-9 | Editorial con journalistIds resuelve | context.journalists populated |
| QA-003-10 | Factory crea objetos correctos | JournalistDTO instances |

### TagResolver
| ID | Caso | Expected |
|----|------|----------|
| QA-003-11 | Editorial sin tagIds no resuelve | context.tags = [] |
| QA-003-12 | Editorial con tagIds resuelve | context.tags populated |

**Comandos de verificación:**
```bash
php bin/phpunit tests/Orchestrator/Resolver/ --testdox
```

**Acceptance Criteria:**
- [ ] Cada resolver tiene 5+ tests
- [ ] Cobertura > 90% por resolver
- [ ] Mocks verifican interacciones

**Estimación:** 4 horas

---

## QA-004: Validar ResolverRegistry

**Descripción:** Verificar que el registry ordena y filtra correctamente.

**Casos de Test:**

| ID | Caso | Expected |
|----|------|----------|
| QA-004-1 | Resolvers se ordenan por prioridad descendente | High(100) > Med(50) > Low(10) |
| QA-004-2 | getResolversFor filtra por supports | Only supported resolvers |
| QA-004-3 | Registry vacío retorna array vacío | [] |
| QA-004-4 | Mismo prioridad mantiene orden de inserción | FIFO |

**Comandos de verificación:**
```bash
php bin/phpunit tests/Orchestrator/Resolver/ResolverRegistryTest.php --testdox
```

**Estimación:** 1 hora

---

## QA-005: Validar URLGenerationService

**Descripción:** Verificar que las URLs generadas son idénticas a las originales.

**Casos de Test:**

| ID | Caso | Input | Expected URL |
|----|------|-------|--------------|
| QA-005-1 | Editorial URL normal | editorial, section www | `https://www.site.com/path` |
| QA-005-2 | Editorial URL blog | editorial, section blog | `https://blog.site.com/path` |
| QA-005-3 | Section URL | section | `https://www.site.com/section-path` |
| QA-005-4 | Tag URL | tag, section | `https://www.site.com/tag/tag-path` |
| QA-005-5 | Journalist URL | journalist, section | `https://www.site.com/autor/alias` |
| QA-005-6 | Path con leading slash | `/path` | Slash removido |
| QA-005-7 | Path sin leading slash | `path` | OK |

**Comandos de verificación:**
```bash
php bin/phpunit tests/Infrastructure/Service/URLGenerationServiceTest.php --testdox
```

**Acceptance Criteria:**
- [ ] URLs idénticas a implementación original
- [ ] Maneja edge cases (paths vacíos, slashes)
- [ ] Cobertura 100%

**Estimación:** 2 horas

---

## QA-006: Validar EditorialOrchestratorFacade

**Descripción:** Test de integración del Facade completo.

**Casos de Test:**

| ID | Caso | Expected |
|----|------|----------|
| QA-006-1 | Request válido retorna response completo | All fields populated |
| QA-006-2 | Resolver falla, otros continúan | Partial response |
| QA-006-3 | Todos los resolvers se ejecutan en orden | Priority order |
| QA-006-4 | Context se pasa correctamente | Data flows through |
| QA-006-5 | Response estructura correcta | JSON schema valid |

**Comandos de verificación:**
```bash
php bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorFacadeTest.php --testdox
```

**Acceptance Criteria:**
- [ ] Facade orquesta todos los resolvers
- [ ] Response idéntica a orquestador legacy
- [ ] Logging funciona correctamente

**Estimación:** 3 horas

---

## QA-007: Test de Regresión End-to-End

**Descripción:** Comparar output de Facade vs Legacy orquestador.

**Metodología:**
1. Seleccionar 100 editoriales de producción
2. Ejecutar ambos orquestadores
3. Comparar JSON responses
4. Documentar diferencias (si hay)

**Casos de Test:**

| ID | Tipo Editorial | Expected |
|----|----------------|----------|
| QA-007-1 | Editorial simple | 100% match |
| QA-007-2 | Editorial con video | 100% match |
| QA-007-3 | Editorial con galería | 100% match |
| QA-007-4 | Editorial con inserted news | 100% match |
| QA-007-5 | Editorial con recommended | 100% match |
| QA-007-6 | Editorial membership | 100% match |

**Script de comparación:**
```php
<?php
// tests/E2E/OrchestratorComparisonTest.php

final class OrchestratorComparisonTest extends TestCase
{
    private const EDITORIAL_IDS = [
        'editorial-1',
        'editorial-2',
        // ... 100 IDs
    ];

    public function testOutputsMatch(): void
    {
        foreach (self::EDITORIAL_IDS as $id) {
            $legacy = $this->legacyOrchestrator->execute($this->createRequest($id));
            $new = $this->facadeOrchestrator->execute($this->createRequest($id));

            $this->assertEquals(
                $this->normalize($legacy),
                $this->normalize($new),
                "Output mismatch for editorial: {$id}"
            );
        }
    }

    private function normalize(array $data): array
    {
        // Normalizar timestamps, ordenar arrays, etc.
        ksort($data);
        return $data;
    }
}
```

**Acceptance Criteria:**
- [ ] 100% match en 100 editoriales
- [ ] Documentar cualquier diferencia intencional
- [ ] Performance igual o mejor

**Estimación:** 4 horas

---

## QA-008: Validar Métricas y Performance

**Descripción:** Verificar que no hay regresión de performance.

**Métricas a Validar:**

| Métrica | Baseline | Target | Method |
|---------|----------|--------|--------|
| Response time p50 | 120ms | ≤120ms | NewRelic/Datadog |
| Response time p95 | 180ms | ≤200ms | NewRelic/Datadog |
| Memory usage | 32MB | ≤32MB | memory_get_peak_usage() |
| Error rate | 0.1% | ≤0.1% | Logs |

**Script de benchmark:**
```php
<?php
// tests/Performance/OrchestratorBenchmarkTest.php

final class OrchestratorBenchmarkTest extends TestCase
{
    public function testResponseTimeP95(): void
    {
        $times = [];

        for ($i = 0; $i < 1000; $i++) {
            $start = microtime(true);
            $this->orchestrator->execute($this->createRequest());
            $times[] = (microtime(true) - $start) * 1000;
        }

        sort($times);
        $p95 = $times[(int) (count($times) * 0.95)];

        $this->assertLessThanOrEqual(200, $p95, "P95 response time exceeds 200ms");
    }

    public function testMemoryUsage(): void
    {
        $baseline = memory_get_usage();

        for ($i = 0; $i < 100; $i++) {
            $this->orchestrator->execute($this->createRequest());
        }

        $peak = memory_get_peak_usage() - $baseline;
        $perRequest = $peak / 100;

        $this->assertLessThanOrEqual(
            32 * 1024 * 1024,
            $perRequest,
            "Memory usage exceeds 32MB per request"
        );
    }
}
```

**Acceptance Criteria:**
- [ ] Response time no regresa
- [ ] Memory usage no aumenta
- [ ] Error rate no aumenta

**Estimación:** 3 horas

---

## Resumen de Tareas QA

| ID | Tarea | Horas | Dependencias |
|----|-------|-------|--------------|
| QA-001 | ImageSizesRegistry | 2 | BE-001 |
| QA-002 | Exception Hierarchy | 1 | BE-002 |
| QA-003 | Resolvers Individuales | 4 | BE-006 a BE-011 |
| QA-004 | ResolverRegistry | 1 | BE-005 |
| QA-005 | URLGenerationService | 2 | BE-012 |
| QA-006 | EditorialOrchestratorFacade | 3 | BE-015 |
| QA-007 | Test Regresión E2E | 4 | BE-019 |
| QA-008 | Métricas y Performance | 3 | BE-019 |

**Total: 8 tareas, ~20 horas (~2-3 días)**

---

## Checklist Final de QA

### Antes de Merge
- [ ] Todos los tests unitarios pasan
- [ ] Todos los tests de integración pasan
- [ ] PHPStan level 9 sin errores
- [ ] PHP-CS-Fixer sin cambios
- [ ] Mutation testing MSI ≥86%
- [ ] Container lint pasa
- [ ] YAML lint pasa

### Antes de Deploy
- [ ] Tests E2E comparativos pasan
- [ ] Benchmark de performance pasa
- [ ] Feature flag configurado
- [ ] Rollback plan documentado
- [ ] Monitoring dashboards listos

### Post-Deploy (Feature Flag 1%)
- [ ] Error rate ≤0.1%
- [ ] Response time p95 ≤200ms
- [ ] No errores nuevos en logs
- [ ] Métricas de negocio estables

### Post-Deploy (Feature Flag 100%)
- [ ] 24h sin incidentes
- [ ] Métricas estables
- [ ] Legacy code marcado deprecated
- [ ] Documentación actualizada

---

**Documento creado por:** Planner/Architect
**Fecha:** 2026-01-28
**Versión:** 1.0
