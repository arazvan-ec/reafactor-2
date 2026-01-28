# Mapa de Dependencias - REFACTOR-001

> **Proyecto:** SNAAPI Refactoring
> **Fecha:** 2026-01-28

---

## Diagrama de Dependencias entre Tareas

```
Sprint 1: Fundamentos
═══════════════════════════════════════════════════════════════════

BE-001 (ImageSizesRegistry)          BE-002 (Exceptions)
     │                                    │
     │                                    │
     └────────────────┬───────────────────┘
                      │
                      ▼
             BE-003 (DataResolverInterface)
                      │
                      │
         ┌───────────┴───────────┐
         │                       │
         ▼                       ▼
BE-004 (OrchestrationContext)    BE-005 (ResolverRegistry)
         │                       │
         └───────────┬───────────┘
                     │
    ┌────────────────┼────────────────┐
    │                │                │
    ▼                ▼                ▼
BE-006           BE-007           BE-008
(SectionResolver) (MultimediaResolver) (JournalistResolver)


Sprint 2: Facade y Servicios
═══════════════════════════════════════════════════════════════════

BE-009 (TagResolver)     BE-010 (InsertedResolver)    BE-011 (RecommendedResolver)
    │                         │                            │
    └─────────────────────────┼────────────────────────────┘
                              │
                              ▼
                    BE-012 (URLGenerationService)
                              │
                              │
                    BE-013 (ResponseBuilder)
                              │
                              │
                    BE-014 (ResolverCompilerPass)
                              │
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  BE-015 (EditorialOrchestratorFacade)           │
│                                                                 │
│  Dependencias:                                                  │
│  - BE-005 (ResolverRegistry)                                    │
│  - BE-004 (OrchestrationContext + Factory)                      │
│  - BE-013 (ResponseBuilder)                                     │
│  - BE-014 (CompilerPass para auto-registro)                     │
│  - BE-006 a BE-011 (Todos los Resolvers)                        │
└─────────────────────────────────────────────────────────────────┘


Sprint 3: Migración y Cleanup
═══════════════════════════════════════════════════════════════════

BE-001 ──────────────────┐
                         │
                         ▼
              BE-016 (Refactor PhotoTransformer)
                         │
                         ▼
              BE-017 (Refactor PictureShots)


BE-012 ──────────────────┐
                         │
                         ▼
              BE-018 (Refactor DetailsApps)


BE-015 ──────────────────┐
                         │
                         ▼
              BE-019 (Migración Final)
                         │
                         ▼
              ┌──────────────────────┐
              │   QA-007 + QA-008    │
              │   (Regresión + Perf) │
              └──────────────────────┘
```

---

## Dependencias por Tarea

### Sprint 1

| Tarea | Depende de | Bloquea a |
|-------|------------|-----------|
| BE-001 | - | BE-016, BE-017 |
| BE-002 | - | BE-001 (usa excepciones) |
| BE-003 | - | BE-005, BE-006 a BE-011 |
| BE-004 | - | BE-005, BE-006 a BE-011 |
| BE-005 | BE-003, BE-004 | BE-014, BE-015 |
| BE-006 | BE-003, BE-004 | BE-015 |
| BE-007 | BE-003, BE-004 | BE-015 |
| BE-008 | BE-003, BE-004 | BE-015 |

### Sprint 2

| Tarea | Depende de | Bloquea a |
|-------|------------|-----------|
| BE-009 | BE-003, BE-004 | BE-015 |
| BE-010 | BE-003, BE-004 | BE-015 |
| BE-011 | BE-003, BE-004 | BE-015 |
| BE-012 | - | BE-013, BE-018 |
| BE-013 | BE-004, BE-012 | BE-015 |
| BE-014 | BE-005 | BE-015 |
| BE-015 | BE-005 a BE-014 | BE-019 |

### Sprint 3

| Tarea | Depende de | Bloquea a |
|-------|------------|-----------|
| BE-016 | BE-001 | - |
| BE-017 | BE-001 | - |
| BE-018 | BE-012 | - |
| BE-019 | BE-015 | QA-007, QA-008 |

---

## Dependencias entre QA y Backend

```
                    Backend                              QA
                    ═══════                              ══

                    BE-001 ─────────────────────────► QA-001
                    BE-002 ─────────────────────────► QA-002
                    BE-005 ─────────────────────────► QA-004
        BE-006 to BE-011 ─────────────────────────► QA-003
                    BE-012 ─────────────────────────► QA-005
                    BE-015 ─────────────────────────► QA-006
                    BE-019 ─────────────────────────► QA-007
                    BE-019 ─────────────────────────► QA-008
```

---

## Critical Path

El **camino crítico** determina la duración mínima del proyecto:

```
BE-003 → BE-004 → BE-005 → BE-006 → BE-015 → BE-019 → QA-007
  (1h)    (2h)     (2h)     (2h)     (6h)     (8h)     (4h)

                                              Total: 25 horas
```

**Critical Path = ~25 horas = ~3-4 días**

### Tareas Paralelas (pueden ejecutarse simultáneamente)

**Sprint 1 Paralelo:**
- BE-001 y BE-002 (sin dependencias)
- BE-006, BE-007, BE-008 (después de BE-004 y BE-005)

**Sprint 2 Paralelo:**
- BE-009, BE-010, BE-011 (después de BE-004)
- BE-012 (sin dependencias backend)
- BE-016, BE-017, BE-018 (después de sus dependencias)

**QA Paralelo:**
- QA-001, QA-002 (tan pronto como BE-001, BE-002 completas)
- QA-003, QA-004, QA-005 (después de sus dependencias)

---

## Orden de Ejecución Recomendado

### Día 1-2 (Sprint 1 - Base)

| Orden | Tarea | Duración | Paralelo con |
|-------|-------|----------|--------------|
| 1 | BE-001 ImageSizesRegistry | 3h | BE-002 |
| 1 | BE-002 Exception Hierarchy | 2h | BE-001 |
| 2 | BE-003 DataResolverInterface | 1h | - |
| 3 | BE-004 OrchestrationContext | 2h | - |
| 4 | BE-005 ResolverRegistry | 2h | - |
| 5 | BE-006 SectionResolver | 2h | BE-007, BE-008 |
| 5 | BE-007 MultimediaResolver | 3h | BE-006, BE-008 |
| 5 | BE-008 JournalistResolver | 2h | BE-006, BE-007 |

### Día 3-5 (Sprint 2 - Facade)

| Orden | Tarea | Duración | Paralelo con |
|-------|-------|----------|--------------|
| 6 | BE-009 TagResolver | 2h | BE-010, BE-011 |
| 6 | BE-010 InsertedNewsResolver | 3h | BE-009, BE-011 |
| 6 | BE-011 RecommendedNewsResolver | 3h | BE-009, BE-010 |
| 7 | BE-012 URLGenerationService | 3h | - |
| 8 | BE-013 ResponseBuilder | 4h | - |
| 9 | BE-014 ResolverCompilerPass | 2h | - |
| 10 | BE-015 EditorialOrchestratorFacade | 6h | - |

### Día 6-8 (Sprint 3 - Migración)

| Orden | Tarea | Duración | Paralelo con |
|-------|-------|----------|--------------|
| 11 | BE-016 Refactor PhotoTransformer | 3h | BE-017, BE-018 |
| 11 | BE-017 Refactor PictureShots | 2h | BE-016, BE-018 |
| 11 | BE-018 Refactor DetailsApps | 3h | BE-016, BE-017 |
| 12 | BE-019 Migración Final | 8h | - |

### Día 9-10 (QA Final)

| Orden | Tarea | Duración | Paralelo con |
|-------|-------|----------|--------------|
| 13 | QA-007 Test Regresión E2E | 4h | QA-008 |
| 13 | QA-008 Métricas y Performance | 3h | QA-007 |

---

## Riesgos de Dependencias

### Riesgo 1: Bloqueo en BE-015
**Descripción:** EditorialOrchestratorFacade depende de 10 tareas previas.
**Mitigación:** Ejecutar todas las tareas previas en paralelo cuando sea posible.

### Riesgo 2: Cambios en Interfaces
**Descripción:** Cambiar DataResolverInterface afecta todos los resolvers.
**Mitigación:** Diseñar interface completa antes de implementar resolvers.

### Riesgo 3: Integración con Clientes Externos
**Descripción:** Resolvers dependen de clientes `ec/*` que pueden cambiar.
**Mitigación:** Usar mocks en tests, validar con tests de integración.

---

## Checklist de Dependencias

### Antes de empezar BE-005 (ResolverRegistry)
- [ ] BE-003 (DataResolverInterface) completada
- [ ] BE-004 (OrchestrationContext) completada

### Antes de empezar BE-015 (Facade)
- [ ] BE-005 (ResolverRegistry) completada
- [ ] BE-006 a BE-011 (Todos los Resolvers) completadas
- [ ] BE-013 (ResponseBuilder) completada
- [ ] BE-014 (CompilerPass) completada

### Antes de empezar BE-019 (Migración)
- [ ] BE-015 (Facade) completada
- [ ] QA-006 (Tests de Facade) pasando
- [ ] Feature flag configurado

### Antes de Deploy
- [ ] QA-007 (Test Regresión) pasando
- [ ] QA-008 (Performance) pasando
- [ ] Rollback plan documentado

---

**Documento creado por:** Planner/Architect
**Fecha:** 2026-01-28
**Versión:** 1.0
