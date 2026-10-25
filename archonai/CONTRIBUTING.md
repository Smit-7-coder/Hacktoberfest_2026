# Contributing to ArchonAI

Thank you for contributing to ArchonAI during Hacktoberfest 2026!

## 🌿 Branch Workflow

- `main` — Production-ready, stable codebase.
- `feature/dashboard` — Dashboard metrics & navigation.
- `feature/repository-analysis` — GitHub importer & syntax parsing.
- `feature/ai-assistant` — Context retrieval & Ollama inference.
- `feature/architecture-graph` — Cytoscape.js dependency graph.
- `feature/contribution-navigator` — GitHub issues & bookmarks.

## 📋 Rules
1. Never commit `.env` or credentials.
2. Keep `database/schema.sql` updated with any schema change.
3. Use prepared statements with PDO for all queries.
4. Escape all frontend output with `htmlspecialchars()`.
5. Keep business logic in `src/` services and repositories.
