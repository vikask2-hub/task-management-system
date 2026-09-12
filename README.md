# Task Management System

[![Live demo](https://img.shields.io/badge/Live_Demo-tech4projects.online-2563eb?style=for-the-badge)](https://tech4projects.online/tms)
[![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20?logo=laravel)](https://laravel.com)
[![Tests](https://img.shields.io/badge/Tests-PHPUnit-22c55e)](.github/workflows/tests.yml)

A focused field-operations workspace for General Managers, Assistant Managers, and Business Development Executives. It deliberately keeps the product surface small: tasks, verification, and reports for managers; tasks for BDEs.

## Product highlights

- Role-specific workspaces for GM, AM, and BDE stakeholders
- Task assignment, priority, scheduling, comments, status transitions, and completion evidence
- Manager verification queue and operational reporting
- AM-managed BDE creation with scoped team ownership
- Business-unit and hospital context with auditable task history
- Responsive UI, demo data, and comprehensive workflow tests

## Stack

PHP 8.3+ · Laravel 13 · Blade · Tailwind CSS 4 · SQLite/MySQL · PHPUnit

## Run locally

```bash
git clone https://github.com/vikask2-hub/task-management-system.git
cd task-management-system
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/tms`. Use the GM, AM, or BDE demo buttons to inspect each permission boundary.

## Quality and security

Status transitions are server-controlled, management views are scope-aware, authentication is rate limited, and all state-changing requests use validation and CSRF protection. Production credentials and runtime data are excluded.

---

Built by [Vikas Kaithia](https://github.com/vikask2-hub) · [View the complete product portfolio](https://tech4projects.online/)
