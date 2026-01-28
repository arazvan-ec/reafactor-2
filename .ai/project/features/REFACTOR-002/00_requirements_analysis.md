# Análisis de Requisitos - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Resumen Ejecutivo

### ¿Qué problema resuelve?
El sistema actual de SNAAPI tiene una orquestación de peticiones HTTP para agregadores (Tags, BodyTags, Multimedia, etc.) que:
1. **No es verdaderamente asíncrona** - Usa `promise->wait()` que bloquea el hilo
2. **No es fácilmente escalable** - Añadir un nuevo agregador requiere modificar múltiples archivos
3. **Tiene lógica dispersa** - La orquestación, resolución y transformación están mezcladas en `EditorialOrchestrator`
4. **No tiene un patrón uniforme** - Cada agregador se procesa de forma diferente

### ¿Para quién es?
- **Desarrolladores Backend**: Que necesitan añadir nuevos agregadores de datos
- **Arquitectos**: Que necesitan mantener un sistema escalable y mantenible
- **Equipo de Operaciones**: Que se beneficia de mejor rendimiento y monitorización

### ¿Cuál es el valor de negocio?
- **Reducción de tiempo de desarrollo** al añadir nuevos agregadores (de días a horas)
- **Mejor rendimiento** con async real (PHP 8.1+ Fibers o Promises paralelas)
- **Menor deuda técnica** con arquitectura limpia y extensible
- **Facilidad de testing** con componentes desacoplados

---

## Requisitos Funcionales

### RF-001: Agregadores Async Uniformes
El sistema debe permitir definir agregadores de datos (Tags, BodyTags, Multimedia, Journalists, etc.) que se ejecuten de forma asíncrona con un patrón uniforme.

### RF-002: Capa de Orquestación Desacoplada
La orquestación de peticiones HTTP debe estar separada de la lógica de transformación, siguiendo el principio de responsabilidad única.

### RF-003: Capa de Traducción a JSON Extensible
La traducción de datos a JSON debe seguir un patrón de Strategy extensible donde añadir un nuevo tipo de dato solo requiera crear una clase que implemente una interfaz.

### RF-004: Registro Automático de Agregadores
Los nuevos agregadores deben registrarse automáticamente mediante Compiler Passes de Symfony, sin necesidad de modificar código existente.

### RF-005: Contexto de Orquestación Tipado
El contexto de orquestación debe ser un Value Object tipado que contenga toda la información necesaria para los agregadores.

### RF-006: Resolución Paralela de Promesas
El sistema debe soportar la resolución paralela de múltiples promesas HTTP, minimizando el tiempo de espera total.

### RF-007: Manejo de Errores Graceful
Los agregadores deben manejar errores de forma graceful, permitiendo que la respuesta se complete parcialmente si un agregador falla.

### RF-008: Configuración por Agregador
Cada agregador debe poder configurar su prioridad, timeout y comportamiento de fallback de forma independiente.

### RF-009: Soporte para Agregadores Anidados
El sistema debe soportar agregadores anidados (ej: BodyTags que contienen Multimedia que contiene Tags).

### RF-010: Métricas y Observabilidad
El sistema debe emitir eventos/métricas para monitorizar el rendimiento de cada agregador.

---

## Requisitos No Funcionales

### RNF-001: Performance
- La orquestación completa debe completarse en < 500ms para el 95% de las solicitudes
- Las promesas paralelas deben ejecutarse concurrentemente, no secuencialmente
- El overhead de la abstracción debe ser < 5ms por agregador

### RNF-002: Escalabilidad
- Añadir un nuevo agregador debe requerir crear máximo 2 archivos (Aggregator + Transformer)
- No debe requerirse modificar archivos existentes para añadir agregadores

### RNF-003: Mantenibilidad
- Cada agregador debe ser testeable de forma aislada
- El código debe seguir principios SOLID y DDD
- La cobertura de tests debe ser > 80%

### RNF-004: Compatibilidad
- Mantener compatibilidad con Symfony 6.4+
- Mantener compatibilidad con PHP 8.1+
- No romper contratos de API existentes

### RNF-005: Observabilidad
- Logs estructurados por agregador
- Métricas de tiempo por agregador
- Trace IDs para debugging distribuido

---

## Entidades Identificadas

### Agregadores de Datos (Nuevos Conceptos)

| Entidad | Descripción |
|---------|-------------|
| `Aggregator` | Interfaz base para todos los agregadores de datos |
| `AsyncAggregator` | Agregador que retorna una Promesa |
| `AggregatorRegistry` | Registro central de agregadores con prioridad |
| `AggregatorContext` | DTO con datos necesarios para la agregación |
| `AggregatorResult` | Value Object con el resultado de la agregación |

### Transformadores (Existentes a Refactorizar)

| Entidad | Descripción |
|---------|-------------|
| `JsonTransformer` | Interfaz para transformación a JSON |
| `TransformerRegistry` | Registro de transformadores por tipo |
| `TransformationContext` | Contexto con datos ya agregados |

### Orquestación (Refactorizar)

| Entidad | Descripción |
|---------|-------------|
| `OrchestrationPipeline` | Pipeline de agregación secuencial/paralela |
| `ParallelExecutor` | Ejecutor de promesas en paralelo |
| `OrchestrationResult` | Resultado combinado de todos los agregadores |

---

## Reglas de Negocio

### RN-001: Prioridad de Agregadores
Los agregadores se ejecutan en orden de prioridad (mayor primero). Agregadores con la misma prioridad pueden ejecutarse en paralelo.

### RN-002: Dependencias entre Agregadores
Un agregador puede declarar dependencias en otros agregadores. El sistema debe resolver el grafo de dependencias antes de ejecutar.

### RN-003: Timeout por Agregador
Cada agregador tiene un timeout configurable. Si se excede, se usa el valor de fallback.

### RN-004: Fallback Graceful
Si un agregador falla, el sistema debe:
1. Registrar el error
2. Usar el valor de fallback (null, array vacío, o valor por defecto)
3. Continuar con los demás agregadores

### RN-005: Transformación Post-Agregación
La transformación a JSON solo ocurre después de que todos los agregadores hayan completado (o fallado).

### RN-006: Inmutabilidad del Contexto
El contexto de orquestación es inmutable. Cada agregador recibe una copia y retorna un nuevo resultado.

### RN-007: Idempotencia
Ejecutar el mismo agregador múltiples veces con el mismo input debe producir el mismo output.

---

## Casos de Uso

### UC-001: Agregar Datos de Editorial
**Actor**: Sistema (API Request)
**Flujo**:
1. Controller recibe request para editorial
2. Orquestador crea contexto con ID de editorial
3. Ejecuta agregadores en orden de prioridad/dependencias:
   - TagAggregator (async)
   - MultimediaAggregator (async)
   - JournalistAggregator (async)
   - BodyTagAggregator (sync con nested async)
4. Espera todas las promesas
5. Transforma resultados a JSON
6. Retorna respuesta

### UC-002: Añadir Nuevo Agregador
**Actor**: Desarrollador
**Flujo**:
1. Crear clase que implemente `AsyncAggregator`
2. Anotar con `#[AsAggregator(priority: 50)]`
3. Crear `JsonTransformer` correspondiente
4. Symfony auto-registra via Compiler Pass
5. El agregador se ejecuta automáticamente

### UC-003: Agregar BodyTags con Nested Data
**Actor**: Sistema
**Flujo**:
1. BodyTagAggregator itera sobre elementos del body
2. Para cada elemento, determina tipo (Picture, Video, InsertedNews)
3. Crea sub-agregadores para datos nested (foto, video, noticia)
4. Ejecuta sub-agregadores en paralelo
5. Combina resultados
6. Retorna body con datos resueltos

### UC-004: Manejar Fallo de Agregador
**Actor**: Sistema
**Flujo**:
1. MultimediaAggregator hace request HTTP
2. Request falla (timeout, 500, etc.)
3. Sistema registra error en logs
4. Usa valor fallback (multimedia vacío)
5. Continúa con otros agregadores
6. Respuesta se retorna con campo multimedia vacío

---

## Acceptance Criteria

### AC-001: Arquitectura Extensible
- [ ] Puedo añadir un nuevo agregador creando una sola clase
- [ ] El nuevo agregador se registra automáticamente
- [ ] No necesito modificar `EditorialOrchestrator` ni otros archivos

### AC-002: Ejecución Paralela
- [ ] Los agregadores sin dependencias se ejecutan en paralelo
- [ ] El tiempo total es ~ max(tiempo de cada agregador), no la suma

### AC-003: Transformación Uniforme
- [ ] Todos los agregadores usan el mismo patrón de transformación
- [ ] Los transformadores se registran automáticamente por tipo

### AC-004: Manejo de Errores
- [ ] Un agregador que falla no rompe la respuesta completa
- [ ] Los errores se registran con contexto suficiente
- [ ] Se usan valores fallback cuando un agregador falla

### AC-005: Compatibilidad
- [ ] Los endpoints existentes siguen funcionando
- [ ] Los contratos de API no cambian
- [ ] Los tests existentes pasan

### AC-006: Performance
- [ ] El overhead de la nueva arquitectura es < 5ms
- [ ] Las promesas se ejecutan en paralelo cuando es posible
- [ ] Hay métricas para medir tiempo por agregador

### AC-007: Testing
- [ ] Cada agregador tiene tests unitarios
- [ ] Hay tests de integración para el pipeline completo
- [ ] La cobertura es > 80%

---

## Constraints

### Técnicas
- **Symfony 6.4+**: Mantener compatibilidad con versión actual
- **PHP 8.1+**: Usar features modernas (attributes, readonly, etc.)
- **GuzzleHttp**: Mantener uso de Guzzle para HTTP async
- **DDD**: Seguir arquitectura Domain-Driven Design existente

### Temporales
- **Sprint 4**: Este refactoring debe completarse en el sprint actual

### Recursos
- **1 Backend Engineer**: Para implementación
- **1 QA Engineer**: Para validación

### Arquitectura Existente
- **No romper**: Los Compiler Passes existentes
- **No modificar**: Los contratos de API
- **Preservar**: La estructura de directorios DDD

---

## Agregadores Identificados para Migrar

| Agregador Actual | Prioridad | Async | Dependencias |
|------------------|-----------|-------|--------------|
| TagResolver | 70 | Sí | Ninguna |
| MultimediaResolver | 90 | Sí | Ninguna |
| JournalistResolver | 60 | Sí | Ninguna |
| SectionResolver | 80 | No | Ninguna |
| InsertedNewsResolver | 50 | Sí | Ninguna |
| RecommendedNewsResolver | 40 | Sí | Ninguna |
| BodyTagAggregator (nuevo) | 100 | Mixto | Multimedia |

---

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Performance regression | Media | Alto | Tests de performance antes/después |
| Romper API existente | Baja | Crítico | Feature flags, tests de contrato |
| Complejidad excesiva | Media | Medio | Revisión de arquitectura, KISS |
| Dependencias circulares | Baja | Alto | Validación de grafo en runtime |

---

## Referencias

- Código actual: `/src/Orchestrator/`
- Tests actuales: `/tests/`
- Documentación DDD: `.ai/workflow/rules/ddd_rules.md`
- Feature anterior: `.ai/project/features/REFACTOR-001/`

---

**Próximo paso**: 10_architecture.md (Diseño de Arquitectura)
