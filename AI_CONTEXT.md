# 🧠 AI Context & Developer Guidelines - TimeSloth (Strict Mode)

> **CRITICAL INSTRUCTION:** This file contains the ABSOLUTE TRUTH about the project architecture. Read it before generating any code.

## 1. Core Philosophy: Quality over Speed
* **NO TRUNCATION:** Never shorten code with comments like `// ... rest of code`. Always generate full, functional files.
* **NO RUSH:** Do not generate multiple complex files in one response. Ask for confirmation after every file.
* **PRESERVATION:** If a file had 120 lines of logic, the refactored version must preserve that logic. Do not delete features (like holiday merging or validation) accidentally.

## 2. Project Architecture
* **App:** TimeSloth (Time Tracking) & CATSloth (Project Budgeting).
* **Stack:** PHP 8.5 (Alpine), Vue.js 3, SQLite.
* **Pattern:** Custom MVC (No Framework).
    * **Controller:** Handles Request -> Calls Service -> Renders View/JSON. (NO SQL HERE).
    * **Service:** Business Logic (Validation, Calculation). Calls Repository.
    * **Repository:** Pure SQL Queries. Typed return values.

## 3. The "Strict Naming" Convention (Refactoring Target)
The application is split into 4 domains. Every file (PHP View, JS) MUST carry its domain prefix.

| Domain | Folder Path | File Prefix | Example |
| :--- | :--- | :--- | :--- |
| **TimeSloth** (Core) | `/app/templates/timesloth/` | `ts_` | `ts_dashboard.php` |
| **CATSloth** (Projects) | `/app/templates/catsloth/` | `cs_` | `cs_dashboard.php` |
| **Admin** | `/app/templates/admin/` | `adm_` | `adm_dashboard.php` |
| **User/Auth** | `/app/templates/user/` | `usr_` / `auth_` | `usr_settings.php`, `auth_login.php` |

### Controller Mapping
* `TimeSlothController.php` (Core Logic)
* `CatsSlothController.php` (Project Logic)
* `AdminController.php` (Admin Logic)
* `UserController.php` (Auth & Settings Logic)

## 4. Coding Standards (DO NOT BREAK)
1.  **Strict Types:** Every PHP file MUST start with `declare(strict_types=1);`.
2.  **Type Hinting:** Every method argument and return type MUST be typed (e.g., `public function get(int $id): ?array`).
3.  **Vue Delimiters:** Always use `delimiters: ['[[', ']]']` to avoid PHP conflicts.
4.  **Pathing:** Views are rendered via `$this->render('timesloth/ts_dashboard')`.
5.  **Global Data:** Frontend data is passed via `window.slothData` object in the PHP view footer.

## 5. Database Schema (SQLite)
* **`users`**: `id`, `username`, `password_hash`, `is_admin`, `is_active`, `is_cats_user`, `settings` (JSON).
* **`entries`**: `id`, `user_id`, `date_str`, `data` (JSON), `status` (F/U/K), `comment`, `status_note`.
* **`cats_projects`**: `id`, `psp_element`, `customer_name`, `yearly_budget_hours`, `start_date`, `end_date`.
* **`cats_allocations`**: `project_id`, `user_id`, `share_weight`, `joined_at`, `left_at`.
* **`cats_bookings`**: `project_id`, `user_id`, `month`, `hours`.