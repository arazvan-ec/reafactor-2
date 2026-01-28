# Tareas Frontend - REFACTOR-002

> **Feature**: Scalable Async Aggregators
> **Versión**: 1.0
> **Fecha**: 2026-01-28
> **Autor**: Planner/Architect

---

## Nota Importante

Este feature es un **refactoring de backend**. SNAAPI es un API Gateway que no tiene frontend propio.

Las tareas de "frontend" en este contexto se refieren a:
1. **Documentación de API** para consumidores
2. **Validación de contratos** con aplicaciones móviles cliente
3. **Comunicación con equipos consumidores**

---

## Resumen

| Métrica | Valor |
|---------|-------|
| Total de tareas | 4 |
| Documentación | 2 tareas |
| Comunicación | 1 tarea |
| Validación | 1 tarea |
| Estimación total | ~4-6 horas |

---

## Tareas de Documentación

### FE-001: Actualizar Documentación OpenAPI/Swagger

**Descripción:** Verificar y actualizar la documentación de la API si hay cambios en las respuestas.

**Archivos:**
- Revisar: `src/Controller/V1/Schemas/*.php`
- Revisar: `config/packages/nelmio_api_doc.yaml`

**Acciones:**
```markdown
1. Revisar que los schemas OpenAPI reflejen la estructura de respuesta
2. Verificar que no hay campos nuevos que documentar
3. Verificar que no hay campos eliminados que actualizar
4. Regenerar documentación si es necesario
```

**Verificación:**
```bash
# Generar documentación
php bin/console nelmio:apidoc:dump --format=json > api-doc.json

# Comparar con versión anterior
diff api-doc.json api-doc-previous.json
```

**Acceptance Criteria:**
- [ ] Documentación OpenAPI está actualizada
- [ ] No hay campos sin documentar
- [ ] Ejemplos de respuesta son correctos

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 1-2 horas

**Dependencias:** BE-019 (después de integración)

---

### FE-002: Crear Changelog para Consumidores

**Descripción:** Documentar los cambios (si los hay) para los equipos que consumen la API.

**Archivos:**
- Crear/Actualizar: `CHANGELOG.md` o `docs/API_CHANGES.md`

**Contenido esperado:**
```markdown
# API Changes - v2.x.x (REFACTOR-002)

## Summary
Internal refactoring of aggregation layer. No breaking changes to API contracts.

## Changes

### Response Structure
- **No changes**: Response JSON structure remains identical
- **Performance**: Parallel aggregation may improve response times

### New Internal Metadata (optional, if enabled)
If debug mode is enabled, responses may include:
```json
{
  "_meta": {
    "aggregatorStats": {
      "total": 7,
      "successful": 7,
      "failed": 0,
      "totalTime": 0.234
    }
  }
}
```

### Deprecations
- None

### Breaking Changes
- None

## Migration Guide
No migration required. This is an internal refactoring.
```

**Acceptance Criteria:**
- [ ] Changelog documenta cambios (o ausencia de ellos)
- [ ] Equipo de apps móviles notificado
- [ ] No hay breaking changes no documentados

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 1-2 horas

**Dependencias:** BE-019

---

## Tareas de Comunicación

### FE-003: Notificar a Equipos Consumidores

**Descripción:** Comunicar a los equipos de aplicaciones móviles sobre el refactoring.

**Acciones:**
```markdown
1. Enviar email/slack a equipos de iOS y Android
2. Incluir:
   - Resumen del cambio (refactoring interno)
   - Confirmación de que no hay breaking changes
   - Fecha de deploy prevista
   - Contacto para dudas
3. Solicitar validación en staging antes de producción
```

**Template de comunicación:**
```markdown
Subject: [SNAAPI] Internal Refactoring - No Action Required

Hi team,

We're deploying an internal refactoring of SNAAPI's aggregation layer.

**What's changing:**
- Internal architecture improvements for scalability
- Better parallel processing of data aggregation

**What's NOT changing:**
- API endpoints
- Request/Response formats
- Authentication

**When:**
- Staging: [DATE]
- Production: [DATE + X days]

**Action needed:**
Please validate your apps work correctly with staging after [DATE].

Questions? Contact: [CONTACT]
```

**Acceptance Criteria:**
- [ ] Equipos notificados
- [ ] Fecha de deploy comunicada
- [ ] Canal de feedback establecido

**Estimación:**
- Complejidad: S (Small)
- Tiempo: 1 hora

**Dependencias:** Ninguna (puede hacerse en paralelo)

---

## Tareas de Validación

### FE-004: Validar Contratos con Aplicaciones Cliente

**Descripción:** Verificar que las aplicaciones cliente funcionan correctamente con el nuevo código.

**Acciones:**
```markdown
1. Desplegar en staging
2. Ejecutar suite de tests de contrato
3. Solicitar a equipos de apps que validen manualmente
4. Revisar métricas de errores en staging
```

**Verificación:**
```bash
# Tests de contrato (si existen)
./vendor/bin/phpunit tests/Contract/

# Comparar respuestas staging vs producción
curl https://staging.api.example.com/editorials/4433 > staging.json
curl https://api.example.com/editorials/4433 > prod.json
diff staging.json prod.json
```

**Acceptance Criteria:**
- [ ] Tests de contrato pasan
- [ ] No hay diferencias en respuestas
- [ ] Equipos de apps confirman OK

**Estimación:**
- Complejidad: M (Medium)
- Tiempo: 2-3 horas

**Dependencias:** BE-019, Deploy a staging

---

## Resumen de Estimaciones

| Tarea | Tiempo |
|-------|--------|
| FE-001: Actualizar OpenAPI | 1-2 horas |
| FE-002: Crear Changelog | 1-2 horas |
| FE-003: Notificar equipos | 1 hora |
| FE-004: Validar contratos | 2-3 horas |
| **Total** | **~5-8 horas (~1 día)** |

---

## Orden de Ejecución

```
Antes del deploy:
├── FE-003: Notificar equipos (puede hacerse temprano)
└── FE-001: Actualizar OpenAPI (después de BE-019)

Durante staging:
├── FE-002: Crear Changelog
└── FE-004: Validar contratos

Después del deploy:
└── Monitorear métricas
```

---

**Próximo paso**: 32_tasks_qa.md (Tareas QA)
