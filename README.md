# ArchonAI — Intelligence for Software Architecture 

> **Understand Any Codebase. Discover What Connects.**  
> An open-source, AI-powered software architecture intelligence platform built for Hacktoberfest 2026.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![XAMPP Ready](https://img.shields.io/badge/XAMPP-Apache%20%7C%20MariaDB-FB7A24?style=flat&logo=xampp&logoColor=white)](https://www.apachefriends.org/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-00758F?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Hacktoberfest](https://img.shields.io/badge/Hacktoberfest-2026-FF8AE2?style=flat)](https://hacktoberfest.com/)

---

## 🌟 Highlights

- **Repository Explorer**: Inspect directory trees, languages, and source files directly from GitHub.
- **Architecture Visualizer**: Interactive module and dependency graphs rendered with Cytoscape.js.
- **AI Code Assistant**: Evidence-backed codebase Q&A powered by local open-weight coding models (Ollama).
- **Contribution Navigator**: Discover beginner-friendly GitHub issues with AI-assisted file recommendations.
- **Code History Explorer**: Browse commits, analyze diffs, and inspect changelog narratives.

---

## 🚀 Quick Start (XAMPP on Windows)

### 1. Prerequisites
- **XAMPP** with Apache & MySQL/MariaDB running.
- **PHP 8.2+** with `pdo_mysql`, `curl`, and `mbstring` enabled.
- **Ollama** (optional, for local AI code assistant features).

### 2. Setup Database
1. Open phpMyAdmin at `http://localhost/phpmyadmin`.
2. Locate or create database `archonai`.
3. Import `database/schema.sql` (or run initialization from the setup assistant).
4. (Optional) Import `database/seed.sql` for demo accounts and expressjs sample graph.

### 3. Environment Configuration
Copy `.env.example` to `.env`:
```ini
APP_NAME="ArchonAI"
APP_ENV="development"
APP_URL="http://localhost/Hacktoberfest_2026/archonai"

DB_CONNECTION="mysql"
DB_HOST="127.0.0.1"
DB_PORT=3306
DB_DATABASE="archonai"
DB_USERNAME="root"
DB_PASSWORD=""

# Optional GitHub Token for higher rate limits
GITHUB_API_TOKEN=""

# Local Ollama Inference
OLLAMA_BASE_URL="http://127.0.0.1:11434"
OLLAMA_MODEL="codellama"
```

### 4. Run the Application
Open your browser and navigate to:
```
http://localhost/Hacktoberfest_2026/archonai/
```
(or `http://localhost/archonai/` if directory mapped).

---

## 👥 Hacktoberfest 2026 Contributing
Please read [CONTRIBUTING.md](CONTRIBUTING.md) for branch guidelines, coding standards, and PR workflows.
