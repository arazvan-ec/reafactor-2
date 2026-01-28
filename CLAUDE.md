# CLAUDE.md - Workflow Enforcement Rules

> **MANDATORY**: All requests MUST go through the workflow system before execution.

## Workflow Enforcement

### Rule 1: All Requests Pass Through Workflow

**CRITICAL**: Before executing ANY request from the user, you MUST:

1. **Identify the workflow type** based on the request:
   - Feature development -> Use `default.yaml` or `task-breakdown.yaml`
   - Bug fixes -> Use `implementation-only.yaml`
   - Simple tasks -> Use `implementation-only.yaml`

2. **Read the appropriate workflow** from `.ai/extensions/workflows/`

3. **Follow the workflow stages** in order:
   - Planning -> Implementation -> Review -> Compound

4. **Update state** in `50_state.md` at each stage

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
| New feature | `default.yaml` | Full workflow: Planning -> Backend -> Frontend -> QA |
| Complex feature | `task-breakdown.yaml` | Extended planning with detailed task breakdown |
| Bug fix | `implementation-only.yaml` | Direct implementation without full planning |
| Refactoring | `implementation-only.yaml` | Direct implementation |
| Documentation | `implementation-only.yaml` | Direct implementation |

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
