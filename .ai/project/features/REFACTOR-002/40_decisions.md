# Decisiones de Arquitectura - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect
> **Generado por**: workflows:comprehension

---

## Propósito

Este documento captura las decisiones de arquitectura con su justificación (**WHY**), trade-offs considerados, y condiciones para reconsiderar.

> *"Documentar decisiones previene preguntas repetitivas y facilita onboarding."*

---

## Decisiones de Arquitectura

### DEC-001: Usar Interfaces en Domain Layer (No Clases Abstractas)

**Decisión**: Las interfaces `AggregatorInterface`, `AsyncAggregatorInterface`, y `SyncAggregatorInterface` son interfaces puras, no clases abstractas.

**Por qué**:
- PHP permite implementar múltiples interfaces (no herencia múltiple)
- Las interfaces no imponen implementación, solo contrato
- Facilita testing con mocks
- Sigue el principio de Interface Segregation (ISP)

**Trade-offs**:
- (+) Máxima flexibilidad para implementadores
- (+) Facilita testing con PHPUnit mocks
- (-) No hay implementación compartida (código duplicado potencial)
- (-) Cada agregador debe implementar todos los métodos

**Alternativas consideradas**:
1. **Clase abstracta base**: Rechazada porque limitaría herencia
2. **Traits compartidos**: Posible complemento futuro si hay duplicación

**Cuándo reconsiderar**:
- Si hay > 5 métodos duplicados entre agregadores
- Si el boilerplate se vuelve excesivo

---

### DEC-002: GuzzleHttp Promises vs PHP 8.1 Fibers

**Decisión**: Usar `GuzzleHttp\Promise\PromiseInterface` en lugar de PHP Fibers.

**Por qué**:
- Guzzle ya está en uso en el proyecto actual
- Los clientes HTTP existentes ya retornan Guzzle Promises
- Fibers requieren refactoring más extenso de clientes
- Guzzle Promises son battle-tested en producción

**Trade-offs**:
- (+) Compatibilidad con código existente
- (+) No requiere cambios en clientes HTTP
- (+) Bien documentado y estable
- (-) `promise->wait()` sigue siendo bloqueante al final
- (-) No es async "nativo" de PHP

**Alternativas consideradas**:
1. **PHP Fibers**: Async nativo, pero requiere reescribir clientes
2. **ReactPHP**: Overkill para este caso de uso
3. **Amphp**: Similar a ReactPHP, demasiado cambio

**Cuándo reconsiderar**:
- Si se migra a PHP 8.2+ con mejor soporte de Fibers
- Si se reescribe la capa de clientes HTTP
- Si el rendimiento de Promises es insuficiente

---

### DEC-003: Registry Pattern vs Container Tags Directo

**Decisión**: Usar `AggregatorRegistry` y `TransformerRegistry` como intermediarios.

**Por qué**:
- Encapsula la lógica de búsqueda y filtrado
- Permite ordenar por prioridad y filtrar por contexto
- Más fácil de testear que dependencias directas del container
- Permite añadir lógica de validación centralizada

**Trade-offs**:
- (+) Código más testeable
- (+) Lógica de prioridad/filtrado centralizada
- (+) Fácil de extender (cache, métricas, etc.)
- (-) Una capa adicional de indirección
- (-) Los registros deben poblar se via Compiler Pass

**Alternativas consideradas**:
1. **Inyectar array de servicios directo**: Más simple pero sin lógica
2. **Service Locator**: Anti-pattern, evitado
3. **Tagged Iterator de Symfony**: Posible, pero menos control

**Cuándo reconsiderar**:
- Si el overhead del Registry es medible (>1ms)
- Si Symfony añade mejor soporte para prioridad en tags

---

### DEC-004: Topological Sort para Dependencias (Kahn's Algorithm)

**Decisión**: Usar algoritmo de Kahn para ordenación topológica de dependencias.

**Por qué**:
- Detecta ciclos durante la ordenación (no después)
- Complejidad O(V+E) - óptimo para grafos
- Produce batches naturales para ejecución paralela
- Algoritmo bien conocido y documentado

**Trade-offs**:
- (+) Eficiente y correcto
- (+) Detección de ciclos integrada
- (+) Batches para paralelismo
- (-) Más complejo que ordenación simple
- (-) Requiere tests exhaustivos

**Alternativas consideradas**:
1. **DFS + ordenación post-order**: Detecta ciclos pero no crea batches
2. **Sin dependencias**: Más simple pero menos flexible
3. **Dependencias resueltas por el desarrollador**: Error-prone

**Cuándo reconsiderar**:
- Si las dependencias entre agregadores nunca se usan
- Si la complejidad no se justifica en la práctica

---

### DEC-005: Value Objects Inmutables (readonly)

**Decisión**: `AggregatorContext` y `AggregatorResult` son `readonly class`.

**Por qué**:
- Previene bugs de mutación accidental
- Facilita razonamiento sobre el código
- Thread-safe por diseño
- PHP 8.1+ lo soporta nativamente

**Trade-offs**:
- (+) Código más seguro
- (+) Más fácil de razonar
- (+) Sin efectos secundarios
- (-) Crear nuevas instancias para cada cambio (allocations)
- (-) Requiere métodos `withX()` para modificaciones

**Alternativas consideradas**:
1. **Clases mutables**: Más simple pero más bugs potenciales
2. **DTOs con setters**: Rechazado por riesgo de mutación

**Cuándo reconsiderar**:
- Si el GC muestra presión por muchas allocations
- Si PHP añade copy-on-write para readonly

---

### DEC-006: Events vs Callbacks para Observabilidad

**Decisión**: Usar Symfony EventDispatcher para eventos de agregación.

**Por qué**:
- Ya está en el stack de Symfony
- Permite múltiples listeners sin acoplar código
- Fácil añadir logging, métricas, tracing después
- Patrón estándar en el ecosistema

**Trade-offs**:
- (+) Desacoplamiento total
- (+) Múltiples observadores posibles
- (+) Integra con ecosystem (Monolog, APM, etc.)
- (-) Overhead de dispatch (~0.1ms por evento)
- (-) Debugging más difícil (indirección)

**Alternativas consideradas**:
1. **Callbacks directos**: Más simple pero acoplado
2. **Sin eventos**: Más rápido pero sin observabilidad
3. **Hooks pattern**: Similar a eventos pero custom

**Cuándo reconsiderar**:
- Si el overhead de eventos es medible (>1% del tiempo total)
- Si necesitamos eventos async (usar Messenger)

---

### DEC-007: Fallback Values vs Exceptions

**Decisión**: Los agregadores definen `getFallback()` que se usa cuando fallan.

**Por qué**:
- La API debe responder aunque un agregador falle
- "Graceful degradation" es mejor que error 500
- El cliente puede manejar datos parciales
- Logs capturan el error para debugging

**Trade-offs**:
- (+) Resiliente a fallos parciales
- (+) Mejor UX que errores completos
- (+) Permite monitorear errores sin afectar usuarios
- (-) Respuestas pueden tener datos incompletos
- (-) El cliente debe manejar campos vacíos

**Alternativas consideradas**:
1. **Fallar completamente**: Peor UX, más simple
2. **Retry automático**: Aumenta latencia, puede empeorar cascada
3. **Circuit breaker**: Futuro enhancement posible

**Cuándo reconsiderar**:
- Si datos parciales causan bugs en clientes
- Si necesitamos garantías de consistencia estrictas

---

### DEC-008: PHP Attributes vs YAML Configuration

**Decisión**: Usar `#[AsAggregator]` y `#[AsJsonTransformer]` attributes.

**Por qué**:
- Configuración junto al código (co-location)
- Type-safe y validado por PHP
- IDE support con autocompletion
- Menos archivos que mantener

**Trade-offs**:
- (+) Configuración visible en el código
- (+) IDE support nativo
- (+) Sin archivos YAML adicionales
- (-) Cambiar prioridad requiere tocar código PHP
- (-) No se puede cambiar en runtime

**Alternativas consideradas**:
1. **YAML en services.yaml**: Separado del código, más difícil sincronizar
2. **Interfaz con constantes**: Más verbose, sin flexibilidad
3. **Configuración en database**: Overkill para este caso

**Cuándo reconsiderar**:
- Si necesitamos cambiar prioridades sin deploy
- Si operators necesitan configurar sin acceso al código

---

### DEC-009: Mantener EditorialOrchestrator como Facade

**Decisión**: `EditorialOrchestrator` delega a `OrchestrationPipeline`, no se elimina.

**Por qué**:
- Backwards compatibility con código que usa EditorialOrchestrator
- Permite migración gradual
- El controller no necesita cambiar
- Facade pattern es apropiado aquí

**Trade-offs**:
- (+) No rompe código existente
- (+) Migración gradual posible
- (+) Controller unchanged
- (-) Una clase "extra" que es solo delegación
- (-) Dos formas de hacer lo mismo temporalmente

**Alternativas consideradas**:
1. **Reemplazar completamente**: Más limpio pero breaking change
2. **Deprecar gradualmente**: Lo que haremos eventualmente

**Cuándo reconsiderar**:
- Después de que el nuevo sistema esté estable (Sprint +2)
- Cuando todos los consumidores migren

---

### DEC-010: Body Elements como Sync Aggregator con Nested Async

**Decisión**: `BodyTagAggregator` es `SyncAggregatorInterface` pero usa datos de `MultimediaAggregator` (async).

**Por qué**:
- Body processing es secuencial (elemento por elemento)
- Multimedia ya está resuelto cuando se procesa body
- Declarar dependencia en `multimedia` garantiza orden
- Simplifica la lógica de BodyTagAggregator

**Trade-offs**:
- (+) Lógica más simple en BodyTagAggregator
- (+) Multimedia siempre disponible
- (+) Aprovecha paralelismo del batch anterior
- (-) BodyTagAggregator espera a multimedia
- (-) No puede ejecutarse en paralelo con multimedia

**Alternativas consideradas**:
1. **BodyTag async que resuelve su propio multimedia**: Duplicación, más llamadas HTTP
2. **Dos fases de resolución**: Más complejo
3. **Lazy loading de multimedia en body**: Más complejo de implementar

**Cuándo reconsiderar**:
- Si multimedia tiene alta latencia y body no lo necesita siempre
- Si hay body elements sin multimedia

---

## Asunciones Documentadas

### ASM-001: Los Clientes HTTP Soportan Async
**Asunción**: `QueryTagClient`, `QueryMultimediaClient`, etc. pueden retornar Promises.
**Validación**: ✅ Verificado en código existente - ya usan `findMultimediaById($id, self::ASYNC)`
**Riesgo si es falsa**: Alto - requeriría modificar clientes

### ASM-002: Guzzle Promise Settlement es Eficiente
**Asunción**: `Utils::settle()` con 5-7 promises no tiene overhead significativo.
**Validación**: ⚠️ Necesita benchmark
**Riesgo si es falsa**: Medio - podría afectar latencia

### ASM-003: El Contexto No Crece Demasiado
**Asunción**: `AggregatorContext` con datos resueltos no supera ~100KB.
**Validación**: ⚠️ Necesita medición en producción
**Riesgo si es falsa**: Bajo - solo afecta memoria

### ASM-004: Los Agregadores Son Independientes
**Asunción**: Excepto BodyTag→Multimedia, no hay otras dependencias.
**Validación**: ✅ Análisis de código actual confirma
**Riesgo si es falsa**: Bajo - el sistema soporta dependencias

### ASM-005: Prioridades Estáticas Son Suficientes
**Asunción**: Las prioridades no necesitan cambiar en runtime.
**Validación**: ⚠️ Asunción sin validar con stakeholders
**Riesgo si es falsa**: Bajo - se puede añadir configuración después

---

## Gaps de Conocimiento Identificados

| Gap | Impacto | Cómo Resolver |
|-----|---------|---------------|
| Performance de `Utils::settle()` con muchas promises | Medio | Benchmark antes de producción |
| Memory footprint del contexto | Bajo | Profiling en staging |
| Comportamiento bajo alta concurrencia | Alto | Load test con QA-008 |
| Timeout handling real en Guzzle | Medio | Test con servicios lentos mock |

---

## Historial de Decisiones

| ID | Fecha | Decisión | Autor |
|----|-------|----------|-------|
| DEC-001 | 2026-01-28 | Interfaces puras en Domain | Planner |
| DEC-002 | 2026-01-28 | GuzzleHttp Promises | Planner |
| DEC-003 | 2026-01-28 | Registry Pattern | Planner |
| DEC-004 | 2026-01-28 | Kahn's Algorithm | Planner |
| DEC-005 | 2026-01-28 | Readonly Value Objects | Planner |
| DEC-006 | 2026-01-28 | Symfony Events | Planner |
| DEC-007 | 2026-01-28 | Fallback Values | Planner |
| DEC-008 | 2026-01-28 | PHP Attributes | Planner |
| DEC-009 | 2026-01-28 | EditorialOrchestrator Facade | Planner |
| DEC-010 | 2026-01-28 | BodyTag Sync with Dependency | Planner |

---

**Próxima revisión**: Después de BE-008 (AggregatorExecutor) implementado
