# Feature: REFACTOR-002 - Scalable Async Aggregators

> **Status**: PLANNING COMPLETE - Ready for Implementation
> **Version**: 1.0
> **Created**: 2026-01-28
> **Author**: Planner/Architect

---

## Resumen Ejecutivo

### Objetivo

Refactorizar el sistema de agregación de datos de SNAAPI para hacerlo:
- **Escalable**: Añadir nuevos agregadores con mínimo código
- **Asíncrono real**: Ejecución paralela de promesas HTTP
- **Extensible**: Auto-registro mediante PHP Attributes y Compiler Passes
- **Mantenible**: Arquitectura limpia siguiendo DDD y SOLID

### Problema Actual

El `EditorialOrchestrator` actual tiene:
1. **Lógica mezclada**: Orquestación, resolución y transformación en un mismo archivo
2. **No es escalable**: Añadir un agregador requiere modificar múltiples archivos
3. **Async bloqueante**: Usa `promise->wait()` que bloquea el hilo
4. **Difícil de testear**: Componentes fuertemente acoplados

### Solución Propuesta

Crear una arquitectura de agregadores basada en:
- **Interfaces tipadas**: `AsyncAggregatorInterface`, `SyncAggregatorInterface`
- **Registros centrales**: `AggregatorRegistry`, `TransformerRegistry`
- **Pipeline de ejecución**: `DependencyResolver` → `AggregatorExecutor` → `TransformationPipeline`
- **Auto-registro**: PHP 8.1+ Attributes + Symfony Compiler Passes

---

## Estimaciones

### Por Rol

| Rol | Tareas | Tiempo Estimado |
|-----|--------|-----------------|
| **Backend** | 19 | 5-6 días |
| **Frontend** | 4 | 1 día |
| **QA** | 8 | 3-4 días |

### Tiempo Total

| Ejecución | Tiempo |
|-----------|--------|
| Secuencial | ~12-14 días |
| **Paralelo (recomendado)** | **~6-7 días** |

### Distribución del Esfuerzo

```
Backend (65%)    ████████████████████░░░░░░░░░░
Frontend (8%)    ██░░░░░░░░░░░░░░░░░░░░░░░░░░░░
QA (27%)         ████████░░░░░░░░░░░░░░░░░░░░░░
```

---

## Documentos Disponibles

| Documento | Contenido | Páginas |
|-----------|-----------|---------|
| [00_requirements_analysis.md](./00_requirements_analysis.md) | Requisitos, entidades, reglas de negocio | ~8 |
| [10_architecture.md](./10_architecture.md) | Arquitectura DDD, componentes, diagramas | ~12 |
| [15_data_model.md](./15_data_model.md) | Value Objects, DTOs, estructuras JSON | ~8 |
| [20_api_contracts.md](./20_api_contracts.md) | Interfaces PHP, contratos internos | ~10 |
| [30_tasks_backend.md](./30_tasks_backend.md) | 19 tareas backend detalladas | ~20 |
| [31_tasks_frontend.md](./31_tasks_frontend.md) | 4 tareas frontend/documentación | ~4 |
| [32_tasks_qa.md](./32_tasks_qa.md) | 8 tareas QA con test cases | ~12 |
| [35_dependencies.md](./35_dependencies.md) | Mapa de dependencias, critical path | ~6 |
| [50_state.md](./50_state.md) | Estado actual del planning | ~2 |

---

## Arquitectura Propuesta

```
┌─────────────────────────────────────────────────────────────────┐
│                      OrchestrationPipeline                       │
│                                                                  │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐     │
│   │  Aggregator  │ => │  Aggregator  │ => │Transformation│     │
│   │   Registry   │    │   Executor   │    │   Pipeline   │     │
│   └──────────────┘    └──────────────┘    └──────────────┘     │
│          │                   │                   │              │
│          │           ┌──────────────┐            │              │
│          │           │  Dependency  │            │              │
│          │           │   Resolver   │            │              │
│          │           └──────────────┘            │              │
│          │                                       │              │
│          ▼                                       ▼              │
│   ┌─────────────────────────────────────────────────────┐      │
│   │                  Aggregators                         │      │
│   │  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐   │      │
│   │  │ Tag │ │Multi│ │Journ│ │Sect │ │Body │ │ ... │   │      │
│   │  └─────┘ └─────┘ └─────┘ └─────┘ └─────┘ └─────┘   │      │
│   └─────────────────────────────────────────────────────┘      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Beneficios Esperados

### Para Desarrolladores
- **Reducción de código**: Añadir agregador = 1 clase + 1 transformador
- **Auto-registro**: Sin modificar services.yaml ni otros archivos
- **Testabilidad**: Cada componente aislado y mockeable
- **Claridad**: Responsabilidades bien definidas

### Para el Sistema
- **Performance**: Ejecución paralela real de agregadores
- **Resiliencia**: Fallback graceful si un agregador falla
- **Observabilidad**: Eventos para métricas y debugging
- **Escalabilidad**: Fácil añadir nuevos tipos de datos

### Para el Negocio
- **Time to market**: Nuevas features más rápidas
- **Mantenibilidad**: Menor deuda técnica
- **Confiabilidad**: Mejor manejo de errores

---

## Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Regresión en API | Baja | Crítico | Tests de contrato, comparar responses |
| Performance peor | Baja | Alto | Benchmarks antes/después |
| Complejidad excesiva | Media | Medio | Code review, KISS principle |
| Timeline slip | Media | Medio | Buffer de 1 día, priorizar critical path |

---

## Próximos Pasos

### Para Implementar Este Feature

1. **Usar workflow 'default' para implementación:**
   ```bash
   # Iniciar implementación
   ./workflow start REFACTOR-002 default --execute
   ```

2. **O trabajar como roles individuales:**
   ```bash
   # Backend
   ./workflow role backend REFACTOR-002

   # QA (puede empezar en paralelo con Domain)
   ./workflow role qa REFACTOR-002

   # Frontend (después de BE-019)
   ./workflow role frontend REFACTOR-002
   ```

3. **Cada rol debe leer su archivo de tareas:**
   - Backend: `30_tasks_backend.md`
   - Frontend: `31_tasks_frontend.md`
   - QA: `32_tasks_qa.md`

---

## Checklist General

### Planning
- [x] Requisitos analizados (00_requirements_analysis.md)
- [x] Arquitectura diseñada (10_architecture.md)
- [x] Modelo de datos definido (15_data_model.md)
- [x] API contracts definidos (20_api_contracts.md)
- [x] Tareas backend desglosadas (30_tasks_backend.md)
- [x] Tareas frontend desglosadas (31_tasks_frontend.md)
- [x] Tareas QA desglosadas (32_tasks_qa.md)
- [x] Dependencias mapeadas (35_dependencies.md)

### Backend (Pendiente)
- [ ] Domain layer completo (BE-001 to BE-005)
- [ ] Application layer completo (BE-006 to BE-011)
- [ ] Infrastructure layer completo (BE-012 to BE-019)
- [ ] Tests passing (>80%)
- [ ] Integración con EditorialOrchestrator

### Frontend (Pendiente)
- [ ] OpenAPI actualizado
- [ ] Changelog creado
- [ ] Equipos notificados
- [ ] Contratos validados

### QA (Pendiente)
- [ ] Tests unitarios (QA-001, QA-002)
- [ ] Tests integración (QA-003, QA-004)
- [ ] Tests regresión (QA-005, QA-006)
- [ ] Tests performance (QA-007, QA-008)

---

## Métricas de Éxito

| Métrica | Objetivo | Medición |
|---------|----------|----------|
| Cobertura de tests | >80% | `./vendor/bin/phpunit --coverage` |
| Tiempo de respuesta p95 | <500ms | APM / Artillery |
| Tiempo para añadir agregador | <4h | Developer feedback |
| Regresiones | 0 | Tests de contrato |
| Breaking changes | 0 | API diff |

---

## Contacto

- **Planner**: [Planner/Architect]
- **Backend Lead**: [TBD]
- **QA Lead**: [TBD]
- **Feature Owner**: [TBD]

---

**Generated by**: task-breakdown workflow
**Workflow version**: 1.0
**Documentation complete**: 2026-01-28
