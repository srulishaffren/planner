-- schema.sql for Gapaika Planner
-- Drop tables if needed (be careful in production)

-- Migration for existing databases (run this if you already have the tasks table):
-- ALTER TABLE tasks ADD COLUMN notes TEXT AFTER text;

DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS journal_entries;
DROP TABLE IF EXISTS tasks;


-- Tasks table
CREATE TABLE tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_date DATE NOT NULL,
  text TEXT NOT NULL,
  notes TEXT,
  priority ENUM('A','B','C','D') NOT NULL DEFAULT 'C',
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('todo','planning','in_progress','waiting','done') NOT NULL DEFAULT 'todo',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_date (task_date),
  INDEX idx_date_priority (task_date, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Journal entries table
CREATE TABLE journal_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_date DATE NOT NULL UNIQUE,
  content MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Attachments table
CREATE TABLE attachments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  journal_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Monthly index entries (for flagging significant journal entries)
CREATE TABLE journal_index (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_date DATE NOT NULL,
  summary VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_entry_date (entry_date),
  INDEX idx_year_month (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Yartzheits (Hebrew date anniversaries of death)
CREATE TABLE yartzheits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  hebrew_month INT NOT NULL,
  hebrew_day INT NOT NULL,
  relationship VARCHAR(100),
  notes TEXT,
  created_at DATETIME NOT NULL,
  INDEX idx_hebrew_date (hebrew_month, hebrew_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- User settings (key-value store)
CREATE TABLE settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default location settings
INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
  ('location_name', 'Har Bracha, Israel', NOW()),
  ('latitude', '32.1133', NOW()),
  ('longitude', '35.3097', NOW()),
  ('timezone', 'Asia/Jerusalem', NOW()),
  ('elevation', '829', NOW());

