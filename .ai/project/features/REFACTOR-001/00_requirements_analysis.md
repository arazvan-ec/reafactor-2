# Análisis de Requisitos - REFACTOR-001

> **Proyecto:** SNAAPI (Symfony News API)
> **Tipo:** Refactorización para Clean Code, SOLID y Escalabilidad
> **Fecha:** 2026-01-28
> **Estado:** EN PROGRESO

---

## Resumen Ejecutivo

### ¿Qué problema resuelve este feature?

El codebase de SNAAPI presenta **deuda técnica significativa** que afecta:
- **Mantenibilidad:** Clases con 500+ líneas son difíciles de modificar
- **Testabilidad:** 19 dependencias en una clase requiere 19 mocks por test
- **Escalabilidad:** Agregar nuevos tipos de contenido requiere modificar clases existentes
- **Productividad:** Developers nuevos tardan semanas en entender EditorialOrchestrator

### ¿Para quién es?

- **Desarrolladores backend** que mantienen y extienden SNAAPI
- **DevOps** que monitorizan y escalan el servicio
- **QA** que necesita tests más granulares y rápidos

### ¿Cuál es el valor de negocio?

| Métrica | Antes | Después | Impacto |
|---------|-------|---------|---------|
| Tiempo de onboarding | ~2 semanas | ~3 días | -75% |
| Tiempo de implementar nuevo tipo | ~5 días | ~1 día | -80% |
| Tiempo de debugging | ~4 horas | ~1 hora | -75% |
| Cobertura de tests unitarios | ~70% | ~90% | +20% |
| Complejidad ciclomática máx | 45 | <15 | -67% |

---

## Requisitos Funcionales

### RF-001: Mantener comportamiento actual
El sistema debe seguir funcionando **exactamente igual** después de la refactorización.
- No cambios en API contracts
- No cambios en respuestas JSON
- No cambios en comportamiento de negocio

### RF-002: Aplicar Single Responsibility Principle (SRP)
Cada clase debe tener **una única razón para cambiar**.
- EditorialOrchestrator → máximo 5 dependencias (actualmente 19)
- Métodos → máximo 30 líneas (actualmente algunos tienen 180)
- Clases → máximo 200 líneas (actualmente hasta 536)

### RF-003: Eliminar código duplicado (DRY)
- SIZES_RELATIONS debe existir en **1 solo lugar** (actualmente en 3)
- Lógica de generación de URLs centralizada
- Transformación de loops extraída a patrones reutilizables

### RF-004: Aplicar Open/Closed Principle (OCP)
- Agregar nuevo tipo de multimedia **sin modificar** código existente
- Agregar nuevo tipo de editorial **sin modificar** código existente
- Usar Strategy pattern + Registry para extensibilidad

### RF-005: Aplicar Dependency Inversion Principle (DIP)
- Depender de **abstracciones** (interfaces), no implementaciones
- Crear interfaces para Resolvers, Transformers, Repositories
- Inyectar abstracciones, no clases concretas

### RF-006: Mejorar manejo de errores
- Excepciones tipadas y específicas (no `\Throwable` genérico)
- Logging contextual con información útil
- No tragar excepciones silenciosamente

### RF-007: Aplicar patrones de diseño apropiados
- **Facade Pattern:** Para EditorialOrchestrator
- **Strategy Pattern:** Mantener y mejorar en DataTransformers
- **Registry Pattern:** Para resolvers dinámicos
- **Builder Pattern:** Para construcción de respuestas complejas

---

## Requisitos No Funcionales

### RNF-001: Performance
- Tiempo de respuesta API: **mantener <200ms** (sin regresión)
- Uso de memoria: **mantener igual o reducir**
- No agregar overhead por abstracciones

### RNF-002: Testabilidad
- Cobertura de tests unitarios: **≥90%**
- Mutation Score Indicator (MSI): **≥86%**
- Tests ejecutables en **<30 segundos**

### RNF-003: Compatibilidad
- PHP **≥8.1** (mantener)
- Symfony **6.4** (mantener)
- PSR-12 + Symfony coding standards

### RNF-004: Incremental
- Cada refactorización debe ser **deployable independientemente**
- Sin big-bang refactors
- Backwards compatible en cada paso

### RNF-005: Documentación
- Cada nueva interface documentada con PHPDoc
- Actualizar CLAUDE.md con nuevos patrones
- README con ejemplos de extensión

---

## Entidades/Componentes a Refactorizar

### Prioridad CRÍTICA (Sprint 1)

| Componente | Problema | Solución |
|------------|----------|----------|
| `EditorialOrchestrator` | 536 líneas, 19 deps, God Class | Facade + 5-6 Resolvers |
| `SIZES_RELATIONS` | Duplicado 3x | `ImageSizesRegistry` |

### Prioridad ALTA (Sprint 2)

| Componente | Problema | Solución |
|------------|----------|----------|
| `DetailsMultimediaPhotoDataTransformer` | 350 líneas | Extraer constantes, dividir |
| `DetailsAppsDataTransformer` | 211 líneas, URLs repetidas | `URLGenerationService` |
| `DetailsMultimediaDataTransformer` | 229 líneas | Aplicar Strategy |

### Prioridad MEDIA (Sprint 3)

| Componente | Problema | Solución |
|------------|----------|----------|
| `RecommendedEditorialsDataTransformer` | 170 líneas | Builder pattern |
| Excepciones genéricas | `\Throwable` everywhere | Exception hierarchy |
| Promises handling | Código repetido | `PromiseResolver` |

---

## Reglas de Negocio

### RN-001: Inmutabilidad de API
La API pública **NO debe cambiar**. Cualquier cambio interno debe ser transparente para los consumidores.

### RN-002: Retrocompatibilidad de Tests
Los tests existentes **deben seguir pasando** sin modificación (excepto imports).

### RN-003: Feature Flags
Usar feature flags para nuevas implementaciones:
```php
if ($this->featureFlags->isEnabled('new_orchestrator')) {
    return $newOrchestrator->execute($request);
}
return $legacyOrchestrator->execute($request);
```

### RN-004: No Mixed Refactoring
Cada PR debe tener **un solo objetivo**:
- ❌ Refactor + Fix bug + Add feature
- ✅ Solo refactor de EditorialOrchestrator
- ✅ Solo extracción de ImageSizesRegistry

### RN-005: Preservar Async Behavior
Las llamadas asíncronas con Promises deben mantener el mismo comportamiento paralelo.

---

## Casos de Uso de Refactorización

### UC-001: Extraer ImageSizesRegistry
**Actor:** Developer
**Precondición:** SIZES_RELATIONS existe en 3 lugares
**Flujo:**
1. Crear `ImageSizesRegistry` con constantes centralizadas
2. Refactorizar `DetailsMultimediaPhotoDataTransformer`
3. Refactorizar `PictureShots`
4. Refactorizar `MultimediaTrait`
5. Eliminar constantes duplicadas
6. Verificar tests pasan

**Postcondición:** SIZES_RELATIONS en 1 solo lugar

### UC-002: Refactorizar EditorialOrchestrator con Facade
**Actor:** Developer
**Precondición:** EditorialOrchestrator tiene 19 dependencias
**Flujo:**
1. Crear interfaces para cada resolver
2. Crear `MultimediaResolver` implementando interface
3. Crear `JournalistResolver` implementando interface
4. Crear `TagResolver` implementando interface
5. Crear `InsertedNewsResolver` implementando interface
6. Crear `RecommendedNewsResolver` implementando interface
7. Crear `EditorialOrchestratorFacade` que orquesta resolvers
8. Actualizar DI configuration
9. Deprecar `EditorialOrchestrator` legacy
10. Migrar usando feature flag

**Postcondición:** EditorialOrchestrator con 5-6 dependencias

### UC-003: Extraer URLGenerationService
**Actor:** Developer
**Precondición:** URL generation repetida en 3+ lugares
**Flujo:**
1. Crear `URLGenerationService` con métodos específicos
2. Inyectar en `DetailsAppsDataTransformer`
3. Eliminar `UrlGeneratorTrait`
4. Actualizar tests

**Postcondición:** URL generation centralizada

### UC-004: Crear Exception Hierarchy
**Actor:** Developer
**Precondición:** `\Throwable` usado genéricamente
**Flujo:**
1. Crear `SnaApiException` base
2. Crear excepciones específicas
3. Refactorizar catch blocks
4. Actualizar PHPDoc

**Postcondición:** Excepciones tipadas y específicas

---

## Acceptance Criteria

### AC-001: Tests Existentes Pasan
```bash
make test
# Expected: 100% tests passing
# No modifications to existing test assertions
```

### AC-002: PHPStan Level 9
```bash
vendor/bin/phpstan analyse --level=9
# Expected: 0 errors
```

### AC-003: Mutation Testing
```bash
make test_infection
# Expected: MSI ≥86%
```

### AC-004: No Regresión de Performance
```bash
# Benchmark antes y después
# Response time: ≤200ms (p95)
# Memory: ≤mismo consumo
```

### AC-005: Cobertura de Tests
```bash
make test_coverage
# Expected: ≥90% line coverage
```

### AC-006: Complejidad Reducida
```bash
vendor/bin/phpmetrics src/
# Expected:
# - Max cyclomatic complexity: <15 (era 45)
# - Max class lines: <200 (era 536)
# - Max method lines: <30 (era 180)
```

---

## Constraints

### Técnicas
- **PHP:** ≥8.1 (readonly classes, named arguments, enums)
- **Symfony:** 6.4.* (no upgrade durante refactor)
- **PHPUnit:** 10.5 (attributes, no annotations)
- **Sin nuevas dependencias** de composer (usar lo existente)

### Temporales
- **Sprint 1:** 2 semanas - EditorialOrchestrator + ImageSizesRegistry
- **Sprint 2:** 2 semanas - DataTransformers grandes
- **Sprint 3:** 1 semana - Excepciones + URLService
- **Total:** ~5 semanas

### Recursos
- **1 Backend Senior** para implementación
- **1 QA** para validación
- **Code Review:** Obligatorio por otro developer

### Proceso
- **TDD:** Escribir test primero, luego refactorizar
- **Small commits:** Un cambio por commit
- **CI/CD:** Todos los checks deben pasar antes de merge
- **No force push:** Historia limpia

---

## Riesgos Identificados

### Riesgo 1: Regresión de Funcionalidad
**Probabilidad:** Media
**Impacto:** Alto
**Mitigación:**
- Tests exhaustivos antes de refactorizar
- Feature flags para rollback instantáneo
- Monitoreo de métricas post-deploy

### Riesgo 2: Performance Degradation
**Probabilidad:** Baja
**Impacto:** Alto
**Mitigación:**
- Benchmarks antes/después
- Profile con Blackfire/XDebug
- No agregar indirección innecesaria

### Riesgo 3: Scope Creep
**Probabilidad:** Alta
**Impacto:** Medio
**Mitigación:**
- PRs pequeños y focalizados
- Code review estricto
- Definition of Done clara

---

## Métricas de Éxito

| Métrica | Actual | Objetivo | Método de Medición |
|---------|--------|----------|-------------------|
| Líneas en EditorialOrchestrator | 536 | <100 | `wc -l` |
| Dependencias EditorialOrchestrator | 19 | ≤5 | Constructor params |
| Duplicación SIZES_RELATIONS | 3 lugares | 1 lugar | `grep -r` |
| Complejidad ciclomática máx | 45 | <15 | phpmetrics |
| Test coverage | 70% | ≥90% | PHPUnit |
| MSI | 86% | ≥86% | Infection |
| Tiempo response p95 | 180ms | ≤200ms | NewRelic/Datadog |

---

## Referencias

- [Clean Code - Robert C. Martin](https://www.amazon.com/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Refactoring - Martin Fowler](https://refactoring.com/)
- [Design Patterns - Gang of Four](https://en.wikipedia.org/wiki/Design_Patterns)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)

---

**Documento creado por:** Planner/Architect
**Fecha:** 2026-01-28
**Próximo paso:** Crear 10_architecture.md con diseño de solución
