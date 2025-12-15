# Gapaika Planner

A personal daily planner web application with task management, journaling, Hebrew calendar integration, and more.

## Features

- **Task Management**: Priority-based tasks (A/B/C/D) with drag-and-drop reordering
- **Task Statuses**: todo, planning, in_progress, waiting, done
- **Recurring Tasks**: Flexible patterns (day of month, day of week, interval-based)
- **Daily Journal**: Rich text journaling with search functionality
- **Journal Index**: Flag and summarize important entries for quick monthly review
- **Attachments**: File uploads linked to journal entries
- **Hebrew Calendar**: Hebrew date display, zmanim (prayer times), holidays
- **Yartzheits**: Track Hebrew date anniversaries
- **Calendar Import**: Import events from ICS calendar feeds (Google Calendar, etc.)
- **Jira Integration**: OAuth 2.0 connection to view assigned issues
- **Categories**: Color-coded task categorization
- **Export**: Full data export to JSON
- **Keyboard Shortcuts**: Ctrl+Enter to save journal entries

## Tech Stack

- **Backend**: PHP 7.4+ with PDO/MySQL
- **Frontend**: Vanilla JavaScript (single-page app)
- **Database**: MariaDB/MySQL
- **External APIs**: Hebcal (Hebrew calendar), Sefaria (Torah content)

## Project Structure

```
├── index.php           # Main entry point (HTML + JS frontend)
├── api.php             # JSON API endpoint
├── config.example.php  # Configuration template
├── schema.sql          # Database schema
├── logout.php          # Session logout
├── oauth-callback.php  # Jira OAuth callback
└── tests/              # Playwright tests
```

## Setup

1. Copy `config.example.php` to `config.php` and fill in your values:
   - Database credentials
   - Location settings (for zmanim)
   - App login credentials

2. Create the database and run `schema.sql`

3. Configure your web server (Apache/Nginx) to serve the project

## Security

This repository is configured to exclude sensitive files:

- `config.php` - Contains database credentials and password hashes (gitignored)
- `jira_tokens.json` - OAuth tokens (gitignored)
- `.htaccess` - Server configuration (gitignored)
- `uploads/` - User uploads (gitignored)
- `logs/` - Error logs (gitignored)
- `sessions/` - PHP session files (gitignored)

Only the template file `config.example.php` is tracked, which contains placeholder values.

## Authentication

Two-layer authentication:
1. HTTP Basic Auth via `.htaccess` (server level)
2. PHP session-based login (application level)

## API

All data operations go through `api.php` with JSON request/response:

- `get_day` - Load tasks and journal for a date
- `add_task`, `update_task`, `delete_task` - Task CRUD
- `reorder_tasks` - Update sort order
- `save_journal` - Upsert journal entry
- `search_journal` - Full-text search
- `get_hebrew_info`, `get_zmanim` - Hebrew calendar data
- `export_all` - Full data export

CSRF protection is enforced on all API requests.

## License

Private/Personal Use
