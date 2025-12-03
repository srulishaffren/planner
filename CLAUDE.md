# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Gapaika Planner is a personal daily planner web application built with PHP and vanilla JavaScript. It provides task management with priority levels (A/B/C/D) and daily journal entries.

## Architecture

**Single-page app with PHP backend:**
- `index.php` - Main entry point containing login form, app shell HTML, and all frontend JavaScript
- `api.php` - JSON API endpoint handling all data operations (tasks CRUD, journal, reordering)
- `config.php` - Database credentials and PDO connection helper
- `logout.php` - Session destruction and redirect

**Authentication:**
- Two-layer auth: HTTP Basic Auth via `.htaccess` plus PHP session-based login
- Session variable `$_SESSION['planner_logged_in']` gates access to the app

**Database (MariaDB):**
- Schema defined in `schema.sql`
- `tasks` - id, task_date (DATE), text, priority ENUM(A/B/C/D), sort_order, status ENUM(todo/planning/in_progress/waiting/done), timestamps
- `journal_entries` - id, entry_date (DATE, unique), content (MEDIUMTEXT), timestamps
- `attachments` - prepared for future use, FK to journal_entries

**API Actions (POST to api.php):**
- `get_day` - Load tasks and journal for a date
- `add_task` - Create new task
- `update_task` - Modify task fields (text, priority, status)
- `delete_task` - Remove task
- `reorder_tasks` - Update sort_order for drag-and-drop
- `save_journal` - Upsert journal entry

**Task Statuses:** todo, planning, in_progress, waiting, done

## Development

No build process - edit PHP/JS files directly. Test by loading in browser with valid auth.

**Deployment:** Plesk server with Apache, PHP 7.4+, MariaDB
