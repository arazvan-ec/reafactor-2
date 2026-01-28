# CLAUDE.md - Workflow Enforcement Rules

> **MANDATORY**: All requests MUST go through the workflow system before execution.

## Workflow Enforcement

### Rule 1: All Requests Pass Through Workflow

**CRITICAL**: Before executing ANY request from the user, you MUST:

1. **Identify the workflow type** based on the request:
   - **Feature development** -> Use `task-breakdown.yaml` (ALWAYS for new features)
   - **Bug fixes** -> Use `implementation-only.yaml`
   - **Simple/trivial tasks** -> Use `implementation-only.yaml`

2. **Read the appropriate workflow** from `.ai/extensions/workflows/`

3. **Follow the workflow stages** in order:
   - For features: Planning (task-breakdown) -> Implementation (default) -> Review -> Compound
   - For bug fixes: Direct implementation -> Review

4. **Update state** in `50_state.md` at each stage

### Rule 1.1: Default Workflow is task-breakdown

**For ANY new feature or significant change**, ALWAYS use `task-breakdown.yaml` first:

1. **Why task-breakdown first?**
   - Creates exhaustive documentation (10 documents)
   - Each task has: code examples, acceptance criteria, verification commands
   - Provides time estimates per task and total
   - Maps dependencies explicitly
   - Reduces ambiguity and re-work

2. **Workflow sequence for features:**
   ```
   Step 1: task-breakdown.yaml (Planning ONLY)
           → Creates: 00_requirements, 10_architecture, 15_data_model,
                      20_api_contracts, 30_tasks_backend, 31_tasks_frontend,
                      32_tasks_qa, 35_dependencies, FEATURE_X.md, 50_state.md

   Step 2: default.yaml (Implementation)
           → Backend, Frontend, QA follow the detailed tasks from Step 1
   ```

3. **When to skip task-breakdown:**
   - Bug fixes (use `implementation-only.yaml`)
   - Trivial changes < 1 hour (use `implementation-only.yaml`)
   - Documentation-only changes (use `implementation-only.yaml`)

### Rule 2: Mandatory Readings Before Any Work

Before starting ANY task, you MUST read these files in order:

```
1. .ai/project/context.md           # Project context
2. .ai/project/config.yaml          # Project configuration
3. .ai/extensions/workflows/*.yaml   # Available workflows
4. .ai/extensions/rules/*.md         # Project rules
```

### Rule 3: Role-Based Execution

Each task must be executed by the appropriate role:

| Task Type | Role | Role File |
|-----------|------|-----------|
| Planning | Planner | plugins/multi-agent-workflow/core/roles/planner.md |
| Backend | Backend Engineer | plugins/multi-agent-workflow/core/roles/backend.md |
| Frontend | Frontend Engineer | plugins/multi-agent-workflow/core/roles/frontend.md |
| Review | QA | plugins/multi-agent-workflow/core/roles/qa.md |

### Rule 4: State Management

All work must update `50_state.md`:

```markdown
## [Role Name]
**Status**: [PENDING | IN_PROGRESS | BLOCKED | COMPLETED]
**Checkpoint**: [Current checkpoint]
**Notes**: [Relevant notes]
```

### Rule 5: Git Workflow

After completing each checkpoint:

1. Stage changes: `git add .`
2. Commit with format: `[role][feature-id] Description`
3. Push to feature branch

## Workflow Selection Guide

| Request Type | Workflow | Description |
|--------------|----------|-------------|
| **New feature** | `task-breakdown.yaml` → `default.yaml` | First detailed planning, then implementation |
| **Complex feature** | `task-breakdown.yaml` → `default.yaml` | Same as above (always use task-breakdown) |
| **Bug fix** | `implementation-only.yaml` | Direct implementation without planning |
| **Refactoring** | `implementation-only.yaml` | Direct implementation |
| **Documentation** | `implementation-only.yaml` | Direct implementation |
| **Trivial task** | `implementation-only.yaml` | Tasks < 1 hour |

### Feature Development Flow (MANDATORY)

```
┌─────────────────────────────────────────────────────────────┐
│                    NEW FEATURE REQUEST                       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ PHASE 1: task-breakdown.yaml (Planning)                     │
│                                                             │
│ Creates 10 documents:                                       │
│ • 00_requirements_analysis.md (what & why)                  │
│ • 10_architecture.md (DDD layers, components)               │
│ • 15_data_model.md (database schema)                        │
│ • 20_api_contracts.md (all endpoints with examples)         │
│ • 30_tasks_backend.md (detailed tasks with code)            │
│ • 31_tasks_frontend.md (detailed tasks with code)           │
│ • 32_tasks_qa.md (test cases)                               │
│ • 35_dependencies.md (task dependencies map)                │
│ • FEATURE_X.md (executive summary)                          │
│ • 50_state.md (state tracking)                              │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ PHASE 2: default.yaml (Implementation)                      │
│                                                             │
│ Roles follow detailed tasks from Phase 1:                   │
│ • Backend: reads 30_tasks_backend.md, implements step by step│
│ • Frontend: reads 31_tasks_frontend.md, implements          │
│ • QA: reads 32_tasks_qa.md, validates                       │
└─────────────────────────────────────────────────────────────┘
```

## Quick Commands

```bash
# Start planning a feature
Read: .ai/extensions/workflows/default.yaml
Create: .ai/project/features/[FEATURE_ID]/FEATURE_[FEATURE_ID].md

# Check current status
Read: .ai/project/features/[FEATURE_ID]/50_state.md

# Sync with team
Run: .ai/extensions/scripts/git_sync.sh [FEATURE_ID]
```

## Enforcement Checklist

Before executing ANY request, verify:

- [ ] Workflow identified and read
- [ ] Context files read
- [ ] Role assigned
- [ ] Feature directory exists (if applicable)
- [ ] State file initialized (if applicable)

## Plugin Documentation

For complete documentation, see:
- `QUICKSTART.md` - 5-minute onboarding
- `TUTORIAL.md` - Complete tutorial
- `INDEX.md` - Full documentation index
- `GLOSSARY.md` - Terms and definitions
- `README.md` - Complete plugin documentation

## Extensions

Project-specific extensions are in `.ai/extensions/`:
- `rules/` - Project rules (customize here)
- `workflows/` - Custom workflows (customize here)
- `trust/` - Trust model configuration
- `scripts/` - Utility scripts

---

**Version**: 1.0.0
**Plugin**: multi-agent-workflow v2.0.0
**Enforcement**: MANDATORY for all requests
