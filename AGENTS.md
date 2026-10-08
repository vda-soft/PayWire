# Agents Guidelines

> **Instruction budget:** this file must stay under **32,768 bytes** (Codex's default
> `project_doc_max_bytes`, shared with the nested `AGENTS.md` files below it) — anything past
> that byte offset never reaches the agent. Keep hard rules and routing here. See
> [`.agents/instructions`](.agents/instructions/projectInstructions.md).

## Always

- Trust explicit instructions: when project or user instructions clearly specify a command, file, or procedure, follow them without reading other files just to confirm. Consult additional sources only when the instruction is unclear, conflicting, potentially outdated, or the specified action fails.
- Check `.agents/specs/` for existing specs before modifying a module.
- Enter plan mode for non-trivial tasks with 3+ steps or architectural decisions.
- Preserve behavior unless the user or a spec explicitly asks for a behavior change.
- Keep changes minimal, focused, and integrated through real call sites.
- Use the closest package/module `AGENTS.md` for local architecture, imports, and validation commands.
- Use modern PHP features and best practices released in PHP 8.4.
- If unsure about a package version, check `composer.json` / `composer.lock` instead of assuming.

## Ask First

- Ask before reducing scope, changing architecture, changing public contracts, adding production dependencies, or touching multiple modules in a way not covered by an existing spec.

## Never

- Never edit generated files by hand.
- Never create direct ORM relationships between modules.
- Never commit credentials, raw tokens, private keys, local-only ops files, or fork-only infrastructure notes into upstream-friendly branches.

## Scripts

Choose the right script:

- `composer ci` — run full CI pipeline (tests, static analysis, architecture)
- `composer phpunit` for run tests
  - `composer phpunit -- --filter PaymentTest` — run specific test
  - `composer phpunit -- --testdox` — verbose output
- `composer phpstan` for static analysis
  - `composer phpstan -- --no-progress` — disable progress bar
  - `composer phpstan -- --level=max` — strictest level
- `composer architecture` for architecture validation

## Core Principles

- **Domain First**: Keep domain clean and simple.
- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.
- **Self-Documenting Code**: Don't use comments unless absolutely necessary. Class, method, and variable names should clearly express intent.

## Workflow Orchestration

1.  **Spec-first**: Enter plan mode for non-trivial tasks (3+ steps or architectural decisions). Check `.agents/specs/` before coding; name new spec files `{YYYY-MM-DD}-{kebab-case-title}.md`. Skip for small fixes.
2.  **Subagent strategy**: Use subagents liberally to keep main context clean. Offload research and parallel analysis. One task per subagent.
3.  **Self-improvement**: After corrections, scan `.agents/lessons.md`; update one tagged lesson record + index row or the relevant AGENTS.md. Never bulk-read lessons.
4.  **Verification**: Run tests, check build, suggest user verification. Ask: "Would a staff engineer approve this?"
5.  **Elegance**: For non-trivial changes, pause and ask "is there a more elegant way?" Skip for simple fixes.

## Monorepo Structure

### Core (`src/PayWire/Core/`)

Core module, framework-agnostic

### Gateways (`src/PayWire/Gateway/`)

Gateways to handle specific partner, ex. `StripeGateway`, `OfflineGateway`, `PayUGateway`, `Przelewy24Gateway`, `PaypalGateway`.

### Bridges (`src/PayWire/Bridge/`)

Bridges to glue Core with Gateways, ex. Symfony, Laravel. Uses specific frameworks dependencies such as Doctrine ORM for Symfony.