<?php
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (empty($_SESSION['planner_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

// Handle both JSON and multipart/form-data requests
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'multipart/form-data') !== false) {
    $input = $_POST;
    $action = $_POST['action'] ?? '';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

try {
    $pdo = get_pdo();

    switch ($action) {
        case 'get_day':
            $date = $input['date'] ?? date('Y-m-d');
            $tasks = load_tasks($pdo, $date);
            $journal = load_journal($pdo, $date);
            echo json_encode([
                'success' => true,
                'tasks' => $tasks,
                'journal' => $journal,
            ]);
            break;

        case 'add_task':
            $date = $input['date'] ?? date('Y-m-d');
            $text = trim($input['text'] ?? '');
            $priority = $input['priority'] ?? 'C';
            if ($text === '') {
                echo json_encode(['success' => false, 'error' => 'Empty text']);
                break;
            }
            add_task($pdo, $date, $text, $priority);
            $tasks = load_tasks($pdo, $date);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;

        case 'update_task':
            $id = (int)($input['id'] ?? 0);
            $fields = $input['fields'] ?? [];
            update_task($pdo, $id, $fields);
            $date = get_task_date($pdo, $id);
            $tasks = load_tasks($pdo, $date);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;

        case 'delete_task':
            $id = (int)($input['id'] ?? 0);
            $date = get_task_date($pdo, $id);
            delete_task($pdo, $id);
            $tasks = load_tasks($pdo, $date);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;

        case 'copy_task':
            $id = (int)($input['id'] ?? 0);
            $targetDate = $input['target_date'] ?? '';
            if (!$id || !$targetDate) {
                echo json_encode(['success' => false, 'error' => 'Missing task ID or target date']);
                break;
            }
            $newId = copy_task($pdo, $id, $targetDate);
            $tasks = load_tasks($pdo, $targetDate);
            echo json_encode(['success' => true, 'tasks' => $tasks, 'new_task_id' => $newId]);
            break;

        case 'get_uncompleted_tasks':
            $date = $input['date'] ?? date('Y-m-d');
            $uncompleted = get_uncompleted_tasks($pdo, $date);
            echo json_encode(['success' => true, 'uncompleted_tasks' => $uncompleted]);
            break;

        case 'carry_forward_tasks':
            $fromDate = $input['from_date'] ?? '';
            $toDate = $input['to_date'] ?? '';
            if (!$fromDate || !$toDate) {
                echo json_encode(['success' => false, 'error' => 'Missing from_date or to_date']);
                break;
            }
            $copied = carry_forward_tasks($pdo, $fromDate, $toDate);
            $tasks = load_tasks($pdo, $toDate);
            echo json_encode(['success' => true, 'tasks' => $tasks, 'copied_count' => $copied]);
            break;

        case 'reorder_tasks':
            $date = $input['date'] ?? date('Y-m-d');
            $priority = $input['priority'] ?? 'C';
            $orderedIds = $input['ordered_ids'] ?? [];
            reorder_tasks($pdo, $date, $priority, $orderedIds);
            $tasks = load_tasks($pdo, $date);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;

        case 'save_journal':
            $date = $input['date'] ?? date('Y-m-d');
            $content = $input['content'] ?? '';
            save_journal($pdo, $date, $content);
            echo json_encode(['success' => true]);
            break;

        case 'upload_attachment':
            $date = $input['date'] ?? date('Y-m-d');
            if (empty($_FILES['file'])) {
                echo json_encode(['success' => false, 'error' => 'No file uploaded']);
                break;
            }
            $result = upload_attachment($pdo, $date, $_FILES['file']);
            if ($result['success']) {
                $attachments = get_attachments($pdo, $date);
                echo json_encode(['success' => true, 'attachment' => $result['attachment'], 'attachments' => $attachments]);
            } else {
                echo json_encode($result);
            }
            break;

        case 'get_attachments':
            $date = $input['date'] ?? date('Y-m-d');
            $attachments = get_attachments($pdo, $date);
            echo json_encode(['success' => true, 'attachments' => $attachments]);
            break;

        case 'delete_attachment':
            $id = (int)($input['id'] ?? 0);
            $date = $input['date'] ?? '';
            $result = delete_attachment($pdo, $id);
            if ($result['success'] && $date) {
                $attachments = get_attachments($pdo, $date);
                echo json_encode(['success' => true, 'attachments' => $attachments]);
            } else {
                echo json_encode($result);
            }
            break;

        case 'add_index_entry':
            $date = $input['date'] ?? date('Y-m-d');
            $summary = trim($input['summary'] ?? '');
            if ($summary === '') {
                echo json_encode(['success' => false, 'error' => 'Summary required']);
                break;
            }
            add_index_entry($pdo, $date, $summary);
            $entries = get_index_entries($pdo, $date);
            echo json_encode(['success' => true, 'index_entries' => $entries]);
            break;

        case 'get_index_entries':
            $date = $input['date'] ?? date('Y-m-d');
            $entries = get_index_entries($pdo, $date);
            echo json_encode(['success' => true, 'index_entries' => $entries]);
            break;

        case 'delete_index_entry':
            $id = (int)($input['id'] ?? 0);
            $date = $input['date'] ?? '';
            delete_index_entry($pdo, $id);
            $entries = $date ? get_index_entries($pdo, $date) : [];
            echo json_encode(['success' => true, 'index_entries' => $entries]);
            break;

        case 'get_month_index':
            $year = (int)($input['year'] ?? date('Y'));
            $month = (int)($input['month'] ?? date('n'));
            $entries = get_month_index($pdo, $year, $month);
            echo json_encode(['success' => true, 'index_entries' => $entries]);
            break;

        case 'search_journal':
            $query = trim($input['query'] ?? '');
            if (strlen($query) < 2) {
                echo json_encode(['success' => false, 'error' => 'Query too short']);
                break;
            }
            $results = search_journal($pdo, $query);
            echo json_encode(['success' => true, 'results' => $results]);
            break;

        case 'get_hebrew_info':
            $date = $input['date'] ?? date('Y-m-d');
            $hebrewInfo = get_hebrew_info($pdo, $date);
            echo json_encode(['success' => true, 'hebrew_info' => $hebrewInfo]);
            break;

        case 'get_zmanim':
            $date = $input['date'] ?? date('Y-m-d');
            $zmanim = get_zmanim($date);
            echo json_encode(['success' => true, 'zmanim' => $zmanim]);
            break;

        case 'get_yartzheits':
            $yartzheits = get_all_yartzheits($pdo);
            echo json_encode(['success' => true, 'yartzheits' => $yartzheits]);
            break;

        case 'add_yartzheit':
            $name = trim($input['name'] ?? '');
            $hebrewMonth = (int)($input['hebrew_month'] ?? 0);
            $hebrewDay = (int)($input['hebrew_day'] ?? 0);
            $relationship = trim($input['relationship'] ?? '');
            $notes = trim($input['notes'] ?? '');
            if ($name === '' || $hebrewMonth < 1 || $hebrewMonth > 13 || $hebrewDay < 1 || $hebrewDay > 30) {
                echo json_encode(['success' => false, 'error' => 'Invalid yartzheit data']);
                break;
            }
            add_yartzheit($pdo, $name, $hebrewMonth, $hebrewDay, $relationship, $notes);
            $yartzheits = get_all_yartzheits($pdo);
            echo json_encode(['success' => true, 'yartzheits' => $yartzheits]);
            break;

        case 'delete_yartzheit':
            $id = (int)($input['id'] ?? 0);
            delete_yartzheit($pdo, $id);
            $yartzheits = get_all_yartzheits($pdo);
            echo json_encode(['success' => true, 'yartzheits' => $yartzheits]);
            break;

        case 'get_settings':
            $settings = get_all_settings($pdo);
            echo json_encode(['success' => true, 'settings' => $settings]);
            break;

        case 'save_settings':
            $settings = $input['settings'] ?? [];
            save_settings($pdo, $settings);
            $allSettings = get_all_settings($pdo);
            echo json_encode(['success' => true, 'settings' => $allSettings]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Helper functions

function load_tasks(PDO $pdo, string $date): array {
    $stmt = $pdo->prepare('SELECT id, task_date, text, notes, priority, sort_order, status, created_at, updated_at FROM tasks WHERE task_date = :d ORDER BY priority, sort_order, id');
    $stmt->execute([':d' => $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function load_journal(PDO $pdo, string $date): ?string {
    $stmt = $pdo->prepare('SELECT content FROM journal_entries WHERE entry_date = :d');
    $stmt->execute([':d' => $date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['content'] : '';
}

function add_task(PDO $pdo, string $date, string $text, string $priority): void {
    // find max sort_order for this date+priority
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM tasks WHERE task_date = :d AND priority = :p');
    $stmt->execute([':d' => $date, ':p' => $priority]);
    $max = (int)$stmt->fetchColumn();
    $newSort = $max + 1;
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO tasks (task_date, text, priority, sort_order, status, created_at, updated_at) VALUES (:d, :t, :p, :s, :st, :c, :u)');
    $stmt->execute([
        ':d' => $date,
        ':t' => $text,
        ':p' => $priority,
        ':s' => $newSort,
        ':st' => 'todo',
        ':c' => $now,
        ':u' => $now,
    ]);
}

function get_task_date(PDO $pdo, int $id): string {
    $stmt = $pdo->prepare('SELECT task_date FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $d = $stmt->fetchColumn();
    if (!$d) {
        throw new Exception('Task not found');
    }
    return $d;
}

function update_task(PDO $pdo, int $id, array $fields): void {
    if (!$id) return;
    $allowed = ['text','priority','status','notes'];
    $setParts = [];
    $params = [':id' => $id];
    foreach ($fields as $k => $v) {
        if (!in_array($k, $allowed, true)) continue;
        $setParts[] = "$k = :$k";
        $params[":$k"] = $v;
    }
    if (empty($setParts)) return;
    $setParts[] = "updated_at = :u";
    $params[':u'] = date('Y-m-d H:i:s');
    $sql = 'UPDATE tasks SET ' . implode(', ', $setParts) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function delete_task(PDO $pdo, int $id): void {
    if (!$id) return;
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function copy_task(PDO $pdo, int $id, string $targetDate): int {
    // Get the original task
    $stmt = $pdo->prepare('SELECT text, notes, priority, status FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        throw new Exception('Task not found');
    }

    // Find max sort_order for target date+priority
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM tasks WHERE task_date = :d AND priority = :p');
    $stmt->execute([':d' => $targetDate, ':p' => $task['priority']]);
    $maxSort = (int)$stmt->fetchColumn();

    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO tasks (task_date, text, notes, priority, sort_order, status, created_at, updated_at) VALUES (:d, :t, :n, :p, :s, :st, :c, :u)');
    $stmt->execute([
        ':d' => $targetDate,
        ':t' => $task['text'],
        ':n' => $task['notes'],
        ':p' => $task['priority'],
        ':s' => $maxSort + 1,
        ':st' => 'todo', // Reset status to todo when copying
        ':c' => $now,
        ':u' => $now,
    ]);
    return (int)$pdo->lastInsertId();
}

function get_uncompleted_tasks(PDO $pdo, string $date): array {
    $stmt = $pdo->prepare('SELECT id, task_date, text, notes, priority, sort_order, status FROM tasks WHERE task_date = :d AND status != :done ORDER BY priority, sort_order, id');
    $stmt->execute([':d' => $date, ':done' => 'done']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function carry_forward_tasks(PDO $pdo, string $fromDate, string $toDate): int {
    $uncompleted = get_uncompleted_tasks($pdo, $fromDate);
    $copied = 0;

    foreach ($uncompleted as $task) {
        // Check if the same task text already exists on target date (avoid duplicates)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE task_date = :d AND text = :t');
        $stmt->execute([':d' => $toDate, ':t' => $task['text']]);
        if ((int)$stmt->fetchColumn() > 0) {
            continue; // Skip if already exists
        }

        // Find max sort_order for target date+priority
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM tasks WHERE task_date = :d AND priority = :p');
        $stmt->execute([':d' => $toDate, ':p' => $task['priority']]);
        $maxSort = (int)$stmt->fetchColumn();

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO tasks (task_date, text, notes, priority, sort_order, status, created_at, updated_at) VALUES (:d, :t, :n, :p, :s, :st, :c, :u)');
        $stmt->execute([
            ':d' => $toDate,
            ':t' => $task['text'],
            ':n' => $task['notes'],
            ':p' => $task['priority'],
            ':s' => $maxSort + 1,
            ':st' => 'todo',
            ':c' => $now,
            ':u' => $now,
        ]);
        $copied++;
    }

    return $copied;
}

function reorder_tasks(PDO $pdo, string $date, string $priority, array $orderedIds): void {
    // reassign sort_order based on orderedIds
    $pdo->beginTransaction();
    try {
        $sort = 1;
        $stmt = $pdo->prepare('UPDATE tasks SET sort_order = :s WHERE id = :id AND task_date = :d AND priority = :p');
        foreach ($orderedIds as $id) {
            $stmt->execute([
                ':s' => $sort++,
                ':id' => (int)$id,
                ':d' => $date,
                ':p' => $priority,
            ]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function save_journal(PDO $pdo, string $date, string $content): void {
    $now = date('Y-m-d H:i:s');
    // upsert
    $stmt = $pdo->prepare('SELECT id FROM journal_entries WHERE entry_date = :d');
    $stmt->execute([':d' => $date]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $stmt = $pdo->prepare('UPDATE journal_entries SET content = :c, updated_at = :u WHERE id = :id');
        $stmt->execute([':c' => $content, ':u' => $now, ':id' => $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO journal_entries (entry_date, content, created_at, updated_at) VALUES (:d, :c, :cr, :u)');
        $stmt->execute([':d' => $date, ':c' => $content, ':cr' => $now, ':u' => $now]);
    }
}

function get_or_create_journal_id(PDO $pdo, string $date): int {
    $stmt = $pdo->prepare('SELECT id FROM journal_entries WHERE entry_date = :d');
    $stmt->execute([':d' => $date]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int)$id;
    }
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO journal_entries (entry_date, content, created_at, updated_at) VALUES (:d, :c, :cr, :u)');
    $stmt->execute([':d' => $date, ':c' => '', ':cr' => $now, ':u' => $now]);
    return (int)$pdo->lastInsertId();
}

function upload_attachment(PDO $pdo, string $date, array $file): array {
    global $uploadDir, $maxFileSize, $allowedMimeTypes;

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Upload error'];
    }

    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'error' => 'File too large (max 10MB)'];
    }

    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return ['success' => false, 'error' => 'File type not allowed: ' . $mimeType];
    }

    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'error' => 'Could not create upload directory'];
        }
    }

    // Create date-based subdirectory
    $dateDir = $uploadDir . '/' . $date;
    if (!is_dir($dateDir)) {
        mkdir($dateDir, 0755, true);
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    $uniqueName = $safeName . '_' . uniqid() . ($ext ? '.' . $ext : '');
    $storedPath = $date . '/' . $uniqueName;
    $fullPath = $uploadDir . '/' . $storedPath;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }

    // Get or create journal entry for this date
    $journalId = get_or_create_journal_id($pdo, $date);

    // Insert attachment record
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO attachments (journal_id, original_name, stored_path, mime_type, size_bytes, created_at) VALUES (:jid, :name, :path, :mime, :size, :created)');
    $stmt->execute([
        ':jid' => $journalId,
        ':name' => $file['name'],
        ':path' => $storedPath,
        ':mime' => $mimeType,
        ':size' => $file['size'],
        ':created' => $now,
    ]);

    $attachmentId = (int)$pdo->lastInsertId();

    return [
        'success' => true,
        'attachment' => [
            'id' => $attachmentId,
            'original_name' => $file['name'],
            'stored_path' => $storedPath,
            'mime_type' => $mimeType,
            'size_bytes' => $file['size'],
        ]
    ];
}

function get_attachments(PDO $pdo, string $date): array {
    $stmt = $pdo->prepare('
        SELECT a.id, a.original_name, a.stored_path, a.mime_type, a.size_bytes, a.created_at
        FROM attachments a
        JOIN journal_entries j ON a.journal_id = j.id
        WHERE j.entry_date = :d
        ORDER BY a.created_at DESC
    ');
    $stmt->execute([':d' => $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function delete_attachment(PDO $pdo, int $id): array {
    global $uploadDir;

    if (!$id) {
        return ['success' => false, 'error' => 'Invalid attachment ID'];
    }

    // Get attachment info
    $stmt = $pdo->prepare('SELECT stored_path FROM attachments WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $storedPath = $stmt->fetchColumn();

    if (!$storedPath) {
        return ['success' => false, 'error' => 'Attachment not found'];
    }

    // Delete file
    $fullPath = $uploadDir . '/' . $storedPath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }

    // Delete record
    $stmt = $pdo->prepare('DELETE FROM attachments WHERE id = :id');
    $stmt->execute([':id' => $id]);

    return ['success' => true];
}

// Index entry functions
function add_index_entry(PDO $pdo, string $date, string $summary): void {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO journal_index (entry_date, summary, created_at) VALUES (:d, :s, :c)');
    $stmt->execute([':d' => $date, ':s' => $summary, ':c' => $now]);
}

function get_index_entries(PDO $pdo, string $date): array {
    $stmt = $pdo->prepare('SELECT id, entry_date, summary, created_at FROM journal_index WHERE entry_date = :d ORDER BY created_at');
    $stmt->execute([':d' => $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function delete_index_entry(PDO $pdo, int $id): void {
    if (!$id) return;
    $stmt = $pdo->prepare('DELETE FROM journal_index WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function get_month_index(PDO $pdo, int $year, int $month): array {
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = sprintf('%04d-%02d-31', $year, $month); // MySQL handles overflow gracefully
    $stmt = $pdo->prepare('
        SELECT id, entry_date, summary, created_at
        FROM journal_index
        WHERE entry_date >= :start AND entry_date <= :end
        ORDER BY entry_date, created_at
    ');
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function search_journal(PDO $pdo, string $query): array {
    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare('
        SELECT entry_date, content,
               SUBSTRING(content, GREATEST(1, LOCATE(:q1, content) - 50), 150) AS snippet
        FROM journal_entries
        WHERE content LIKE :q2
        ORDER BY entry_date DESC
        LIMIT 50
    ');
    $stmt->execute([':q1' => $query, ':q2' => $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hebrew calendar functions
function get_hebrew_info(PDO $pdo, string $date): array {
    global $latitude, $longitude, $timezone, $locationName;

    list($year, $month, $day) = explode('-', $date);
    $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 5=Friday, 6=Saturday

    // Try to get location settings from database
    try {
        $settings = get_all_settings($pdo);
        $lat = $settings['latitude'] ?? $latitude;
        $lon = $settings['longitude'] ?? $longitude;
        $tz = $settings['timezone'] ?? $timezone;
    } catch (Exception $e) {
        $lat = $latitude;
        $lon = $longitude;
        $tz = $timezone;
    }

    // Create timezone object for proper time formatting
    $userTimezone = new DateTimeZone($tz);
    $formatTime = function($iso) use ($userTimezone) {
        if (!$iso) return null;
        try {
            $dt = new DateTime($iso);
            $dt->setTimezone($userTimezone);
            return $dt->format('g:i A');
        } catch (Exception $e) {
            return null;
        }
    };

    // Get Hebrew date from Hebcal
    $converterUrl = "https://www.hebcal.com/converter?cfg=json&gy={$year}&gm={$month}&gd={$day}&g2h=1";
    $converterData = @file_get_contents($converterUrl);
    $hebrewDate = $converterData ? json_decode($converterData, true) : null;

    // Get holidays/events for this date with candle lighting
    $holidaysUrl = "https://www.hebcal.com/hebcal?cfg=json&v=1&year={$year}&month={$month}&maj=on&min=on&mod=on&nx=on&ss=on&mf=on&c=on&geo=pos&latitude={$lat}&longitude={$lon}&tzid=" . urlencode($tz);
    $holidaysData = @file_get_contents($holidaysUrl);
    $holidaysJson = $holidaysData ? json_decode($holidaysData, true) : null;

    $holidays = [];
    $isShabbat = ($dayOfWeek == 6);
    $isFriday = ($dayOfWeek == 5);
    $isYomTov = false;
    $isErevYomTov = false;
    $isFastDay = false;
    $specialDay = null;
    $candleLighting = null;
    $havdalah = null;

    // Check tomorrow for Yom Tov (to detect Erev Yom Tov)
    $tomorrow = date('Y-m-d', strtotime($date . ' +1 day'));

    if ($holidaysJson && isset($holidaysJson['items'])) {
        foreach ($holidaysJson['items'] as $item) {
            $itemDate = $item['date'] ?? '';
            // Extract just the date part (first 10 chars) for comparison, since candles/havdalah have full datetime
            $itemDateOnly = substr($itemDate, 0, 10);
            $cat = $item['category'] ?? '';
            $title = $item['title'] ?? '';

            // Items for today
            if ($itemDateOnly === $date) {
                // Candle lighting time
                if ($cat === 'candles') {
                    $candleLighting = $formatTime($itemDate);
                    continue;
                }
                // Havdalah time (for when Shabbat/Yom Tov ends today)
                if ($cat === 'havdalah') {
                    $havdalah = $formatTime($itemDate);
                    continue;
                }

                $holidays[] = [
                    'title' => $title,
                    'category' => $cat,
                    'hebrew' => $item['hebrew'] ?? '',
                ];

                if (in_array($cat, ['holiday', 'yomtov'])) {
                    $isYomTov = true;
                }
                if ($cat === 'fast') {
                    $isFastDay = true;
                }
            }

            // Items for tomorrow
            if ($itemDateOnly === $tomorrow) {
                // Check if tomorrow is Yom Tov (making today Erev Yom Tov)
                if (in_array($cat, ['holiday', 'yomtov'])) {
                    $isErevYomTov = true;
                }
                // If today is Erev (has candle lighting) and tomorrow has havdalah, show it
                // This lets us display "Shabbat ends" on Friday
                if ($cat === 'havdalah' && $candleLighting !== null) {
                    $havdalah = $formatTime($itemDate);
                }
            }
        }
    }

    // If it's Friday and we have candle lighting but no havdalah yet, look for Saturday's havdalah
    if ($isFriday && $candleLighting !== null && $havdalah === null) {
        // Havdalah should have been picked up from tomorrow's items above
        // But if not, we can note that Shabbat ends the next day
    }

    // Determine special day type
    if ($isYomTov) {
        $specialDay = 'yomtov';
    } elseif ($isFastDay) {
        $specialDay = 'fast';
    } elseif ($isShabbat) {
        $specialDay = 'shabbat';
    }

    // Check for yartzheits on this Hebrew date
    $yartzheits = [];
    if ($hebrewDate && isset($hebrewDate['hm']) && isset($hebrewDate['hd'])) {
        $hebrewMonth = hebrew_month_to_number($hebrewDate['hm']);
        $hebrewDay = (int)$hebrewDate['hd'];
        $yartzheits = get_yartzheits_for_date($pdo, $hebrewMonth, $hebrewDay);
    }

    return [
        'hebrew_date' => $hebrewDate ? ($hebrewDate['hebrew'] ?? '') : '',
        'hebrew_day' => $hebrewDate['hd'] ?? null,
        'hebrew_month' => $hebrewDate['hm'] ?? null,
        'hebrew_month_num' => $hebrewDate ? hebrew_month_to_number($hebrewDate['hm'] ?? '') : null,
        'hebrew_year' => $hebrewDate['hy'] ?? null,
        'day_of_week' => $dayOfWeek,
        'holidays' => $holidays,
        'is_shabbat' => $isShabbat,
        'is_friday' => $isFriday,
        'is_yom_tov' => $isYomTov,
        'is_erev_yom_tov' => $isErevYomTov,
        'is_fast_day' => $isFastDay,
        'special_day' => $specialDay,
        'candle_lighting' => $candleLighting,
        'havdalah' => $havdalah,
        'yartzheits' => $yartzheits,
    ];
}

function hebrew_month_to_number(string $monthName): int {
    $months = [
        'Nisan' => 1, 'Iyyar' => 2, 'Sivan' => 3, 'Tamuz' => 4,
        'Av' => 5, 'Elul' => 6, 'Tishrei' => 7, 'Cheshvan' => 8,
        'Kislev' => 9, 'Tevet' => 10, 'Shvat' => 11, "Adar" => 12,
        'Adar I' => 12, 'Adar II' => 13,
    ];
    return $months[$monthName] ?? 0;
}

function number_to_hebrew_month(int $num): string {
    $months = [
        1 => 'Nisan', 2 => 'Iyyar', 3 => 'Sivan', 4 => 'Tamuz',
        5 => 'Av', 6 => 'Elul', 7 => 'Tishrei', 8 => 'Cheshvan',
        9 => 'Kislev', 10 => 'Tevet', 11 => 'Shvat', 12 => 'Adar',
        13 => 'Adar II',
    ];
    return $months[$num] ?? '';
}

function get_zmanim(string $date): array {
    global $latitude, $longitude, $timezone, $elevation, $locationName;

    // Try to get settings from database, fall back to config.php
    try {
        $pdo = get_pdo();
        $settings = get_all_settings($pdo);
        $lat = $settings['latitude'] ?? $latitude;
        $lon = $settings['longitude'] ?? $longitude;
        $tz = $settings['timezone'] ?? $timezone;
        $loc = $settings['location_name'] ?? $locationName;
    } catch (Exception $e) {
        $lat = $latitude;
        $lon = $longitude;
        $tz = $timezone;
        $loc = $locationName;
    }

    $url = "https://www.hebcal.com/zmanim?cfg=json&date={$date}&latitude={$lat}&longitude={$lon}&tzid=" . urlencode($tz) . "&sec=0";
    $data = @file_get_contents($url);
    $json = $data ? json_decode($data, true) : null;

    if (!$json || !isset($json['times'])) {
        return ['error' => 'Could not fetch zmanim', 'location' => $loc];
    }

    $times = $json['times'];

    // Create a formatter that uses the user's timezone
    $userTimezone = new DateTimeZone($tz);
    $formatTime = function($iso) use ($userTimezone) {
        if (!$iso) return null;
        try {
            $dt = new DateTime($iso);
            $dt->setTimezone($userTimezone);
            return $dt->format('g:i A');
        } catch (Exception $e) {
            return null;
        }
    };

    return [
        'location' => $loc,
        'date' => $date,
        'alotHaShachar' => $formatTime($times['alotHaShachar'] ?? null),
        'misheyakir' => $formatTime($times['misheyakir'] ?? null),
        'sunrise' => $formatTime($times['sunrise'] ?? null),
        'sofZmanShma' => $formatTime($times['sofZmanShma'] ?? null),
        'sofZmanTfilla' => $formatTime($times['sofZmanTfilla'] ?? null),
        'chatzot' => $formatTime($times['chatzot'] ?? null),
        'minchaGedola' => $formatTime($times['minchaGedola'] ?? null),
        'minchaKetana' => $formatTime($times['minchaKetana'] ?? null),
        'plagHaMincha' => $formatTime($times['plagHaMincha'] ?? null),
        'sunset' => $formatTime($times['sunset'] ?? null),
        'tzeit42min' => $formatTime($times['tzeit42min'] ?? null),
        'tzeit72min' => $formatTime($times['tzeit72min'] ?? null),
    ];
}

// Yartzheit functions
function get_all_yartzheits(PDO $pdo): array {
    $stmt = $pdo->query('SELECT id, name, hebrew_month, hebrew_day, relationship, notes, created_at FROM yartzheits ORDER BY hebrew_month, hebrew_day, name');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Add month name
    foreach ($rows as &$row) {
        $row['hebrew_month_name'] = number_to_hebrew_month((int)$row['hebrew_month']);
    }
    return $rows;
}

function get_yartzheits_for_date(PDO $pdo, int $hebrewMonth, int $hebrewDay): array {
    $stmt = $pdo->prepare('SELECT id, name, hebrew_month, hebrew_day, relationship, notes FROM yartzheits WHERE hebrew_month = :m AND hebrew_day = :d');
    $stmt->execute([':m' => $hebrewMonth, ':d' => $hebrewDay]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function add_yartzheit(PDO $pdo, string $name, int $hebrewMonth, int $hebrewDay, string $relationship, string $notes): void {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO yartzheits (name, hebrew_month, hebrew_day, relationship, notes, created_at) VALUES (:n, :m, :d, :r, :notes, :c)');
    $stmt->execute([':n' => $name, ':m' => $hebrewMonth, ':d' => $hebrewDay, ':r' => $relationship, ':notes' => $notes, ':c' => $now]);
}

function delete_yartzheit(PDO $pdo, int $id): void {
    if (!$id) return;
    $stmt = $pdo->prepare('DELETE FROM yartzheits WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

// Settings functions
function get_all_settings(PDO $pdo): array {
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function save_settings(PDO $pdo, array $settings): void {
    $allowedKeys = ['location_name', 'latitude', 'longitude', 'timezone', 'elevation'];
    $now = date('Y-m-d H:i:s');

    foreach ($settings as $key => $value) {
        if (!in_array($key, $allowedKeys, true)) continue;
        $stmt = $pdo->prepare('
            INSERT INTO settings (setting_key, setting_value, updated_at)
            VALUES (:k, :v, :u)
            ON DUPLICATE KEY UPDATE setting_value = :v2, updated_at = :u2
        ');
        $stmt->execute([
            ':k' => $key,
            ':v' => $value,
            ':u' => $now,
            ':v2' => $value,
            ':u2' => $now,
        ]);
    }
}
