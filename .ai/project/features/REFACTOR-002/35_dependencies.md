# Mapa de Dependencias - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Dependencias Backend

### Diagrama de Dependencias (ASCII)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         DOMAIN LAYER (Sin dependencias)                      │
│                                                                             │
│   BE-001          BE-002          BE-003          BE-004          BE-005   │
│   Contracts       Value Objects   Exceptions      Events          Transform │
│   ────────────────────────────────────────────────────────────────────────  │
│       │               │               │               │               │     │
│       │               │               │               │               │     │
│       └───────────────┼───────────────┼───────────────┼───────────────┘     │
│                       │               │               │                     │
│                       ▼               ▼               ▼                     │
└───────────────────────┼───────────────┼───────────────┼─────────────────────┘
                        │               │               │
┌───────────────────────┼───────────────┼───────────────┼─────────────────────┐
│                       │   APPLICATION LAYER           │                     │
│                       │                               │                     │
│   ┌───────────────────▼───────────────────┐          │                     │
│   │              BE-006                    │          │                     │
│   │         AggregatorRegistry            │          │                     │
│   └───────────────────┬───────────────────┘          │                     │
│                       │                               │                     │
│   ┌───────────────────▼───────────────────┐          │                     │
│   │              BE-007                    │          │                     │
│   │         DependencyResolver            │          │                     │
│   └───────────────────┬───────────────────┘          │                     │
│                       │                               │                     │
│   ┌───────────────────▼───────────────────────────────▼─────┐              │
│   │                        BE-008                           │              │
│   │                   AggregatorExecutor                    │              │
│   └───────────────────────────┬─────────────────────────────┘              │
│                               │                                             │
│   ┌───────────────────────────┼──────────────┐                             │
│   │                           │              │                             │
│   ▼                           │              ▼                             │
│  BE-009                       │           BE-010                           │
│  TransformerRegistry          │           TransformationPipeline           │
│   │                           │              │                             │
│   └───────────────────────────┼──────────────┘                             │
│                               │                                             │
│                               ▼                                             │
│   ┌───────────────────────────────────────────────────────────────────┐    │
│   │                           BE-011                                   │    │
│   │                    OrchestrationPipeline                          │    │
│   └───────────────────────────┬───────────────────────────────────────┘    │
│                               │                                             │
└───────────────────────────────┼─────────────────────────────────────────────┘
                                │
┌───────────────────────────────┼─────────────────────────────────────────────┐
│                               │   INFRASTRUCTURE LAYER                       │
│                               │                                             │
│   BE-012                      │                                             │
│   PHP Attributes ─────────────┼──────────────────────┐                      │
│                               │                      │                      │
│                               │                      ▼                      │
│   ┌───────────────────────────┼───────────────── BE-013                     │
│   │                           │               Compiler Passes               │
│   │                           │                      │                      │
│   │                           ▼                      │                      │
│   │   ┌─────────────────────────────────────────────┼────────┐              │
│   │   │                                             │        │              │
│   │   ▼                   ▼                   ▼     │   ▼    │              │
│   │ BE-014              BE-015              BE-016  │ BE-017 │              │
│   │ TagAggregator       MultimediaAgg       JournalistAgg   SectionAgg     │
│   │   │                   │                   │              │              │
│   │   └───────────────────┼───────────────────┘              │              │
│   │                       │                                  │              │
│   │                       ▼                                  │              │
│   │   ┌─────────────────────────────────────────────────────┼────┐         │
│   │   │                    BE-018                            │    │         │
│   │   │              BodyTagAggregator                       │    │         │
│   │   │         (depende de MultimediaAgg)                   │    │         │
│   │   └──────────────────────┬───────────────────────────────┘    │         │
│   │                          │                                    │         │
│   │                          ▼                                    │         │
│   │   ┌──────────────────────────────────────────────────────────┐│         │
│   │   │                    BE-019                                 ││         │
│   │   │         Integración con EditorialOrchestrator            ││         │
│   │   └──────────────────────────────────────────────────────────┘│         │
│   │                                                               │         │
│   └───────────────────────────────────────────────────────────────┘         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Tabla de Dependencias Backend

| Tarea | Depende de | Bloquea a |
|-------|------------|-----------|
| BE-001 | - | BE-006, BE-007, BE-008, BE-014-018 |
| BE-002 | - | BE-006, BE-008, BE-010, BE-011 |
| BE-003 | - | BE-006, BE-007, BE-008 |
| BE-004 | - | BE-008, BE-011 |
| BE-005 | BE-002 | BE-009, BE-010 |
| BE-006 | BE-001, BE-002, BE-003 | BE-007, BE-008, BE-013 |
| BE-007 | BE-001, BE-003 | BE-008 |
| BE-008 | BE-001-007 | BE-011, BE-019 |
| BE-009 | BE-005 | BE-010, BE-013 |
| BE-010 | BE-002, BE-005, BE-009 | BE-011 |
| BE-011 | BE-004, BE-008, BE-010 | BE-019 |
| BE-012 | - | BE-013, BE-014-018 |
| BE-013 | BE-006, BE-009, BE-012 | BE-014-018 |
| BE-014 | BE-001, BE-012 | BE-018, BE-019 |
| BE-015 | BE-001, BE-012 | BE-018, BE-019 |
| BE-016 | BE-001, BE-012 | BE-019 |
| BE-017 | BE-001, BE-012 | BE-019 |
| BE-018 | BE-001, BE-012, BE-015 | BE-019 |
| BE-019 | BE-011, BE-014-018 | - |

---

## Dependencias Frontend

```
                    ┌─────────────┐
                    │   BE-019    │
                    │(Integración)│
                    └──────┬──────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
           ▼               ▼               ▼
     ┌─────────┐     ┌─────────┐     ┌─────────┐
     │ FE-001  │     │ FE-002  │     │ FE-003  │
     │ OpenAPI │     │Changelog│     │ Notify  │
     └─────────┘     └─────────┘     └────┬────┘
                                          │
                                          ▼
                                    ┌─────────┐
                                    │ FE-004  │
                                    │Validate │
                                    │Contracts│
                                    └─────────┘
```

| Tarea | Depende de | Bloquea a |
|-------|------------|-----------|
| FE-001 | BE-019 | - |
| FE-002 | BE-019 | - |
| FE-003 | - | FE-004 |
| FE-004 | BE-019, FE-003, Deploy | - |

---

## Dependencias QA

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              QA DEPENDENCIES                                 │
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                    PARALLEL WITH BACKEND                             │   │
│   │                                                                      │   │
│   │   BE-001 to BE-005                    BE-006 to BE-011              │   │
│   │         │                                    │                       │   │
│   │         ▼                                    ▼                       │   │
│   │     QA-001                               QA-002                      │   │
│   │   Domain Tests                      Application Tests                │   │
│   │                                                                      │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                    AFTER BACKEND IMPLEMENTATION                      │   │
│   │                                                                      │   │
│   │   BE-014 to BE-018                        BE-019                    │   │
│   │         │                                    │                       │   │
│   │         ▼                                    ▼                       │   │
│   │     QA-003                               QA-004                      │   │
│   │  Aggregator Tests                     Pipeline Tests                 │   │
│   │                                                                      │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                    AFTER STAGING DEPLOY                              │   │
│   │                                                                      │   │
│   │     QA-004                                                          │   │
│   │         │                                                            │   │
│   │         ├───────────────┬───────────────┐                           │   │
│   │         ▼               ▼               ▼                           │   │
│   │     QA-005          QA-006          QA-007                          │   │
│   │   Regression       Contract        Benchmarks                       │   │
│   │     Tests           Tests                                           │   │
│   │         │               │               │                           │   │
│   │         └───────────────┼───────────────┘                           │   │
│   │                         ▼                                           │   │
│   │                     QA-008                                          │   │
│   │                   Load Tests                                        │   │
│   │                                                                      │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

| Tarea | Depende de | Bloquea a |
|-------|------------|-----------|
| QA-001 | BE-001 to BE-005 | QA-002 |
| QA-002 | BE-006 to BE-011, QA-001 | QA-003 |
| QA-003 | BE-014 to BE-018, QA-002 | QA-004 |
| QA-004 | BE-019, QA-003 | QA-005, QA-006, QA-007 |
| QA-005 | QA-004, Deploy Staging | QA-008 |
| QA-006 | QA-004, Deploy Staging | QA-008 |
| QA-007 | QA-004, Deploy Staging | QA-008 |
| QA-008 | QA-005, QA-006, QA-007 | - |

---

## Dependencias Cross-Role

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         CROSS-ROLE DEPENDENCIES                              │
│                                                                             │
│  ┌────────────┐                                                             │
│  │  BACKEND   │                                                             │
│  │  BE-001    │─────┐                                                       │
│  │  to        │     │                                                       │
│  │  BE-011    │     │  Domain + Application Layer                           │
│  └────────────┘     │                                                       │
│                     │                                                       │
│  ┌────────────┐     │     ┌────────────┐                                   │
│  │    QA      │◄────┴─────│ Can start  │                                   │
│  │  QA-001    │           │  testing   │                                   │
│  │  QA-002    │           │  in        │                                   │
│  └────────────┘           │  parallel  │                                   │
│                           └────────────┘                                   │
│                                                                             │
│  ┌────────────┐                                                             │
│  │  BACKEND   │                                                             │
│  │  BE-012    │─────┐                                                       │
│  │  to        │     │  Infrastructure Layer                                 │
│  │  BE-019    │     │                                                       │
│  └────────────┘     │                                                       │
│                     │                                                       │
│  ┌────────────┐     │                                                       │
│  │    QA      │◄────┴─── Must wait for aggregators                         │
│  │  QA-003    │                                                             │
│  │  QA-004    │                                                             │
│  └────────────┘                                                             │
│                                                                             │
│  ┌────────────┐                                                             │
│  │  BACKEND   │                                                             │
│  │  BE-019    │─────┬──────────────────────────────────────┐               │
│  │ Complete   │     │                                      │               │
│  └────────────┘     │                                      │               │
│                     ▼                                      ▼               │
│  ┌────────────┐  ┌────────────┐                      ┌────────────┐        │
│  │  FRONTEND  │  │    QA      │                      │   DEPLOY   │        │
│  │  FE-001    │  │  QA-005    │◄─────────────────────│   Staging  │        │
│  │  FE-002    │  │  to        │                      └────────────┘        │
│  │  FE-004    │  │  QA-008    │                                            │
│  └────────────┘  └────────────┘                                            │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Critical Path

El camino crítico (ruta más larga) determina el tiempo mínimo de entrega:

```
BE-001 → BE-006 → BE-007 → BE-008 → BE-011 → BE-019 → QA-005 → QA-008
  1h       3h        5h       5h       4h       4h       4h       4h

Total Critical Path: ~30 horas (~4 días)

Alternative path through aggregators:
BE-001 → BE-012 → BE-013 → BE-015 → BE-018 → BE-019
  1h       1h        3h       4h       7h       4h = ~20h

Combined (parallel execution):
- Domain Layer (BE-001 to BE-005): Day 1
- Application Layer (BE-006 to BE-011): Day 2-3
- Infrastructure Layer (BE-012 to BE-019): Day 3-5
- QA + Frontend: Day 4-6 (parallel with later backend)
```

---

## Tareas Paralelizables

### Batch 1: Sin dependencias (Día 1)
```
┌─────────┬─────────┬─────────┬─────────┬─────────┬─────────┐
│ BE-001  │ BE-002  │ BE-003  │ BE-004  │ BE-012  │ FE-003  │
│Contracts│  VOs    │Exceptions│ Events │Attributes│ Notify │
└─────────┴─────────┴─────────┴─────────┴─────────┴─────────┘
```

### Batch 2: Después de Domain (Día 2)
```
┌─────────┬─────────┬─────────┐
│ BE-005  │ BE-006  │ QA-001  │
│Transform│Registry │Domain   │
│Contract │         │Tests    │
└─────────┴─────────┴─────────┘
```

### Batch 3: Application Core (Día 2-3)
```
┌─────────┬─────────┬─────────┬─────────┐
│ BE-007  │ BE-009  │ BE-013  │ QA-002  │
│Resolver │TransfReg│Compiler │App Tests│
└─────────┴─────────┴─────────┴─────────┘
```

### Batch 4: Execution Layer (Día 3)
```
┌─────────┬─────────┐
│ BE-008  │ BE-010  │
│Executor │Pipeline │
└─────────┴─────────┘
```

### Batch 5: Aggregators (Día 3-4)
```
┌─────────┬─────────┬─────────┬─────────┐
│ BE-014  │ BE-015  │ BE-016  │ BE-017  │
│   Tag   │Multimed │Journal  │Section  │
└─────────┴─────────┴─────────┴─────────┘
```

### Batch 6: Complex Aggregator + Pipeline (Día 4)
```
┌─────────┬─────────┐
│ BE-011  │ BE-018  │
│Orchestr │BodyTag │
└─────────┴─────────┘
```

### Batch 7: Integration (Día 5)
```
┌─────────┬─────────┬─────────┐
│ BE-019  │ QA-003  │ QA-004  │
│Integrate│AggTests │Pipeline │
└─────────┴─────────┴─────────┘
```

### Batch 8: Validation (Día 5-6)
```
┌─────────┬─────────┬─────────┬─────────┬─────────┐
│ FE-001  │ FE-002  │ QA-005  │ QA-006  │ QA-007  │
│OpenAPI  │Changelog│Regress  │Contract │Benchmark│
└─────────┴─────────┴─────────┴─────────┴─────────┘
```

### Batch 9: Final (Día 6)
```
┌─────────┬─────────┐
│ FE-004  │ QA-008  │
│Validate │Load Test│
└─────────┴─────────┘
```

---

## Resumen de Estimaciones por Fase

| Fase | Tareas | Tiempo (secuencial) | Tiempo (paralelo) |
|------|--------|---------------------|-------------------|
| Domain | BE-001 to BE-005 | 7-8h | 4-5h |
| Application | BE-006 to BE-011 | 20-25h | 10-12h |
| Infrastructure | BE-012 to BE-019 | 22-28h | 12-16h |
| Frontend | FE-001 to FE-004 | 5-8h | 4-5h |
| QA | QA-001 to QA-008 | 28-37h | 15-20h |
| **Total** | **31 tareas** | **~82-106h** | **~45-58h (~6-7 días)** |

---

## Riesgos de Dependencias

| Riesgo | Impacto | Mitigación |
|--------|---------|------------|
| BE-008 (Executor) se retrasa | Bloquea BE-011 y toda la integración | Priorizar, asignar dev senior |
| BE-018 (BodyTag) es más complejo de lo esperado | Retrasa BE-019 | Comenzar early, puede ser paralelo a otros aggregators |
| QA-005/006 encuentran problemas | Retrasa producción | Dedicar tiempo suficiente a staging |

---

**Próximo paso**: FEATURE_REFACTOR-002.md y 50_state.md (Resumen y Estado)
