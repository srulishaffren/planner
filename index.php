<?php
require_once __DIR__ . '/config.php';
session_start();

// Simple app login on top of HTTP auth
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    global $appUsername, $appPasswordHash;

    if ($user === $appUsername && password_verify($pass, $appPasswordHash)) {
        $_SESSION['planner_logged_in'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Invalid credentials';
    }
}

if (empty($_SESSION['planner_logged_in'])):
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Planner Login</title>
  <meta name="robots" content="noindex,nofollow">
  <style>
    body { font-family: sans-serif; background:#111; color:#eee; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
    .login-box { background:#222; padding:20px; border-radius:8px; width:300px; box-shadow:0 0 12px rgba(0,0,0,0.6); }
    h2 { margin-top:0; }
    label { display:block; margin-top:10px; }
    input[type="text"], input[type="password"] { width:100%; padding:6px; margin-top:4px; background:#333; border:1px solid #555; color:#eee; border-radius:4px; }
    button { margin-top:15px; padding:8px 12px; background:#4a90e2; border:none; color:#fff; border-radius:4px; cursor:pointer; }
    .error { color:#f66; margin-top:10px; }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Planner Login</h2>
    <?php if (!empty($loginError)): ?>
      <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
    <?php endif; ?>
    <form method="post">
      <label>Username
        <input type="text" name="username" autocomplete="username">
      </label>
      <label>Password
        <input type="password" name="password" autocomplete="current-password">
      </label>
      <button type="submit" name="login" value="1">Login</button>
    </form>
  </div>
</body>
</html>
<?php
exit;
endif;

// Logged in, render app shell
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Gapaika Planner</title>
  <meta name="robots" content="noindex,nofollow">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
  <style>
    :root {
      /* Base theme variables */
      --font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
      --font-size-base: 14px;
      --font-size-small: 0.85rem;
      --font-size-xs: 0.75rem;
      --font-size-large: 1.1rem;
      --font-size-xl: 1.3rem;

      /* Colors */
      --bg-primary: #111;
      --bg-secondary: #181818;
      --bg-tertiary: #222;
      --bg-input: #222;
      --bg-hover: #333;
      --bg-active: #444;

      --text-primary: #eee;
      --text-secondary: #aaa;
      --text-muted: #888;
      --text-faint: #666;

      --border-primary: #333;
      --border-secondary: #444;
      --border-input: #555;

      --accent-primary: #4a90e2;
      --accent-hover: #5a9df2;
      --accent-success: #5cb85c;
      --accent-warning: #f0ad4e;
      --accent-danger: #d9534f;
      --accent-info: #5bc0de;

      /* Shadows */
      --shadow-modal: 0 4px 12px rgba(0,0,0,0.5);
      --shadow-dropdown: 0 0 12px rgba(0,0,0,0.6);
    }

    body { font-family: var(--font-family); font-size: var(--font-size-base); margin:0; background: var(--bg-primary); color: var(--text-primary); }
    header { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; padding:10px 16px; background:var(--bg-secondary); border-bottom:1px solid var(--border-primary); gap:16px; }
    .header-left { font-weight:bold; white-space:nowrap; }
    .header-right { display:flex; align-items:center; gap:8px; justify-content:flex-end; }
    .header-right form { margin:0; }

    /* User menu */
    .user-menu { position:relative; }
    .user-menu-btn { width:40px; height:40px; border-radius:50%; background:var(--bg-hover); border:2px solid var(--border-input); cursor:pointer; padding:0; display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .user-menu-btn:hover { border-color:var(--accent-primary); }
    .user-icon { width:24px; height:24px; color:var(--text-secondary); }
    .user-avatar { width:100%; height:100%; object-fit:cover; }
    .user-dropdown { position:absolute; top:100%; right:0; margin-top:8px; background:var(--bg-tertiary); border:1px solid var(--border-input); border-radius:8px; min-width:200px; box-shadow:var(--shadow-dropdown); display:none; z-index:1001; overflow:hidden; }
    .user-dropdown.open { display:block; }
    .user-dropdown-header { padding:12px 16px; border-bottom:1px solid var(--border-secondary); }
    .user-dropdown-name { font-weight:600; color:var(--text-primary); }
    .user-dropdown-item { display:flex; align-items:center; gap:10px; padding:10px 16px; color:var(--text-primary); cursor:pointer; background:none; border:none; width:100%; text-align:left; font-size:var(--font-size-base); }
    .user-dropdown-item:hover { background:var(--bg-hover); }
    .user-dropdown-item svg { width:18px; height:18px; color:var(--text-secondary); flex-shrink:0; }
    .user-dropdown-divider { height:1px; background:var(--border-secondary); margin:4px 0; }
    .user-dropdown-logout { color:var(--accent-danger); }
    .user-dropdown-logout svg { color:var(--accent-danger); }

    .date-controls-wrapper { display:flex; justify-content:center; }
    .date-controls { display:flex; align-items:center; gap:4px; }
    .date-controls button { padding:4px 8px; background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; }
    .date-controls button:hover { background:var(--bg-active); }
    .date-nav-btn { font-size:var(--font-size-large); padding:4px 10px; }
    #pretty-date { font-size:var(--font-size-xl); font-weight:600; color:var(--text-primary); cursor:pointer; padding:4px 12px; border-radius:4px; }
    #pretty-date:hover { background:var(--bg-hover); }
    #today-btn { margin-left:8px; }

    .calendar-dropdown { position:absolute; top:100%; left:50%; transform:translateX(-50%); background:var(--bg-tertiary); border:1px solid var(--border-input); border-radius:8px; padding:12px; z-index:1000; display:none; box-shadow:var(--shadow-modal); min-width:280px; }
    .calendar-dropdown.open { display:block; }
    .calendar-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .calendar-header button { padding:2px 8px; background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; font-size:var(--font-size-small); }
    .calendar-header button:hover { background:var(--bg-active); }
    .calendar-nav { display:flex; gap:4px; }
    .calendar-month-year { font-weight:600; font-size:1rem; }
    .calendar-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:2px; margin-bottom:8px; }
    .calendar-grid .day-header { text-align:center; font-size:var(--font-size-xs); color:var(--text-muted); padding:4px; }
    .calendar-grid .day-cell { text-align:center; padding:6px 4px; border-radius:4px; cursor:pointer; font-size:var(--font-size-small); }
    .calendar-grid .day-cell:hover { background:var(--bg-active); }
    .calendar-grid .day-cell.other-month { color:var(--text-faint); }
    .calendar-grid .day-cell.today { border:1px solid var(--accent-primary); }
    .calendar-grid .day-cell.selected { background:var(--accent-primary); color:#fff; }
    .calendar-input-row { display:flex; gap:8px; margin-top:8px; padding-top:8px; border-top:1px solid var(--border-secondary); }
    .calendar-input-row input[type="text"] { flex:1; background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 8px; border-radius:4px; font-size:0.9rem; }
    .calendar-input-row button { padding:6px 12px; background:var(--accent-primary); border:none; color:#fff; border-radius:4px; cursor:pointer; }

    main { display:flex; flex-direction:row; padding:16px; gap:16px; }
    .column { flex:1; background:var(--bg-secondary); border-radius:8px; padding:12px; border:1px solid var(--border-primary); min-height:400px; }
    h2 { margin-top:0; font-size:var(--font-size-large); border-bottom:1px solid var(--border-primary); padding-bottom:4px; }

    .priority-section { margin-bottom:12px; }
    .priority-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; }
    .priority-title { font-weight:bold; }
    .task-list { list-style:none; padding:4px; margin:0; min-height:30px; border:1px dashed var(--border-primary); border-radius:4px; }
    .task-item { background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:4px; padding:6px; margin-bottom:4px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .drag-handle { cursor:grab; color:var(--text-muted); padding:4px 2px; font-size:1.1em; user-select:none; }
    .drag-handle:hover { color:var(--text-secondary); }
    .drag-handle:active { cursor:grabbing; }
    .task-left { display:flex; flex-direction:column; }
    .task-meta { font-size:var(--font-size-xs); color:var(--text-secondary); }
    .task-controls { display:flex; gap:4px; align-items:center; }
    .task-controls button { padding:2px 6px; font-size:var(--font-size-xs); background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; }
    .task-controls button:hover { background:var(--bg-active); }
    .task-controls select { background:var(--bg-tertiary); color:var(--text-primary); border:1px solid var(--border-input); font-size:var(--font-size-xs); }
    .task-controls .pomodoro-btn { font-size:1rem; padding:2px 4px; }
    .task-controls .pomodoro-btn.active { background:var(--accent-danger); color:#fff; border-color:var(--accent-danger); }
    .task-item.pomodoro-active { border-color:var(--accent-danger); box-shadow:0 0 8px rgba(220,53,69,0.3); }

    /* Done section styles */
    .done-section { margin-top:16px; border-top:2px solid var(--border-primary); padding-top:12px; }
    .done-section .priority-title { color:var(--text-secondary); }
    .done-list .task-item { opacity:0.7; }
    .done-list .task-item:hover { opacity:1; }
    .done-task-item .task-text { text-decoration:line-through; color:var(--text-secondary); }
    .done-priority-badge { display:inline-block; background:var(--bg-hover); color:var(--text-secondary); padding:1px 6px; border-radius:3px; font-size:var(--font-size-xs); font-weight:bold; margin-right:8px; }

    /* Pomodoro timer display */
    .pomodoro-timer { display:none; align-items:center; gap:8px; padding:4px 12px; background:var(--accent-danger); color:#fff; border-radius:4px; font-weight:600; }
    .pomodoro-timer.active { display:flex; }
    .pomodoro-timer-time { font-family:monospace; font-size:1.1rem; }
    .pomodoro-timer-task { font-size:var(--font-size-small); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pomodoro-timer-controls { display:flex; gap:4px; }
    .pomodoro-timer-controls button { background:rgba(255,255,255,0.2); border:none; color:#fff; padding:2px 8px; border-radius:3px; cursor:pointer; font-size:var(--font-size-xs); }
    .pomodoro-timer-controls button:hover { background:rgba(255,255,255,0.3); }

    .add-task-form { display:flex; gap:4px; margin-bottom:8px; }
    .add-task-form input[type="text"] { flex:1; background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:4px; border-radius:4px; }
    .add-task-form select { background:var(--bg-tertiary); color:var(--text-primary); border:1px solid var(--border-input); padding:4px; border-radius:4px; }
    .add-task-form button { padding:4px 8px; background:var(--accent-primary); border:none; color:#fff; border-radius:4px; cursor:pointer; }

    textarea { width:100%; min-height:200px; background:var(--bg-tertiary); color:var(--text-primary); border:1px solid var(--border-input); border-radius:4px; padding:6px; resize:vertical; font-family:var(--font-family); font-size:var(--font-size-base); }
    .journal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .journal-header h2 { margin:0; }
    .journal-search { display:flex; gap:4px; }
    .journal-search input { padding:4px 8px; background:var(--bg-tertiary); border:1px solid var(--border-input); border-radius:4px; color:var(--text-primary); font-size:var(--font-size-small); width:150px; }
    .journal-search input:focus { border-color:var(--accent-primary); outline:none; }
    .journal-search button { padding:4px 8px; background:var(--bg-tertiary); border:1px solid var(--border-input); border-radius:4px; cursor:pointer; }
    .journal-search button:hover { background:var(--bg-hover); }
    .journal-controls { margin-top:8px; display:flex; justify-content:flex-end; }
    .journal-controls button { padding:4px 8px; background:var(--accent-primary); border:none; color:#fff; border-radius:4px; cursor:pointer; }

    .search-result { padding:12px; border-bottom:1px solid var(--border-secondary); cursor:pointer; }
    .search-result:hover { background:var(--bg-hover); }
    .search-result:last-child { border-bottom:none; }
    .search-result-date { font-weight:600; color:var(--accent-primary); margin-bottom:4px; }
    .search-result-snippet { font-size:var(--font-size-small); color:var(--text-secondary); }
    .search-result-snippet mark { background:var(--accent-warning); color:var(--bg-primary); padding:0 2px; border-radius:2px; }
    .search-no-results { text-align:center; color:var(--text-muted); padding:20px; }

    .dropzone { border:2px dashed var(--border-input); border-radius:8px; padding:16px; text-align:center; color:var(--text-muted); margin-top:12px; transition:all 0.2s; cursor:pointer; }
    .dropzone:hover { border-color:var(--accent-primary); color:var(--text-secondary); }
    .dropzone.dragover { border-color:var(--accent-primary); background:rgba(74,144,226,0.1); color:var(--text-primary); }
    .dropzone-text { pointer-events:none; }
    .dropzone input[type="file"] { display:none; }

    .attachments-list { margin-top:12px; }
    .attachments-header { font-size:0.9rem; color:var(--text-secondary); margin-bottom:8px; }
    .attachment-item { display:flex; align-items:center; gap:8px; background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:4px; padding:8px; margin-bottom:6px; }
    .attachment-preview { width:48px; height:48px; border-radius:4px; object-fit:cover; background:var(--bg-hover); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .attachment-preview img { max-width:100%; max-height:100%; border-radius:4px; }
    .attachment-preview .file-icon { font-size:1.5rem; color:var(--text-muted); }
    .attachment-info { flex:1; min-width:0; }
    .attachment-name { font-size:var(--font-size-small); color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .attachment-meta { font-size:var(--font-size-xs); color:var(--text-muted); }
    .attachment-actions { display:flex; gap:4px; }
    .attachment-actions button { padding:4px 8px; font-size:var(--font-size-xs); background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; }
    .attachment-actions button:hover { background:var(--bg-active); }
    .attachment-actions button.delete-btn:hover { background:var(--accent-danger); }

    /* Index entries */
    .index-section { margin-top:16px; padding-top:12px; border-top:1px solid var(--border-secondary); }
    .index-section h3 { font-size:0.95rem; margin:0 0 8px 0; color:var(--text-secondary); }
    .add-index-form { display:flex; gap:6px; margin-bottom:8px; }
    .add-index-form input[type="text"] { flex:1; background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 8px; border-radius:4px; font-size:0.9rem; }
    .add-index-form button { padding:6px 12px; background:var(--accent-primary); border:none; color:#fff; border-radius:4px; cursor:pointer; white-space:nowrap; }
    .index-entries-list { }
    .index-entry-item { display:flex; align-items:center; justify-content:space-between; background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:4px; padding:6px 8px; margin-bottom:4px; }
    .index-entry-summary { font-size:var(--font-size-small); color:var(--text-primary); }
    .index-entry-item button { padding:2px 6px; font-size:0.7rem; background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; }
    .index-entry-item button:hover { background:var(--accent-danger); }

    /* Modal */
    .modal-overlay { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); display:none; align-items:center; justify-content:center; z-index:2000; }
    .modal-overlay.open { display:flex; }
    .modal { background:var(--bg-secondary); border:1px solid var(--border-secondary); border-radius:8px; max-width:600px; width:90%; max-height:80vh; display:flex; flex-direction:column; }
    .confirm-modal { max-width:400px; }
    .confirm-modal .modal-body { text-align:center; padding:24px; }
    .confirm-modal .confirm-message { margin-bottom:20px; font-size:1.1rem; }
    .confirm-modal .confirm-buttons { display:flex; gap:12px; justify-content:center; }
    .confirm-modal .confirm-buttons button { padding:10px 24px; border-radius:6px; border:none; cursor:pointer; font-size:1rem; }
    .confirm-modal .btn-cancel { background:var(--bg-hover); color:var(--text-primary); border:1px solid var(--border-input); }
    .confirm-modal .btn-cancel:hover { background:var(--bg-active); }
    .confirm-modal .btn-danger { background:var(--accent-danger); color:#fff; }
    .confirm-modal .btn-danger:hover { opacity:0.9; }

    /* Toast notifications */
    .toast { position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:var(--bg-secondary); color:var(--text-primary); padding:12px 24px; border-radius:8px; border:1px solid var(--border-secondary); box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:3000; opacity:0; transition:opacity 0.3s; }
    .toast.show { opacity:1; }
    .toast.success { border-color:var(--accent-success); }
    .toast.error { border-color:var(--accent-danger); }
    .toast.info { border-color:var(--accent-primary); }

    .modal-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid var(--border-secondary); }
    .modal-header h2 { margin:0; font-size:var(--font-size-large); }
    .modal-header button { background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer; line-height:1; }
    .modal-header button:hover { color:var(--text-primary); }
    .modal-body { padding:16px; overflow-y:auto; flex:1; }
    .modal-nav { display:flex; align-items:center; justify-content:center; gap:12px; margin-bottom:16px; }
    .modal-nav button { padding:4px 10px; background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; }
    .modal-nav button:hover { background:var(--bg-active); }
    .modal-nav .month-year { font-weight:600; min-width:140px; text-align:center; }

    /* Monthly index list */
    .month-index-list { }
    .month-index-day { margin-bottom:12px; }
    .month-index-date { font-size:var(--font-size-small); color:var(--accent-primary); margin-bottom:4px; cursor:pointer; }
    .month-index-date:hover { text-decoration:underline; }
    .month-index-entries { padding-left:12px; }
    .month-index-entry { font-size:0.9rem; color:var(--text-primary); padding:2px 0; }
    .month-index-empty { color:var(--text-faint); font-style:italic; }

    /* Search */
    .search-input-row { display:flex; gap:8px; margin-bottom:16px; }
    .search-input-row input[type="text"] { flex:1; background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:8px 12px; border-radius:4px; font-size:1rem; }
    .search-input-row button { padding:8px 16px; background:var(--accent-primary); border:none; color:#fff; border-radius:4px; cursor:pointer; }
    .search-results { }
    .search-result-item { background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:4px; padding:10px; margin-bottom:8px; cursor:pointer; }
    .search-result-item:hover { border-color:var(--accent-primary); }
    .search-result-date { font-size:var(--font-size-small); color:var(--accent-primary); margin-bottom:4px; }
    .search-result-snippet { font-size:0.9rem; color:var(--text-secondary); }
    .search-no-results { color:var(--text-faint); font-style:italic; }

    /* Header buttons */
    .header-actions { display:flex; gap:8px; align-items:center; }
    .header-actions button { padding:6px 12px; background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; font-size:var(--font-size-small); white-space:nowrap; }
    .header-actions button:hover { background:var(--bg-active); }

    /* Torah dropdown */
    .torah-dropdown { position:relative; }
    .torah-dropdown-menu { display:none; position:absolute; top:100%; left:0; min-width:220px; background:var(--bg-secondary); border:1px solid var(--border-secondary); border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:100; margin-top:4px; max-height:400px; overflow-y:auto; }
    .torah-dropdown-menu.open { display:block; }
    .torah-dropdown-item { padding:10px 14px; cursor:pointer; border-bottom:1px solid var(--border-secondary); }
    .torah-dropdown-item:last-child { border-bottom:none; }
    .torah-dropdown-item:hover { background:var(--bg-hover); }
    .torah-dropdown-item-title { font-weight:600; color:var(--text-primary); font-size:var(--font-size-small); }
    .torah-dropdown-item-ref { font-size:var(--font-size-xs); color:var(--text-secondary); margin-top:2px; }

    /* Hebrew date and special days */
    .date-info-row { display:flex; flex-direction:column; align-items:center; gap:2px; }
    .day-of-week { font-size:var(--font-size-small); color:var(--text-muted); }
    .hebrew-date { font-size:0.9rem; color:var(--text-secondary); }
    .special-day-badges { display:flex; gap:6px; margin-top:4px; flex-wrap:wrap; justify-content:center; }
    .special-badge { padding:2px 8px; border-radius:12px; font-size:var(--font-size-xs); font-weight:600; }
    .badge-shabbat { background:#5c4d9a; color:#fff; }
    .badge-yomtov { background:#c9a227; color:#000; }
    .badge-fast { background:#8b4513; color:#fff; }
    .badge-yartzheit { background:var(--bg-active); color:#fff; border:1px solid var(--text-muted); }
    .badge-holiday { background:#2d5a27; color:#fff; }
    .badge-candles { background:#f4a460; color:#000; }
    .badge-havdalah { background:#4169e1; color:#fff; }

    /* Zmanim modal */
    .zmanim-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .zmanim-item { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border-primary); }
    .zmanim-label { color:var(--text-secondary); }
    .zmanim-time { color:var(--text-primary); font-weight:500; }
    .zmanim-location { text-align:center; color:var(--text-muted); font-size:var(--font-size-small); margin-bottom:12px; }

    /* Yartzheit modal */
    .yartzheit-form { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; padding-bottom:16px; border-bottom:1px solid var(--border-secondary); }
    .yartzheit-form-row { display:flex; gap:8px; }
    .yartzheit-form input, .yartzheit-form select { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 8px; border-radius:4px; }
    .yartzheit-form input[type="text"] { flex:1; }
    .yartzheit-form select { min-width:100px; }
    .yartzheit-form button { padding:8px 16px; background:var(--accent-primary); border:none; color:#fff; border-radius:4px; cursor:pointer; }
    .yartzheit-list { }
    .yartzheit-item { display:flex; justify-content:space-between; align-items:center; background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:4px; padding:8px 10px; margin-bottom:6px; }
    .yartzheit-info { }
    .yartzheit-name { font-weight:600; color:var(--text-primary); }
    .yartzheit-date { font-size:var(--font-size-small); color:var(--text-secondary); }
    .yartzheit-rel { font-size:0.8rem; color:var(--text-muted); font-style:italic; }
    .yartzheit-item button { padding:4px 8px; font-size:var(--font-size-xs); background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; }
    .yartzheit-item button:hover { background:var(--accent-danger); }
    .yartzheit-empty { color:var(--text-faint); font-style:italic; }

    /* Recurring tasks modal */
    .recurring-form { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; padding-bottom:16px; border-bottom:1px solid var(--border-secondary); }
    .recurring-form-row { display:flex; gap:8px; align-items:center; }
    .recurring-form input, .recurring-form select { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 8px; border-radius:4px; }
    .recurring-form button { padding:8px 16px; background:var(--accent-primary); border:none; color:#fff; border-radius:4px; cursor:pointer; }
    .recurring-list { }
    .recurring-item { display:flex; justify-content:space-between; align-items:center; background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:4px; padding:8px 10px; margin-bottom:6px; }
    .recurring-item.inactive { opacity:0.5; }
    .recurring-info { flex:1; }
    .recurring-text { font-weight:600; color:var(--text-primary); }
    .recurring-pattern { font-size:var(--font-size-small); color:var(--text-secondary); }
    .recurring-actions { display:flex; gap:4px; }
    .recurring-item button { padding:4px 8px; font-size:var(--font-size-xs); background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); border-radius:4px; cursor:pointer; }
    .recurring-item button:hover { background:var(--accent-danger); }
    .recurring-empty { color:var(--text-faint); font-style:italic; }

    /* Parsha modal */
    .parsha-loading { color:var(--text-muted); font-style:italic; text-align:center; padding:20px; }
    .parsha-header { text-align:center; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border-secondary); }
    .parsha-name { font-size:1.4rem; font-weight:bold; color:var(--text-primary); }
    .parsha-name-hebrew { font-size:1.2rem; color:var(--text-secondary); margin-top:4px; }
    .parsha-ref { font-size:var(--font-size-small); color:var(--text-muted); margin-top:4px; }
    .parsha-section { margin-bottom:16px; }
    .parsha-section-title { font-weight:600; color:var(--text-primary); margin-bottom:8px; display:flex; align-items:center; gap:8px; }
    .parsha-aliyah { background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:6px; padding:12px; }
    .parsha-aliyah-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .parsha-aliyah-num { font-weight:600; color:var(--accent-primary); }
    .parsha-aliyah-ref { font-size:var(--font-size-small); color:var(--text-secondary); }
    .parsha-aliyah-link { display:inline-block; margin-top:8px; padding:6px 12px; background:var(--accent-primary); color:#fff; border-radius:4px; text-decoration:none; font-size:var(--font-size-small); }
    .parsha-aliyah-link:hover { opacity:0.9; }
    .parsha-links { display:flex; flex-direction:column; gap:8px; }
    .parsha-link { display:flex; align-items:center; gap:8px; padding:10px 12px; background:var(--bg-tertiary); border:1px solid var(--border-secondary); border-radius:4px; color:var(--text-primary); text-decoration:none; }
    .parsha-link:hover { background:var(--bg-hover); border-color:var(--accent-primary); }
    .parsha-link-icon { font-size:1.2rem; }
    .parsha-link-text { flex:1; }
    .parsha-link-arrow { color:var(--text-muted); }
    .parsha-day-indicator { font-size:var(--font-size-xs); color:var(--accent-success); background:rgba(90,170,85,0.15); padding:2px 6px; border-radius:3px; }

    /* Settings modal */
    .settings-form { display:flex; flex-direction:column; gap:12px; }
    .settings-row { display:flex; flex-direction:column; gap:4px; }
    .settings-row label { font-size:var(--font-size-small); color:var(--text-secondary); }
    .settings-row input, .settings-row select { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:8px 10px; border-radius:4px; font-size:0.95rem; }
    .settings-row input:focus, .settings-row select:focus { border-color:var(--accent-primary); outline:none; }
    .settings-row-inline { display:flex; gap:12px; }
    .settings-row-inline .settings-row { flex:1; }
    .settings-help { font-size:0.8rem; color:var(--text-faint); margin-top:2px; }
    .calendar-entry { display:flex; gap:8px; margin-bottom:8px; align-items:center; }
    .calendar-entry input { flex:1; }
    .calendar-entry .calendar-name { width:120px; flex:none; }
    .calendar-entry .calendar-url { flex:1; }
    .btn-remove-calendar { background:var(--accent-danger); border:none; color:#fff; padding:6px 10px; border-radius:4px; cursor:pointer; font-size:0.85rem; }
    .btn-remove-calendar:hover { opacity:0.9; }
    .btn-add-calendar { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 12px; border-radius:4px; cursor:pointer; font-size:0.85rem; margin-bottom:8px; }
    .btn-add-calendar:hover { background:var(--bg-hover); }
    .shortcuts-list { display:flex; flex-direction:column; gap:8px; margin:8px 0; }
    .shortcut-row { display:flex; align-items:center; gap:8px; }
    .shortcut-row label { flex:0 0 120px; font-size:var(--font-size-small); color:var(--text-secondary); }
    .shortcut-input { flex:1; background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 10px; border-radius:4px; font-family:monospace; font-size:0.9rem; }
    .shortcut-input.recording { border-color:var(--accent-primary); background:var(--bg-hover); }
    .shortcut-record-btn { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-secondary); padding:4px 10px; border-radius:4px; cursor:pointer; font-size:0.8rem; }
    .shortcut-record-btn:hover { background:var(--bg-hover); }
    .shortcut-record-btn.recording { background:var(--accent-primary); color:#fff; border-color:var(--accent-primary); }
    .btn-reset-shortcuts { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-secondary); padding:6px 12px; border-radius:4px; cursor:pointer; font-size:0.85rem; margin-top:8px; }
    .btn-reset-shortcuts:hover { background:var(--bg-hover); }
    .settings-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:8px; padding-top:12px; border-top:1px solid var(--border-secondary); }
    .settings-actions button { padding:8px 16px; border-radius:4px; cursor:pointer; font-size:0.9rem; }
    .settings-actions .btn-save { background:var(--accent-primary); border:none; color:#fff; }
    .settings-actions .btn-cancel { background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); }
    .checkbox-label { display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--text-primary); font-size:0.95rem; }
    .checkbox-label input[type="checkbox"] { width:16px; height:16px; accent-color:var(--accent-primary); cursor:pointer; }

    .status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:4px; }
    .status-todo { background:var(--text-muted); }
    .status-planning { background:var(--accent-warning); }
    .status-in_progress { background:var(--accent-info); }
    .status-waiting { background:var(--accent-danger); }
    .status-done { background:var(--accent-success); }

    /* Multi-line task text */
    .task-text { white-space:pre-wrap; word-break:break-word; flex:1; }
    .task-left { flex:1; min-width:0; }

    /* Task action buttons */
    .task-actions { display:flex; gap:4px; margin-top:4px; }
    .task-actions button { padding:2px 6px; font-size:0.7rem; background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-secondary); border-radius:4px; cursor:pointer; }
    .task-actions button:hover { background:var(--bg-active); color:var(--text-primary); }

    /* Task details modal */
    .task-details-modal .modal { max-width:500px; }
    .task-details-form { display:flex; flex-direction:column; gap:12px; }
    .task-details-row { display:flex; flex-direction:column; gap:4px; }
    .task-details-row label { font-size:var(--font-size-small); color:var(--text-secondary); }
    .task-details-row textarea, .task-details-row input { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:8px; border-radius:4px; font-size:0.95rem; font-family:inherit; }
    .task-details-row textarea { min-height:80px; resize:vertical; }
    .task-details-row textarea.task-notes { min-height:120px; }
    .task-details-row select { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:8px; border-radius:4px; font-size:0.95rem; }
    .task-details-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:8px; padding-top:12px; border-top:1px solid var(--border-secondary); }
    .task-details-actions button { padding:8px 16px; border-radius:4px; cursor:pointer; font-size:0.9rem; }
    .task-details-actions .btn-save { background:var(--accent-primary); border:none; color:#fff; }
    .task-details-actions .btn-cancel { background:var(--bg-hover); border:1px solid var(--border-input); color:var(--text-primary); }

    /* Copy to date picker */
    .copy-date-row { display:flex; gap:8px; align-items:center; margin-top:8px; padding-top:8px; border-top:1px solid var(--border-secondary); }
    .copy-date-row label { font-size:var(--font-size-small); color:var(--text-secondary); white-space:nowrap; }
    .copy-date-row input[type="date"] { background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 8px; border-radius:4px; flex:1; }
    .copy-date-row button { padding:6px 12px; background:var(--accent-success); border:none; color:#fff; border-radius:4px; cursor:pointer; white-space:nowrap; }
    .copy-date-row button:hover { opacity:0.9; }

    /* Carry forward button */
    .carry-forward-section { display:flex; gap:8px; align-items:center; margin-bottom:8px; padding:8px; background:rgba(90,170,85,0.1); border:1px solid var(--accent-success); border-radius:4px; }
    .carry-forward-section span { font-size:var(--font-size-small); color:var(--accent-success); flex:1; }
    .carry-forward-section button { padding:4px 10px; background:var(--accent-success); border:none; color:#fff; border-radius:4px; cursor:pointer; font-size:var(--font-size-small); }
    .carry-forward-section button:hover { opacity:0.9; }

    /* Theme controls */
    .settings-section { margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--border-secondary); }
    .settings-section:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
    .settings-section-title { font-size:1rem; font-weight:600; margin-bottom:12px; color:var(--text-primary); }
    .theme-presets { display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:8px; margin-bottom:12px; }
    .theme-preset-btn { padding:10px 12px; background:var(--bg-tertiary); border:2px solid var(--border-secondary); color:var(--text-primary); border-radius:6px; cursor:pointer; font-size:var(--font-size-small); text-align:center; transition:all 0.2s; }
    .theme-preset-btn:hover { border-color:var(--accent-primary); }
    .theme-preset-btn.active { border-color:var(--accent-primary); background:rgba(74,144,226,0.1); }
    .theme-custom-row { display:flex; gap:12px; align-items:center; margin-bottom:8px; }
    .theme-custom-row label { flex:1; font-size:var(--font-size-small); color:var(--text-secondary); }
    .theme-custom-row input[type="color"] { width:50px; height:32px; padding:0; border:1px solid var(--border-input); border-radius:4px; cursor:pointer; background:var(--bg-tertiary); }
    .theme-custom-row input[type="range"] { flex:1; }
    .theme-custom-row select { flex:1; background:var(--bg-tertiary); border:1px solid var(--border-input); color:var(--text-primary); padding:6px 8px; border-radius:4px; }
    .font-size-display { min-width:40px; text-align:right; font-size:var(--font-size-small); color:var(--text-secondary); }

    /* Responsive styles */
    @media (max-width: 1024px) {
      main { flex-direction:column; }
      .column { min-height:auto; }
    }

    @media (max-width: 768px) {
      header { grid-template-columns:1fr auto; grid-template-rows:auto auto; padding:8px 12px; gap:8px; }
      .header-left { font-size:var(--font-size-small); }
      .date-controls-wrapper { grid-column:1 / -1; justify-content:center; }
      .header-right { justify-self:end; }
      .header-actions { display:none; }
      .date-controls { flex-wrap:wrap; justify-content:center; gap:4px; }
      .date-info-row { flex-direction:column; align-items:center; gap:2px; }
      #pretty-date { font-size:1.1rem; }
      .hebrew-date { font-size:var(--font-size-small); }
      main { padding:8px; gap:8px; }
      .column { padding:10px; }
      h2 { font-size:1rem; }
      .add-task-form { flex-wrap:wrap; }
      .add-task-form input[type="text"] { flex:1; min-width:150px; }
      #journal-content { min-height:150px; }
      .journal-search { flex-wrap:wrap; }
      .modal { margin:10px; max-height:calc(100vh - 20px); }
      .modal-body { padding:12px; }
    }

    @media (max-width: 480px) {
      .date-controls button { padding:4px 6px; font-size:var(--font-size-small); }
      .task-item { flex-direction:column; align-items:flex-start; gap:6px; }
      .task-actions { width:100%; justify-content:flex-end; }
      .user-dropdown { right:-10px; min-width:180px; }
    }

  </style>
</head>
<body>
<header>
  <div class="header-left">Gapaika Planner</div>

  <div class="date-controls-wrapper">
    <div class="date-controls" style="position:relative;">
      <button id="prev-day" class="date-nav-btn" title="Previous day">&larr;</button>
      <div class="date-info-row">
        <span id="day-of-week" class="day-of-week"></span>
        <span id="pretty-date" title="Click to open calendar"></span>
        <span id="hebrew-date" class="hebrew-date"></span>
        <div id="special-day-badges" class="special-day-badges"></div>
      </div>
      <button id="next-day" class="date-nav-btn" title="Next day">&rarr;</button>

      <div class="calendar-dropdown" id="calendar-dropdown">
        <div class="calendar-header">
          <div class="calendar-nav">
            <button id="cal-prev-year" title="Previous year">&laquo;</button>
            <button id="cal-prev-month" title="Previous month">&lsaquo;</button>
          </div>
          <span class="calendar-month-year" id="cal-month-year"></span>
          <div class="calendar-nav">
            <button id="cal-next-month" title="Next month">&rsaquo;</button>
            <button id="cal-next-year" title="Next year">&raquo;</button>
          </div>
        </div>
        <div class="calendar-grid" id="calendar-grid"></div>
        <div class="calendar-input-row">
          <input type="text" id="date-input" placeholder="YYYY-MM-DD">
          <button id="date-input-go">Go</button>
        </div>
      </div>
    </div>
  </div>

  <div class="header-right">
    <div class="pomodoro-timer" id="pomodoro-timer">
      <span>🍅</span>
      <span class="pomodoro-timer-time" id="pomodoro-time">25:00</span>
      <span class="pomodoro-timer-task" id="pomodoro-task-name"></span>
      <div class="pomodoro-timer-controls">
        <button id="pomodoro-pause">Pause</button>
        <button id="pomodoro-stop">Stop</button>
      </div>
    </div>
    <div class="header-actions">
      <button id="today-btn">Today</button>
      <button id="zmanim-btn" title="Zmanim">Zmanim</button>
      <div class="torah-dropdown" id="torah-dropdown">
        <button id="torah-btn" title="Daily Torah Learning">Torah ▾</button>
        <div class="torah-dropdown-menu" id="torah-dropdown-menu"></div>
      </div>
      <button id="search-btn" title="Search journals">Search</button>
      <button id="month-index-btn" title="Monthly index">Index</button>
      <button id="yartzheits-btn" title="Manage Yartzheits">Yartzheits</button>
      <button id="recurring-btn" title="Recurring Tasks">Recurring</button>
    </div>
    <div class="user-menu" id="user-menu">
      <button class="user-menu-btn" id="user-menu-btn" title="User menu">
        <?php
        $profilePath = __DIR__ . '/uploads/profile/avatar.jpg';
        if (file_exists($profilePath)):
        ?>
          <img src="uploads/profile/avatar.jpg?t=<?php echo filemtime($profilePath); ?>" alt="Profile" class="user-avatar">
        <?php else: ?>
          <svg class="user-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
        <?php endif; ?>
      </button>
      <div class="user-dropdown" id="user-dropdown">
        <div class="user-dropdown-header">
          <span class="user-dropdown-name"><?php echo htmlspecialchars($appUsername); ?></span>
        </div>
        <div class="user-dropdown-item" id="profile-photo-btn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
          Change Photo
        </div>
        <div class="user-dropdown-item" id="settings-btn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
          Settings
        </div>
        <div class="user-dropdown-item" id="change-password-btn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
          Change Password
        </div>
        <div class="user-dropdown-item" id="export-btn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
          Export Data
        </div>
        <div class="user-dropdown-divider"></div>
        <form method="post" action="logout.php" style="margin:0;">
          <button type="submit" class="user-dropdown-item user-dropdown-logout">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            Logout
          </button>
        </form>
      </div>
    </div>
  </div>
</header>

<input type="file" id="profile-photo-input" accept="image/*" style="display:none;">

<main>
  <div class="column" id="tasks-column">
    <h2>Tasks</h2>
    <form class="add-task-form" id="add-task-form">
      <input type="text" id="new-task-text" placeholder="New task...">
      <select id="new-task-priority">
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C" selected>C</option>
        <option value="D">D</option>
      </select>
      <button type="submit">Add</button>
    </form>

    <div id="priority-sections">
      <!-- sections A/B/C/D will be injected here -->
    </div>
  </div>

  <div class="column">
    <div class="journal-header">
      <h2>Journal</h2>
      <div class="journal-search">
        <input type="text" id="journal-search-input" placeholder="Search journals...">
        <button id="journal-search-btn" title="Search">🔍</button>
      </div>
    </div>
    <textarea id="journal-content" placeholder="Daily notes..."></textarea>
    <div class="journal-controls">
      <button id="save-journal">Save journal</button>
    </div>

    <div class="dropzone" id="dropzone">
      <span class="dropzone-text">Drop files here or click to upload</span>
      <input type="file" id="file-input" multiple>
    </div>

    <div class="attachments-list" id="attachments-list"></div>

    <div class="index-section">
      <h3>Index Entry</h3>
      <form class="add-index-form" id="add-index-form">
        <input type="text" id="new-index-summary" placeholder="Add to monthly index (e.g., 'Insurance auth for hospital')">
        <button type="submit">Add</button>
      </form>
      <div class="index-entries-list" id="index-entries-list"></div>
    </div>
  </div>
</main>

<!-- Search Modal -->
<div class="modal-overlay" id="search-modal">
  <div class="modal">
    <div class="modal-header">
      <h2>Search Journals</h2>
      <button id="search-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <div class="search-input-row">
        <input type="text" id="search-query" placeholder="Search journal entries...">
        <button id="search-go">Search</button>
      </div>
      <div class="search-results" id="search-results"></div>
    </div>
  </div>
</div>

<!-- Monthly Index Modal -->
<div class="modal-overlay" id="month-index-modal">
  <div class="modal">
    <div class="modal-header">
      <h2>Monthly Index</h2>
      <button id="month-index-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <div class="modal-nav">
        <button id="index-prev-year" title="Previous year">&laquo;</button>
        <button id="index-prev-month" title="Previous month">&lsaquo;</button>
        <span class="month-year" id="index-month-year"></span>
        <button id="index-next-month" title="Next month">&rsaquo;</button>
        <button id="index-next-year" title="Next year">&raquo;</button>
      </div>
      <div class="month-index-list" id="month-index-list"></div>
    </div>
  </div>
</div>

<!-- Zmanim Modal -->
<div class="modal-overlay" id="zmanim-modal">
  <div class="modal">
    <div class="modal-header">
      <h2>Zmanim</h2>
      <button id="zmanim-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <div class="zmanim-location" id="zmanim-location"></div>
      <div class="zmanim-grid" id="zmanim-grid"></div>
    </div>
  </div>
</div>

<!-- Yartzheits Modal -->
<div class="modal-overlay" id="yartzheits-modal">
  <div class="modal">
    <div class="modal-header">
      <h2>Yartzheits</h2>
      <button id="yartzheits-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form class="yartzheit-form" id="yartzheit-form">
        <div class="yartzheit-form-row">
          <input type="text" id="yartzheit-name" placeholder="Name (e.g., Avraham ben Yitzchak)" required>
        </div>
        <div class="yartzheit-form-row">
          <select id="yartzheit-day" required>
            <option value="">Day</option>
            <script>for(let i=1;i<=30;i++) document.write('<option value="'+i+'">'+i+'</option>');</script>
          </select>
          <select id="yartzheit-month" required>
            <option value="">Month</option>
            <option value="7">Tishrei</option>
            <option value="8">Cheshvan</option>
            <option value="9">Kislev</option>
            <option value="10">Tevet</option>
            <option value="11">Shvat</option>
            <option value="12">Adar</option>
            <option value="13">Adar II</option>
            <option value="1">Nisan</option>
            <option value="2">Iyyar</option>
            <option value="3">Sivan</option>
            <option value="4">Tamuz</option>
            <option value="5">Av</option>
            <option value="6">Elul</option>
          </select>
          <input type="text" id="yartzheit-relationship" placeholder="Relationship (optional)">
        </div>
        <div class="yartzheit-form-row">
          <button type="submit">Add Yartzheit</button>
        </div>
      </form>
      <div class="yartzheit-list" id="yartzheit-list"></div>
    </div>
  </div>
</div>

<!-- Recurring Tasks Modal -->
<div class="modal-overlay" id="recurring-modal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <h2>Recurring Tasks</h2>
      <button id="recurring-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form class="recurring-form" id="recurring-form">
        <div class="recurring-form-row">
          <input type="text" id="recurring-text" placeholder="Task text..." required style="flex:1;">
          <select id="recurring-priority">
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C" selected>C</option>
            <option value="D">D</option>
          </select>
        </div>
        <div class="recurring-form-row">
          <select id="recurring-pattern-type" required>
            <option value="">-- Pattern --</option>
            <option value="day_of_month">Day of month</option>
            <option value="day_of_week">Day of week</option>
            <option value="interval_days">Every N days</option>
            <option value="interval_weeks">Every N weeks</option>
            <option value="interval_months">Every N months</option>
          </select>
          <input type="number" id="recurring-pattern-value" placeholder="Value" min="1" max="31" style="width:80px;">
          <select id="recurring-weekday" style="display:none;">
            <option value="0">Sunday</option>
            <option value="1">Monday</option>
            <option value="2">Tuesday</option>
            <option value="3">Wednesday</option>
            <option value="4">Thursday</option>
            <option value="5">Friday</option>
            <option value="6">Saturday</option>
          </select>
        </div>
        <div class="recurring-form-row">
          <label style="margin-right:8px;">Starting:</label>
          <input type="date" id="recurring-anchor-date" required>
          <label style="margin-left:16px;margin-right:8px;">Until:</label>
          <input type="date" id="recurring-end-date" placeholder="(optional)">
        </div>
        <div class="recurring-form-row">
          <button type="submit">Add Recurring Task</button>
        </div>
      </form>
      <div class="recurring-list" id="recurring-list"></div>
    </div>
  </div>
</div>

<!-- Torah Modal -->
<div class="modal-overlay" id="torah-modal">
  <div class="modal" style="max-width:550px;">
    <div class="modal-header">
      <h2 id="torah-modal-title">📖 Daily Learning</h2>
      <button id="torah-modal-close">&times;</button>
    </div>
    <div class="modal-body" id="torah-content">
      <div class="parsha-loading">Loading...</div>
    </div>
  </div>
</div>

<!-- Settings Modal -->
<div class="modal-overlay" id="settings-modal">
  <div class="modal" style="max-width:650px;">
    <div class="modal-header">
      <h2>Settings</h2>
      <button id="settings-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form class="settings-form" id="settings-form">
        <!-- Theme Section -->
        <div class="settings-section">
          <div class="settings-section-title">Theme</div>
          <div class="theme-presets" id="theme-presets"></div>

          <div class="theme-custom-row">
            <label>Font Family</label>
            <select id="theme-font-family">
              <option value="system-ui, -apple-system, BlinkMacSystemFont, sans-serif">System Default</option>
              <option value="'Segoe UI', Tahoma, Geneva, Verdana, sans-serif">Segoe UI</option>
              <option value="'Helvetica Neue', Helvetica, Arial, sans-serif">Helvetica</option>
              <option value="'Inter', sans-serif">Inter</option>
              <option value="'Roboto', sans-serif">Roboto</option>
              <option value="Georgia, 'Times New Roman', serif">Georgia (Serif)</option>
              <option value="'Courier New', Consolas, monospace">Monospace</option>
              <option value="'Comic Sans MS', cursive">Comic Sans</option>
            </select>
          </div>

          <div class="theme-custom-row">
            <label>Font Size</label>
            <input type="range" id="theme-font-size" min="12" max="20" value="14">
            <span class="font-size-display" id="font-size-display">14px</span>
          </div>

          <div class="theme-custom-row">
            <label>Background</label>
            <input type="color" id="theme-bg-primary" value="#111111">
          </div>

          <div class="theme-custom-row">
            <label>Panel Background</label>
            <input type="color" id="theme-bg-secondary" value="#181818">
          </div>

          <div class="theme-custom-row">
            <label>Text Color</label>
            <input type="color" id="theme-text-primary" value="#eeeeee">
          </div>

          <div class="theme-custom-row">
            <label>Accent Color</label>
            <input type="color" id="theme-accent-primary" value="#4a90e2">
          </div>
        </div>

        <!-- Display Section -->
        <div class="settings-section">
          <div class="settings-section-title">Display</div>
          <div class="settings-row">
            <label class="checkbox-label">
              <input type="checkbox" id="setting-hide-done">
              <span>Hide done tasks</span>
            </label>
          </div>
          <div class="settings-row">
            <label class="checkbox-label">
              <input type="checkbox" id="setting-done-section">
              <span>Show done tasks in separate section below D</span>
            </label>
            <div class="settings-help">When enabled, completed tasks move to a "Done" section instead of being hidden</div>
          </div>
        </div>

        <!-- Keyboard Shortcuts Section -->
        <div class="settings-section">
          <div class="settings-section-title">Keyboard Shortcuts</div>
          <div class="settings-help">Configure keyboard shortcuts. Format: ctrl+key, alt+key, shift+key, or combinations like ctrl+shift+key</div>
          <div class="shortcuts-list" id="shortcuts-list">
            <div class="shortcut-row">
              <label>Save journal</label>
              <input type="text" class="shortcut-input" data-action="save_journal" value="ctrl+enter" readonly>
              <button type="button" class="shortcut-record-btn" title="Click to record new shortcut">Record</button>
            </div>
            <div class="shortcut-row">
              <label>Previous day</label>
              <input type="text" class="shortcut-input" data-action="prev_day" value="alt+left" readonly>
              <button type="button" class="shortcut-record-btn" title="Click to record new shortcut">Record</button>
            </div>
            <div class="shortcut-row">
              <label>Next day</label>
              <input type="text" class="shortcut-input" data-action="next_day" value="alt+right" readonly>
              <button type="button" class="shortcut-record-btn" title="Click to record new shortcut">Record</button>
            </div>
            <div class="shortcut-row">
              <label>Go to today</label>
              <input type="text" class="shortcut-input" data-action="today" value="alt+t" readonly>
              <button type="button" class="shortcut-record-btn" title="Click to record new shortcut">Record</button>
            </div>
            <div class="shortcut-row">
              <label>Focus new task</label>
              <input type="text" class="shortcut-input" data-action="new_task" value="alt+n" readonly>
              <button type="button" class="shortcut-record-btn" title="Click to record new shortcut">Record</button>
            </div>
            <div class="shortcut-row">
              <label>Open search</label>
              <input type="text" class="shortcut-input" data-action="open_search" value="ctrl+k" readonly>
              <button type="button" class="shortcut-record-btn" title="Click to record new shortcut">Record</button>
            </div>
          </div>
          <button type="button" class="btn-reset-shortcuts" id="reset-shortcuts-btn">Reset to Defaults</button>
        </div>

        <!-- Location Section -->
        <div class="settings-section">
          <div class="settings-section-title">Location</div>
          <div class="settings-row">
            <label for="setting-location">Location Name</label>
            <input type="text" id="setting-location" placeholder="e.g., Har Bracha, Israel">
          </div>
          <div class="settings-row-inline">
            <div class="settings-row">
              <label for="setting-latitude">Latitude</label>
              <input type="text" id="setting-latitude" placeholder="e.g., 32.1133">
            </div>
            <div class="settings-row">
              <label for="setting-longitude">Longitude</label>
              <input type="text" id="setting-longitude" placeholder="e.g., 35.3097">
            </div>
          </div>
          <div class="settings-help">Use Google Maps or similar to find coordinates. Right-click a location and copy coordinates.</div>
          <div class="settings-row-inline">
            <div class="settings-row">
              <label for="setting-timezone">Timezone</label>
              <select id="setting-timezone">
                <option value="Asia/Jerusalem">Asia/Jerusalem</option>
                <option value="America/New_York">America/New_York</option>
                <option value="America/Chicago">America/Chicago</option>
                <option value="America/Denver">America/Denver</option>
                <option value="America/Los_Angeles">America/Los_Angeles</option>
                <option value="Europe/London">Europe/London</option>
                <option value="Europe/Paris">Europe/Paris</option>
                <option value="Australia/Sydney">Australia/Sydney</option>
              </select>
            </div>
            <div class="settings-row">
              <label for="setting-elevation">Elevation (meters)</label>
              <input type="number" id="setting-elevation" placeholder="e.g., 829">
            </div>
          </div>
        </div>

        <!-- Calendar Integration Section -->
        <div class="settings-section">
          <div class="settings-section-title">Calendar Integration</div>
          <div id="calendar-list"></div>
          <button type="button" class="btn-add-calendar" id="add-calendar-btn">+ Add Calendar</button>
          <div class="settings-help">Events from these calendars will be imported as tasks automatically. Find the ICS URL under Google Calendar Settings > Integrate calendar > Secret address in iCal format.</div>
        </div>

        <div class="settings-actions">
          <button type="button" class="btn-cancel" id="settings-cancel">Cancel</button>
          <button type="submit" class="btn-save">Save Settings</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Journal Search Results Modal -->
<div class="modal-overlay" id="journal-search-modal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <h2>Journal Search Results</h2>
      <button id="journal-search-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <div id="journal-search-results"></div>
    </div>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="change-password-modal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h2>Change Password</h2>
      <button id="change-password-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form class="settings-form" id="change-password-form">
        <div class="settings-row">
          <label for="current-password">Current Password</label>
          <input type="password" id="current-password" autocomplete="current-password" required>
        </div>
        <div class="settings-row">
          <label for="new-password">New Password</label>
          <input type="password" id="new-password" autocomplete="new-password" required minlength="8">
        </div>
        <div class="settings-row">
          <label for="confirm-password">Confirm New Password</label>
          <input type="password" id="confirm-password" autocomplete="new-password" required minlength="8">
        </div>
        <div class="settings-help">Password must be at least 8 characters.</div>
        <div class="settings-actions">
          <button type="button" class="btn-cancel" id="change-password-cancel">Cancel</button>
          <button type="submit" class="btn-save">Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Task Details Modal -->
<div class="modal-overlay task-details-modal" id="task-details-modal">
  <div class="modal">
    <div class="modal-header">
      <h2>Task Details</h2>
      <button id="task-details-modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form class="task-details-form" id="task-details-form">
        <input type="hidden" id="task-detail-id">
        <div class="task-details-row">
          <label for="task-detail-text">Task</label>
          <textarea id="task-detail-text" placeholder="Task description..."></textarea>
        </div>
        <div class="task-details-row">
          <label for="task-detail-notes">Notes</label>
          <textarea id="task-detail-notes" class="task-notes" placeholder="Additional details, notes, or context..."></textarea>
        </div>
        <div class="task-details-row" style="flex-direction:row; gap:12px;">
          <div style="flex:1;">
            <label for="task-detail-priority">Priority</label>
            <select id="task-detail-priority" style="width:100%;">
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="C">C</option>
              <option value="D">D</option>
            </select>
          </div>
          <div style="flex:1;">
            <label for="task-detail-status">Status</label>
            <select id="task-detail-status" style="width:100%;"></select>
          </div>
        </div>
        <div class="copy-date-row">
          <label>Copy to:</label>
          <input type="text" id="task-copy-date" placeholder="YYYY-MM-DD">
          <button type="button" id="task-copy-btn">Copy</button>
        </div>
        <div class="task-details-actions">
          <button type="button" class="btn-cancel" id="task-details-cancel">Cancel</button>
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal confirm-modal">
    <div class="modal-body">
      <div class="confirm-message" id="confirm-message">Are you sure?</div>
      <div class="confirm-buttons">
        <button class="btn-cancel" id="confirm-cancel">Cancel</button>
        <button class="btn-danger" id="confirm-ok">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
const statuses = [
  { value: 'todo',        label: 'To do' },
  { value: 'planning',    label: 'Planning' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'waiting',     label: 'Waiting' },
  { value: 'done',        label: 'Done' },
];

let currentDate = '<?php echo $today; ?>';
let tasks = [];
let journalContent = '';
let attachments = [];
let indexEntries = [];
let hebrewInfo = null;
let allYartzheits = [];
let appSettings = {};
let currentTheme = {};

// Keyboard shortcuts system
const defaultShortcuts = {
  save_journal: 'ctrl+enter',
  prev_day: 'alt+left',
  next_day: 'alt+right',
  today: 'alt+t',
  new_task: 'alt+n',
  open_search: 'ctrl+k'
};

let shortcuts = { ...defaultShortcuts };

function loadShortcuts() {
  try {
    const saved = localStorage.getItem('planner_shortcuts');
    if (saved) {
      shortcuts = { ...defaultShortcuts, ...JSON.parse(saved) };
    }
  } catch (e) {
    shortcuts = { ...defaultShortcuts };
  }
}

function saveShortcuts() {
  localStorage.setItem('planner_shortcuts', JSON.stringify(shortcuts));
}

function keyEventToString(e) {
  const parts = [];
  if (e.ctrlKey || e.metaKey) parts.push('ctrl');
  if (e.altKey) parts.push('alt');
  if (e.shiftKey) parts.push('shift');

  let key = e.key.toLowerCase();
  // Normalize special keys
  if (key === 'arrowleft') key = 'left';
  if (key === 'arrowright') key = 'right';
  if (key === 'arrowup') key = 'up';
  if (key === 'arrowdown') key = 'down';
  if (key === ' ') key = 'space';

  // Don't add modifier keys alone
  if (['control', 'alt', 'shift', 'meta'].includes(key)) return null;

  parts.push(key);
  return parts.join('+');
}

function matchesShortcut(e, shortcutStr) {
  if (!shortcutStr) return false;
  const eventStr = keyEventToString(e);
  return eventStr === shortcutStr;
}

function populateShortcutInputs() {
  document.querySelectorAll('.shortcut-input').forEach(input => {
    const action = input.dataset.action;
    input.value = shortcuts[action] || defaultShortcuts[action] || '';
  });
}

let recordingInput = null;

function initShortcutRecording() {
  document.querySelectorAll('.shortcut-record-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('.shortcut-row');
      const input = row.querySelector('.shortcut-input');

      if (recordingInput === input) {
        // Cancel recording
        stopRecording();
        return;
      }

      // Stop any existing recording
      stopRecording();

      // Start recording for this input
      recordingInput = input;
      input.classList.add('recording');
      btn.classList.add('recording');
      btn.textContent = 'Press keys...';
      input.value = 'Press shortcut...';
    });
  });
}

function stopRecording() {
  if (recordingInput) {
    const row = recordingInput.closest('.shortcut-row');
    const btn = row.querySelector('.shortcut-record-btn');
    const action = recordingInput.dataset.action;

    recordingInput.classList.remove('recording');
    btn.classList.remove('recording');
    btn.textContent = 'Record';
    recordingInput.value = shortcuts[action] || defaultShortcuts[action] || '';
    recordingInput = null;
  }
}

function handleShortcutRecording(e) {
  if (!recordingInput) return;

  e.preventDefault();
  e.stopPropagation();

  const shortcutStr = keyEventToString(e);
  if (!shortcutStr) return; // Just a modifier key

  const action = recordingInput.dataset.action;
  shortcuts[action] = shortcutStr;
  recordingInput.value = shortcutStr;

  stopRecording();
}

function resetShortcuts() {
  shortcuts = { ...defaultShortcuts };
  populateShortcutInputs();
}

// Global keyboard shortcut handler
function handleGlobalShortcuts(e) {
  // Don't trigger shortcuts when recording
  if (recordingInput) {
    handleShortcutRecording(e);
    return;
  }

  // Don't trigger shortcuts when typing in input fields (except for specific cases)
  const target = e.target;
  const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT';

  // Allow save_journal shortcut in journal textarea
  if (target.id === 'journal-content' && matchesShortcut(e, shortcuts.save_journal)) {
    e.preventDefault();
    saveJournal();
    return;
  }

  // Skip other shortcuts if in input (unless ctrl+k for search)
  if (isInput && !matchesShortcut(e, shortcuts.open_search)) {
    return;
  }

  // Handle global shortcuts
  if (matchesShortcut(e, shortcuts.prev_day)) {
    e.preventDefault();
    goToPrevDay();
  } else if (matchesShortcut(e, shortcuts.next_day)) {
    e.preventDefault();
    goToNextDay();
  } else if (matchesShortcut(e, shortcuts.today)) {
    e.preventDefault();
    goToToday();
  } else if (matchesShortcut(e, shortcuts.new_task)) {
    e.preventDefault();
    document.getElementById('new-task-text').focus();
  } else if (matchesShortcut(e, shortcuts.open_search)) {
    e.preventDefault();
    openSearchModal();
  }
}

// Custom confirm modal
function showConfirm(message, okText = 'Delete') {
  return new Promise((resolve) => {
    const modal = document.getElementById('confirm-modal');
    const msgEl = document.getElementById('confirm-message');
    const okBtn = document.getElementById('confirm-ok');
    const cancelBtn = document.getElementById('confirm-cancel');

    msgEl.textContent = message;
    okBtn.textContent = okText;
    modal.classList.add('open');

    function cleanup() {
      modal.classList.remove('open');
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
    }

    function onOk() {
      cleanup();
      resolve(true);
    }

    function onCancel() {
      cleanup();
      resolve(false);
    }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
  });
}

// Toast notifications
function showToast(message, type = 'info', duration = 3000) {
  // Remove any existing toast
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = 'toast ' + type;
  toast.textContent = message;
  document.body.appendChild(toast);

  // Trigger reflow then show
  toast.offsetHeight;
  toast.classList.add('show');

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// Theme presets
const themePresets = {
  'Dark (Default)': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 14,
    bgPrimary: '#111111',
    bgSecondary: '#181818',
    bgTertiary: '#222222',
    bgInput: '#222222',
    bgHover: '#333333',
    bgActive: '#444444',
    textPrimary: '#eeeeee',
    textSecondary: '#aaaaaa',
    textMuted: '#888888',
    textFaint: '#666666',
    borderPrimary: '#333333',
    borderSecondary: '#444444',
    borderInput: '#555555',
    accentPrimary: '#4a90e2'
  },
  'Light': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 14,
    bgPrimary: '#f5f5f5',
    bgSecondary: '#ffffff',
    bgTertiary: '#fafafa',
    bgInput: '#ffffff',
    bgHover: '#e8e8e8',
    bgActive: '#d0d0d0',
    textPrimary: '#1a1a1a',
    textSecondary: '#555555',
    textMuted: '#777777',
    textFaint: '#999999',
    borderPrimary: '#dddddd',
    borderSecondary: '#cccccc',
    borderInput: '#bbbbbb',
    accentPrimary: '#2563eb'
  },
  'Midnight Blue': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 14,
    bgPrimary: '#0a0e1a',
    bgSecondary: '#111827',
    bgTertiary: '#1e293b',
    bgInput: '#1e293b',
    bgHover: '#334155',
    bgActive: '#475569',
    textPrimary: '#e2e8f0',
    textSecondary: '#94a3b8',
    textMuted: '#64748b',
    textFaint: '#475569',
    borderPrimary: '#1e293b',
    borderSecondary: '#334155',
    borderInput: '#475569',
    accentPrimary: '#3b82f6'
  },
  'Forest Green': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 14,
    bgPrimary: '#0d1912',
    bgSecondary: '#142119',
    bgTertiary: '#1a2e22',
    bgInput: '#1a2e22',
    bgHover: '#234430',
    bgActive: '#2d5a3e',
    textPrimary: '#d4e8dc',
    textSecondary: '#9cc5a8',
    textMuted: '#6b9b7a',
    textFaint: '#4a7a5a',
    borderPrimary: '#1a2e22',
    borderSecondary: '#234430',
    borderInput: '#2d5a3e',
    accentPrimary: '#22c55e'
  },
  'Warm Sepia': {
    fontFamily: "Georgia, 'Times New Roman', serif",
    fontSize: 15,
    bgPrimary: '#f4f1ea',
    bgSecondary: '#fffcf5',
    bgTertiary: '#faf7f0',
    bgInput: '#fffcf5',
    bgHover: '#e8e2d5',
    bgActive: '#d5cdbf',
    textPrimary: '#3d3529',
    textSecondary: '#5c5244',
    textMuted: '#7a7060',
    textFaint: '#998f7f',
    borderPrimary: '#d5cdbf',
    borderSecondary: '#c5baa8',
    borderInput: '#b5a898',
    accentPrimary: '#b45309'
  },
  'Purple Haze': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 14,
    bgPrimary: '#13111a',
    bgSecondary: '#1a1625',
    bgTertiary: '#241f33',
    bgInput: '#241f33',
    bgHover: '#352d4a',
    bgActive: '#463b62',
    textPrimary: '#e8e4f0',
    textSecondary: '#b8aed0',
    textMuted: '#8878a8',
    textFaint: '#5a4a78',
    borderPrimary: '#241f33',
    borderSecondary: '#352d4a',
    borderInput: '#463b62',
    accentPrimary: '#a855f7'
  },
  'Ocean': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 14,
    bgPrimary: '#0c1821',
    bgSecondary: '#162028',
    bgTertiary: '#1b3a4b',
    bgInput: '#1b3a4b',
    bgHover: '#274c5a',
    bgActive: '#326d82',
    textPrimary: '#d5e6ef',
    textSecondary: '#9fc5d8',
    textMuted: '#6aa3c0',
    textFaint: '#4682a8',
    borderPrimary: '#1b3a4b',
    borderSecondary: '#274c5a',
    borderInput: '#326d82',
    accentPrimary: '#06b6d4'
  },
  'High Contrast': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 16,
    bgPrimary: '#000000',
    bgSecondary: '#0a0a0a',
    bgTertiary: '#141414',
    bgInput: '#141414',
    bgHover: '#1f1f1f',
    bgActive: '#2a2a2a',
    textPrimary: '#ffffff',
    textSecondary: '#e0e0e0',
    textMuted: '#b0b0b0',
    textFaint: '#808080',
    borderPrimary: '#333333',
    borderSecondary: '#444444',
    borderInput: '#555555',
    accentPrimary: '#ffcc00'
  },
  'Solarized Dark': {
    fontFamily: "'Courier New', Consolas, monospace",
    fontSize: 14,
    bgPrimary: '#002b36',
    bgSecondary: '#073642',
    bgTertiary: '#094050',
    bgInput: '#094050',
    bgHover: '#0b5060',
    bgActive: '#0d6070',
    textPrimary: '#839496',
    textSecondary: '#657b83',
    textMuted: '#586e75',
    textFaint: '#4a6068',
    borderPrimary: '#073642',
    borderSecondary: '#094050',
    borderInput: '#0b5060',
    accentPrimary: '#268bd2'
  },
  'Rose': {
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    fontSize: 14,
    bgPrimary: '#1a1015',
    bgSecondary: '#24181e',
    bgTertiary: '#31202a',
    bgInput: '#31202a',
    bgHover: '#472d3a',
    bgActive: '#5d3a4a',
    textPrimary: '#f5e6eb',
    textSecondary: '#d4b8c4',
    textMuted: '#b08a9c',
    textFaint: '#8c5c74',
    borderPrimary: '#31202a',
    borderSecondary: '#472d3a',
    borderInput: '#5d3a4a',
    accentPrimary: '#f43f5e'
  }
};

// Apply theme to CSS variables
function applyTheme(theme) {
  const root = document.documentElement;
  root.style.setProperty('--font-family', theme.fontFamily);
  root.style.setProperty('--font-size-base', theme.fontSize + 'px');
  root.style.setProperty('--bg-primary', theme.bgPrimary);
  root.style.setProperty('--bg-secondary', theme.bgSecondary);
  root.style.setProperty('--bg-tertiary', theme.bgTertiary);
  root.style.setProperty('--bg-input', theme.bgInput);
  root.style.setProperty('--bg-hover', theme.bgHover);
  root.style.setProperty('--bg-active', theme.bgActive);
  root.style.setProperty('--text-primary', theme.textPrimary);
  root.style.setProperty('--text-secondary', theme.textSecondary);
  root.style.setProperty('--text-muted', theme.textMuted);
  root.style.setProperty('--text-faint', theme.textFaint);
  root.style.setProperty('--border-primary', theme.borderPrimary);
  root.style.setProperty('--border-secondary', theme.borderSecondary);
  root.style.setProperty('--border-input', theme.borderInput);
  root.style.setProperty('--accent-primary', theme.accentPrimary);
  currentTheme = { ...theme };
}

// Helper function to adjust color brightness
function adjustColor(hex, amount) {
  let r = parseInt(hex.slice(1, 3), 16);
  let g = parseInt(hex.slice(3, 5), 16);
  let b = parseInt(hex.slice(5, 7), 16);
  r = Math.max(0, Math.min(255, r + amount));
  g = Math.max(0, Math.min(255, g + amount));
  b = Math.max(0, Math.min(255, b + amount));
  return '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('');
}

// Generate derived colors from base colors
function generateThemeFromBase(bgPrimary, bgSecondary, textPrimary, accentPrimary, fontFamily, fontSize) {
  const isDark = parseInt(bgPrimary.slice(1), 16) < 0x888888;
  return {
    fontFamily: fontFamily,
    fontSize: fontSize,
    bgPrimary: bgPrimary,
    bgSecondary: bgSecondary,
    bgTertiary: adjustColor(bgSecondary, isDark ? 15 : -10),
    bgInput: adjustColor(bgSecondary, isDark ? 15 : -5),
    bgHover: adjustColor(bgSecondary, isDark ? 30 : -20),
    bgActive: adjustColor(bgSecondary, isDark ? 50 : -35),
    textPrimary: textPrimary,
    textSecondary: adjustColor(textPrimary, isDark ? -60 : 60),
    textMuted: adjustColor(textPrimary, isDark ? -100 : 100),
    textFaint: adjustColor(textPrimary, isDark ? -130 : 130),
    borderPrimary: adjustColor(bgSecondary, isDark ? 30 : -30),
    borderSecondary: adjustColor(bgSecondary, isDark ? 45 : -45),
    borderInput: adjustColor(bgSecondary, isDark ? 60 : -55),
    accentPrimary: accentPrimary
  };
}

// Save theme to localStorage
function saveThemeToStorage(theme, presetName) {
  localStorage.setItem('planner_theme', JSON.stringify(theme));
  localStorage.setItem('planner_theme_preset', presetName || 'Custom');
}

// Load theme from localStorage
function loadThemeFromStorage() {
  const savedTheme = localStorage.getItem('planner_theme');
  const savedPreset = localStorage.getItem('planner_theme_preset') || 'Dark (Default)';
  if (savedTheme) {
    try {
      const theme = JSON.parse(savedTheme);
      applyTheme(theme);
      return { theme, presetName: savedPreset };
    } catch (e) {
      console.error('Failed to parse saved theme', e);
    }
  }
  // Default theme
  applyTheme(themePresets['Dark (Default)']);
  return { theme: themePresets['Dark (Default)'], presetName: 'Dark (Default)' };
}

// Render theme preset buttons
function renderThemePresets(activePreset) {
  const container = document.getElementById('theme-presets');
  container.innerHTML = '';
  Object.keys(themePresets).forEach(name => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'theme-preset-btn' + (name === activePreset ? ' active' : '');
    btn.textContent = name;
    btn.addEventListener('click', () => {
      applyTheme(themePresets[name]);
      updateThemeControls(themePresets[name]);
      renderThemePresets(name);
      saveThemeToStorage(themePresets[name], name);
    });
    container.appendChild(btn);
  });
}

// Update theme control inputs to match current theme
function updateThemeControls(theme) {
  document.getElementById('theme-font-family').value = theme.fontFamily;
  document.getElementById('theme-font-size').value = theme.fontSize;
  document.getElementById('font-size-display').textContent = theme.fontSize + 'px';
  document.getElementById('theme-bg-primary').value = theme.bgPrimary;
  document.getElementById('theme-bg-secondary').value = theme.bgSecondary;
  document.getElementById('theme-text-primary').value = theme.textPrimary;
  document.getElementById('theme-accent-primary').value = theme.accentPrimary;
}

// Handle custom theme changes
function onThemeCustomChange() {
  const fontFamily = document.getElementById('theme-font-family').value;
  const fontSize = parseInt(document.getElementById('theme-font-size').value);
  const bgPrimary = document.getElementById('theme-bg-primary').value;
  const bgSecondary = document.getElementById('theme-bg-secondary').value;
  const textPrimary = document.getElementById('theme-text-primary').value;
  const accentPrimary = document.getElementById('theme-accent-primary').value;

  const customTheme = generateThemeFromBase(bgPrimary, bgSecondary, textPrimary, accentPrimary, fontFamily, fontSize);
  applyTheme(customTheme);
  saveThemeToStorage(customTheme, 'Custom');
  renderThemePresets('Custom');
}

const priorityOrder = ['A','B','C','D'];

// Calendar state
let calendarViewDate = new Date(); // The month/year being viewed in the calendar

// Monthly index modal state
let indexViewDate = new Date();

function apiPost(payload) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  return fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  }).then(r => r.json());
}

function formatPrettyDate(dateStr) {
  if (!dateStr) return '';
  const [y, m, d] = dateStr.split('-').map(Number);
  const dt = new Date(Date.UTC(y, m - 1, d));
  const month = dt.toLocaleDateString('en-US', { month: 'long', timeZone: 'UTC' });
  const day = String(d).padStart(2, '0');
  return y + '-' + month + '-' + day;
}

function updatePrettyDate() {
  const span = document.getElementById('pretty-date');
  if (span) {
    span.textContent = formatPrettyDate(currentDate);
  }
  // Update month index button text
  const monthIndexBtn = document.getElementById('month-index-btn');
  if (monthIndexBtn) {
    const d = new Date(currentDate + 'T00:00:00');
    const monthName = d.toLocaleDateString('en-US', { month: 'long' });
    monthIndexBtn.textContent = monthName + ' Index';
  }
}

function loadDay(date) {
  apiPost({ action: 'get_day', date: date }).then(data => {
    if (!data.success) {
      showToast('Error loading day', 'error');
      return;
    }
    currentDate = date;
    tasks = data.tasks;
    journalContent = data.journal || '';

    document.getElementById('journal-content').value = journalContent;

    updatePrettyDate();
    renderTasks();
    loadAttachments();
    loadIndexEntries();
    loadHebrewInfo();
    checkForUncompletedTasks();

    // Auto-import calendar events (silently, only shows toast if events imported)
    importCalendarEvents(date);
  });
}

function importCalendarEvents(date) {
  apiPost({ action: 'import_calendar_events', date: date }).then(data => {
    if (data.success && data.imported > 0) {
      tasks = data.tasks;
      renderTasks();
      showToast(`Imported ${data.imported} calendar event${data.imported > 1 ? 's' : ''}`, 'success');
    }
  });
}

function loadAttachments() {
  apiPost({ action: 'get_attachments', date: currentDate }).then(data => {
    if (data.success) {
      attachments = data.attachments || [];
      renderAttachments();
    }
  });
}

function renderTasks() {
  const container = document.getElementById('priority-sections');
  container.innerHTML = '';

  const hideDone = localStorage.getItem('planner_hide_done') === 'true';
  const showDoneSection = localStorage.getItem('planner_done_section') === 'true';

  priorityOrder.forEach(priority => {
    const section = document.createElement('div');
    section.className = 'priority-section';
    section.dataset.priority = priority;

    const header = document.createElement('div');
    header.className = 'priority-header';
    const title = document.createElement('div');
    title.className = 'priority-title';
    title.textContent = 'Priority ' + priority;
    header.appendChild(title);
    section.appendChild(header);

    const list = document.createElement('ul');
    list.className = 'task-list';
    list.dataset.priority = priority;
    list.addEventListener('dragover', handleDragOver);
    list.addEventListener('drop', handleDrop);

    // Filter tasks based on settings
    // If done section is enabled, exclude done tasks from priority sections
    // If hide done is enabled (and done section disabled), exclude done tasks
    const filtered = tasks
      .filter(t => t.priority === priority && (showDoneSection ? t.status !== 'done' : (!hideDone || t.status !== 'done')))
      .sort((a, b) => a.sort_order - b.sort_order);

    filtered.forEach(task => {
      const item = document.createElement('li');
      item.className = 'task-item';
      item.draggable = true;
      item.dataset.taskId = task.id;
      item.dataset.priority = priority;
      item.addEventListener('dragstart', handleDragStart);
      item.addEventListener('dragend', handleDragEnd);

      const dragHandle = document.createElement('span');
      dragHandle.className = 'drag-handle';
      dragHandle.textContent = '⋮⋮';
      dragHandle.title = 'Drag to reorder';

      const left = document.createElement('div');
      left.className = 'task-left';
      const text = document.createElement('div');
      text.className = 'task-text';
      text.textContent = task.text;

      const meta = document.createElement('div');
      meta.className = 'task-meta';
      let metaText = 'Status: ' + task.status;
      if (task.notes && task.notes.trim()) {
        metaText += ' | Has notes';
      }
      meta.textContent = metaText;

      const actions = document.createElement('div');
      actions.className = 'task-actions';

      const detailsBtn = document.createElement('button');
      detailsBtn.textContent = 'Details';
      detailsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openTaskDetails(task.id);
      });

      const copyNextBtn = document.createElement('button');
      copyNextBtn.textContent = 'Copy to Tomorrow';
      copyNextBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        copyTaskToNextDay(task.id);
      });

      actions.appendChild(detailsBtn);
      actions.appendChild(copyNextBtn);

      left.appendChild(text);
      left.appendChild(meta);
      left.appendChild(actions);

      const controls = document.createElement('div');
      controls.className = 'task-controls';

      const statusSelect = document.createElement('select');
      statusSelect.className = 'task-status-select';
      statuses.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.value;
        opt.textContent = s.label;
        if (task.status === s.value) opt.selected = true;
        statusSelect.appendChild(opt);
      });
      statusSelect.addEventListener('change', () => {
        updateTask(task.id, { status: statusSelect.value });
      });

      const statusDot = document.createElement('span');
      statusDot.className = 'status-dot status-' + task.status.replace(' ','_');

      const completeBtn = document.createElement('button');
      completeBtn.textContent = '✓';
      completeBtn.title = 'Mark as done';
      completeBtn.addEventListener('click', () => {
        updateTask(task.id, { status: 'done' });
      });

      const deleteBtn = document.createElement('button');
      deleteBtn.textContent = 'X';
      deleteBtn.addEventListener('click', async () => {
        if (await showConfirm('Delete this task?')) {
          deleteTask(task.id);
        }
      });

      const pomodoroBtn = document.createElement('button');
      pomodoroBtn.className = 'pomodoro-btn';
      pomodoroBtn.textContent = '🍅';
      pomodoroBtn.title = 'Start Pomodoro (25 min)';
      pomodoroBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        startPomodoro(task.id, task.text);
      });

      controls.appendChild(pomodoroBtn);
      controls.appendChild(statusDot);
      controls.appendChild(statusSelect);
      controls.appendChild(completeBtn);
      controls.appendChild(deleteBtn);

      item.appendChild(dragHandle);
      item.appendChild(left);
      item.appendChild(controls);
      list.appendChild(item);
    });

    section.appendChild(list);
    container.appendChild(section);
  });

  // Render Done section if enabled
  if (showDoneSection) {
    const doneTasks = tasks.filter(t => t.status === 'done').sort((a, b) => {
      // Sort by priority first, then by sort_order
      const priorityOrder = { A: 1, B: 2, C: 3, D: 4 };
      if (priorityOrder[a.priority] !== priorityOrder[b.priority]) {
        return priorityOrder[a.priority] - priorityOrder[b.priority];
      }
      return a.sort_order - b.sort_order;
    });

    if (doneTasks.length > 0) {
      const doneSection = document.createElement('div');
      doneSection.className = 'priority-section done-section';
      doneSection.dataset.priority = 'done';

      const header = document.createElement('div');
      header.className = 'priority-header';
      const title = document.createElement('div');
      title.className = 'priority-title';
      title.textContent = 'Done (' + doneTasks.length + ')';
      header.appendChild(title);
      doneSection.appendChild(header);

      const list = document.createElement('ul');
      list.className = 'task-list done-list';

      doneTasks.forEach(task => {
        const item = document.createElement('li');
        item.className = 'task-item done-task-item';
        item.dataset.taskId = task.id;

        const left = document.createElement('div');
        left.className = 'task-left';

        const priorityBadge = document.createElement('span');
        priorityBadge.className = 'done-priority-badge';
        priorityBadge.textContent = task.priority;

        const text = document.createElement('div');
        text.className = 'task-text';
        text.textContent = task.text;

        const actions = document.createElement('div');
        actions.className = 'task-actions';

        const detailsBtn = document.createElement('button');
        detailsBtn.textContent = 'Details';
        detailsBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          openTaskDetails(task.id);
        });
        actions.appendChild(detailsBtn);

        left.appendChild(priorityBadge);
        left.appendChild(text);
        left.appendChild(actions);

        const controls = document.createElement('div');
        controls.className = 'task-controls';

        const undoBtn = document.createElement('button');
        undoBtn.textContent = 'Undo';
        undoBtn.title = 'Mark as todo';
        undoBtn.addEventListener('click', () => {
          updateTask(task.id, { status: 'todo' });
        });

        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = 'X';
        deleteBtn.addEventListener('click', async () => {
          if (await showConfirm('Delete this task?')) {
            deleteTask(task.id);
          }
        });

        controls.appendChild(undoBtn);
        controls.appendChild(deleteBtn);

        item.appendChild(left);
        item.appendChild(controls);
        list.appendChild(item);
      });

      doneSection.appendChild(list);
      container.appendChild(doneSection);
    }
  }
}

// Drag and drop
let draggedId = null;

function handleDragStart(e) {
  // Only allow drag from the handle
  if (!e.target.classList.contains('drag-handle')) {
    e.preventDefault();
    return;
  }
  draggedId = this.dataset.taskId;
  e.dataTransfer.effectAllowed = 'move';
  this.style.opacity = '0.5';
}

function handleDragEnd(e) {
  this.style.opacity = '1';
}

function handleDragOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  const list = this;
  const afterElement = getDragAfterElement(list, e.clientY);
  const draggable = document.querySelector('.task-item[data-task-id="' + draggedId + '"]');
  if (!draggable) return;
  if (afterElement == null) {
    list.appendChild(draggable);
  } else {
    list.insertBefore(draggable, afterElement);
  }
}

function getDragAfterElement(container, y) {
  const draggableElements = [...container.querySelectorAll('.task-item')];

  return draggableElements.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offset = y - box.top - box.height / 2;
    if (offset < 0 && offset > closest.offset) {
      return { offset: offset, element: child };
    } else {
      return closest;
    }
  }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function handleDrop(e) {
  e.preventDefault();
  const list = this;
  const priority = list.dataset.priority;
  const ids = [...list.querySelectorAll('.task-item')].map(li => li.dataset.taskId);
  apiPost({ action: 'reorder_tasks', date: currentDate, priority: priority, ordered_ids: ids })
    .then(data => {
      if (!data.success) {
        showToast('Error saving order', 'error');
      } else {
        tasks = data.tasks;
        renderTasks();
      }
    });
}

// Task operations
function addTask(text, priority) {
  apiPost({ action: 'add_task', date: currentDate, text, priority }).then(data => {
    if (!data.success) {
      showToast('Error adding task', 'error');
      return;
    }
    tasks = data.tasks;
    renderTasks();
  });
}

function updateTask(id, fields) {
  apiPost({ action: 'update_task', id, fields }).then(data => {
    if (!data.success) {
      showToast('Error updating task', 'error');
      return;
    }
    tasks = data.tasks;
    renderTasks();
  });
}

function deleteTask(id) {
  apiPost({ action: 'delete_task', id }).then(data => {
    if (!data.success) {
      showToast('Error deleting task', 'error');
      return;
    }
    tasks = data.tasks;
    renderTasks();
  });
}

// Task Details Modal
let currentDetailTaskId = null;

function openTaskDetails(taskId) {
  const task = tasks.find(t => t.id == taskId);
  if (!task) return;

  currentDetailTaskId = taskId;
  document.getElementById('task-detail-id').value = taskId;
  document.getElementById('task-detail-text').value = task.text || '';
  document.getElementById('task-detail-notes').value = task.notes || '';
  document.getElementById('task-detail-priority').value = task.priority || 'C';

  // Populate status dropdown
  const statusSelect = document.getElementById('task-detail-status');
  statusSelect.innerHTML = '';
  statuses.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.value;
    opt.textContent = s.label;
    if (task.status === s.value) opt.selected = true;
    statusSelect.appendChild(opt);
  });

  // Set default copy date to tomorrow
  const tomorrow = getNextDay(currentDate);
  document.getElementById('task-copy-date').value = formatPrettyDate(tomorrow);
  document.getElementById('task-copy-date').dataset.isoDate = tomorrow;

  document.getElementById('task-details-modal').classList.add('open');
}

function closeTaskDetails() {
  document.getElementById('task-details-modal').classList.remove('open');
  currentDetailTaskId = null;
}

function saveTaskDetails() {
  const taskId = document.getElementById('task-detail-id').value;
  if (!taskId) return;

  const fields = {
    text: document.getElementById('task-detail-text').value,
    notes: document.getElementById('task-detail-notes').value,
    priority: document.getElementById('task-detail-priority').value,
    status: document.getElementById('task-detail-status').value,
  };

  apiPost({ action: 'update_task', id: parseInt(taskId), fields }).then(data => {
    if (!data.success) {
      showToast('Error saving task', 'error');
      return;
    }
    tasks = data.tasks;
    renderTasks();
    closeTaskDetails();
  });
}

function copyTaskToNextDay(taskId) {
  const targetDate = getNextDay(currentDate);
  copyTaskToDate(taskId, targetDate);
}

function getNextDay(dateStr) {
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  date.setDate(date.getDate() + 1);
  return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
}

function getPrevDay(dateStr) {
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  date.setDate(date.getDate() - 1);
  return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
}

function copyTaskToDate(taskId, targetDate) {
  apiPost({ action: 'copy_task', id: parseInt(taskId), target_date: targetDate }).then(data => {
    if (!data.success) {
      showToast('Error copying task: ' + (data.error || 'Unknown'), 'error');
      return;
    }
    showToast('Task copied to ' + formatPrettyDate(targetDate), 'success');
  });
}

// Carry Forward functionality
let yesterdayUncompleted = [];

function checkForUncompletedTasks() {
  const yesterdayStr = getPrevDay(currentDate);

  apiPost({ action: 'get_uncompleted_tasks', date: yesterdayStr }).then(data => {
    if (data.success && data.uncompleted_tasks && data.uncompleted_tasks.length > 0) {
      // Filter out tasks that already exist in today's tasks (by text match)
      const todayTexts = new Set(tasks.map(t => t.text));
      const copyable = data.uncompleted_tasks.filter(t => !todayTexts.has(t.text));

      if (copyable.length > 0) {
        yesterdayUncompleted = copyable;
        showCarryForwardSection(yesterdayStr, copyable.length);
      } else {
        yesterdayUncompleted = [];
        hideCarryForwardSection();
      }
    } else {
      yesterdayUncompleted = [];
      hideCarryForwardSection();
    }
  });
}

function showCarryForwardSection(fromDate, count) {
  let section = document.getElementById('carry-forward-section');
  if (!section) {
    section = document.createElement('div');
    section.id = 'carry-forward-section';
    section.className = 'carry-forward-section';
    const form = document.getElementById('add-task-form');
    form.parentNode.insertBefore(section, form.nextSibling);
  }

  section.innerHTML = '';
  const text = document.createElement('span');
  text.textContent = count + ' uncompleted task' + (count !== 1 ? 's' : '') + ' from yesterday';
  const btn = document.createElement('button');
  btn.textContent = 'Carry Forward';
  btn.addEventListener('click', () => carryForwardTasks(fromDate));
  section.appendChild(text);
  section.appendChild(btn);
  section.style.display = 'flex';
}

function hideCarryForwardSection() {
  const section = document.getElementById('carry-forward-section');
  if (section) {
    section.style.display = 'none';
  }
}

function carryForwardTasks(fromDate) {
  apiPost({ action: 'carry_forward_tasks', from_date: fromDate, to_date: currentDate }).then(data => {
    if (!data.success) {
      showToast('Error: ' + (data.error || 'Unknown'), 'error');
      return;
    }
    tasks = data.tasks;
    renderTasks();
    hideCarryForwardSection();
    if (data.copied_count > 0) {
      showToast(data.copied_count + ' task' + (data.copied_count !== 1 ? 's' : '') + ' carried forward', 'success');
    }
    // If copied_count is 0, just hide silently - tasks already exist
  });
}

// Journal
function saveJournal() {
  const content = document.getElementById('journal-content').value;
  apiPost({ action: 'save_journal', date: currentDate, content }).then(data => {
    if (!data.success) {
      showToast('Error saving journal', 'error');
    }
  });
}

// Attachments
function renderAttachments() {
  const container = document.getElementById('attachments-list');
  container.innerHTML = '';

  if (attachments.length === 0) {
    return;
  }

  const header = document.createElement('div');
  header.className = 'attachments-header';
  header.textContent = 'Attachments (' + attachments.length + ')';
  container.appendChild(header);

  attachments.forEach(att => {
    const item = document.createElement('div');
    item.className = 'attachment-item';

    const preview = document.createElement('div');
    preview.className = 'attachment-preview';

    if (att.mime_type.startsWith('image/')) {
      const img = document.createElement('img');
      img.src = 'uploads/' + att.stored_path;
      img.alt = att.original_name;
      preview.appendChild(img);
    } else {
      const icon = document.createElement('span');
      icon.className = 'file-icon';
      icon.textContent = getFileIcon(att.mime_type);
      preview.appendChild(icon);
    }

    const info = document.createElement('div');
    info.className = 'attachment-info';

    const name = document.createElement('div');
    name.className = 'attachment-name';
    name.textContent = att.original_name;
    name.title = att.original_name;

    const meta = document.createElement('div');
    meta.className = 'attachment-meta';
    meta.textContent = formatFileSize(att.size_bytes);

    info.appendChild(name);
    info.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'attachment-actions';

    const viewBtn = document.createElement('button');
    viewBtn.textContent = 'View';
    viewBtn.addEventListener('click', () => {
      window.open('uploads/' + att.stored_path, '_blank');
    });

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'delete-btn';
    deleteBtn.textContent = 'X';
    deleteBtn.addEventListener('click', () => {
      if (confirm('Delete this attachment?')) {
        deleteAttachment(att.id);
      }
    });

    actions.appendChild(viewBtn);
    actions.appendChild(deleteBtn);

    item.appendChild(preview);
    item.appendChild(info);
    item.appendChild(actions);
    container.appendChild(item);
  });
}

function getFileIcon(mimeType) {
  if (mimeType.startsWith('image/')) return '🖼';
  if (mimeType === 'application/pdf') return '📄';
  if (mimeType.startsWith('text/')) return '📝';
  if (mimeType.includes('word')) return '📃';
  if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '📊';
  return '📎';
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function uploadFiles(files) {
  Array.from(files).forEach(file => {
    const formData = new FormData();
    formData.append('action', 'upload_attachment');
    formData.append('date', currentDate);
    formData.append('file', file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('api.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken },
      credentials: 'same-origin',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        showToast('Upload failed: ' + (data.error || 'Unknown error'), 'error');
      } else {
        attachments = data.attachments || [];
        renderAttachments();
      }
    })
    .catch(err => {
      showToast('Upload error: ' + err.message, 'error');
    });
  });
}

function deleteAttachment(id) {
  apiPost({ action: 'delete_attachment', id: id, date: currentDate }).then(data => {
    if (!data.success) {
      showToast('Error deleting attachment', 'error');
    } else {
      attachments = data.attachments || [];
      renderAttachments();
    }
  });
}

// Index entries
function loadIndexEntries() {
  apiPost({ action: 'get_index_entries', date: currentDate }).then(data => {
    if (data.success) {
      indexEntries = data.index_entries || [];
      renderIndexEntries();
    }
  });
}

function renderIndexEntries() {
  const container = document.getElementById('index-entries-list');
  container.innerHTML = '';

  indexEntries.forEach(entry => {
    const item = document.createElement('div');
    item.className = 'index-entry-item';

    const summary = document.createElement('span');
    summary.className = 'index-entry-summary';
    summary.textContent = entry.summary;

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'X';
    deleteBtn.addEventListener('click', () => {
      if (confirm('Remove from index?')) {
        deleteIndexEntry(entry.id);
      }
    });

    item.appendChild(summary);
    item.appendChild(deleteBtn);
    container.appendChild(item);
  });
}

function addIndexEntry(summary) {
  apiPost({ action: 'add_index_entry', date: currentDate, summary: summary }).then(data => {
    if (!data.success) {
      showToast('Error adding index entry', 'error');
    } else {
      indexEntries = data.index_entries || [];
      renderIndexEntries();
    }
  });
}

function deleteIndexEntry(id) {
  apiPost({ action: 'delete_index_entry', id: id, date: currentDate }).then(data => {
    if (!data.success) {
      showToast('Error deleting index entry', 'error');
    } else {
      indexEntries = data.index_entries || [];
      renderIndexEntries();
    }
  });
}

// Monthly index modal
function openMonthIndexModal() {
  const [y, m] = currentDate.split('-').map(Number);
  indexViewDate = new Date(y, m - 1, 1);
  loadMonthIndex();
  document.getElementById('month-index-modal').classList.add('open');
}

function closeMonthIndexModal() {
  document.getElementById('month-index-modal').classList.remove('open');
}

function loadMonthIndex() {
  const year = indexViewDate.getFullYear();
  const month = indexViewDate.getMonth() + 1;

  document.getElementById('index-month-year').textContent =
    indexViewDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

  apiPost({ action: 'get_month_index', year: year, month: month }).then(data => {
    if (data.success) {
      renderMonthIndex(data.index_entries || []);
    }
  });
}

function renderMonthIndex(entries) {
  const container = document.getElementById('month-index-list');
  container.innerHTML = '';

  if (entries.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'month-index-empty';
    empty.textContent = 'No index entries for this month.';
    container.appendChild(empty);
    return;
  }

  // Group by date
  const byDate = {};
  entries.forEach(e => {
    if (!byDate[e.entry_date]) byDate[e.entry_date] = [];
    byDate[e.entry_date].push(e);
  });

  Object.keys(byDate).sort().forEach(date => {
    const dayDiv = document.createElement('div');
    dayDiv.className = 'month-index-day';

    const dateDiv = document.createElement('div');
    dateDiv.className = 'month-index-date';
    dateDiv.textContent = formatPrettyDate(date);
    dateDiv.addEventListener('click', () => {
      closeMonthIndexModal();
      loadDay(date);
    });

    const entriesDiv = document.createElement('div');
    entriesDiv.className = 'month-index-entries';

    byDate[date].forEach(e => {
      const entryDiv = document.createElement('div');
      entryDiv.className = 'month-index-entry';
      entryDiv.textContent = '• ' + e.summary;
      entriesDiv.appendChild(entryDiv);
    });

    dayDiv.appendChild(dateDiv);
    dayDiv.appendChild(entriesDiv);
    container.appendChild(dayDiv);
  });
}

// Search modal
function openSearchModal() {
  document.getElementById('search-modal').classList.add('open');
  document.getElementById('search-query').focus();
}

function closeSearchModal() {
  document.getElementById('search-modal').classList.remove('open');
}

function performSearch() {
  const query = document.getElementById('search-query').value.trim();
  if (query.length < 2) {
    showToast('Please enter at least 2 characters', 'error');
    return;
  }

  apiPost({ action: 'search_journal', query: query }).then(data => {
    if (!data.success) {
      showToast('Search error: ' + (data.error || 'Unknown'), 'error');
      return;
    }
    renderSearchResults(data.results || []);
  });
}

function renderSearchResults(results) {
  const container = document.getElementById('search-results');
  container.innerHTML = '';

  if (results.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'search-no-results';
    empty.textContent = 'No results found.';
    container.appendChild(empty);
    return;
  }

  results.forEach(r => {
    const item = document.createElement('div');
    item.className = 'search-result-item';

    const dateDiv = document.createElement('div');
    dateDiv.className = 'search-result-date';
    dateDiv.textContent = formatPrettyDate(r.entry_date);

    const snippetDiv = document.createElement('div');
    snippetDiv.className = 'search-result-snippet';
    snippetDiv.textContent = r.snippet ? '...' + r.snippet + '...' : '(no preview)';

    item.appendChild(dateDiv);
    item.appendChild(snippetDiv);

    item.addEventListener('click', () => {
      closeSearchModal();
      loadDay(r.entry_date);
    });

    container.appendChild(item);
  });
}

// Hebrew calendar functions
function loadHebrewInfo() {
  apiPost({ action: 'get_hebrew_info', date: currentDate }).then(data => {
    if (data.success) {
      hebrewInfo = data.hebrew_info;
      renderHebrewInfo();
    }
  });
}

function renderHebrewInfo() {
  const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Shabbat'];

  // Display day of week
  const dayOfWeekEl = document.getElementById('day-of-week');
  if (hebrewInfo && hebrewInfo.day_of_week !== undefined) {
    dayOfWeekEl.textContent = dayNames[hebrewInfo.day_of_week];
  } else {
    // Fallback to calculating from currentDate
    const d = new Date(currentDate + 'T12:00:00');
    dayOfWeekEl.textContent = dayNames[d.getDay()];
  }

  // Display Hebrew date
  const hebrewDateEl = document.getElementById('hebrew-date');
  if (hebrewInfo && hebrewInfo.hebrew_date) {
    hebrewDateEl.textContent = hebrewInfo.hebrew_date;
  } else {
    hebrewDateEl.textContent = '';
  }

  // Display special day badges
  const badgesEl = document.getElementById('special-day-badges');
  badgesEl.innerHTML = '';

  if (!hebrewInfo) return;

  // Candle lighting badge (for Erev Shabbat or Erev Yom Tov)
  if (hebrewInfo.candle_lighting) {
    const badge = document.createElement('span');
    badge.className = 'special-badge badge-candles';
    badge.textContent = 'Candles: ' + hebrewInfo.candle_lighting;
    badge.title = 'Candle lighting time';
    badgesEl.appendChild(badge);
  }

  // Shabbat badge
  if (hebrewInfo.is_shabbat) {
    const badge = document.createElement('span');
    badge.className = 'special-badge badge-shabbat';
    badge.textContent = 'Shabbat';
    badgesEl.appendChild(badge);
  }

  // Yom Tov badge
  if (hebrewInfo.is_yom_tov) {
    const badge = document.createElement('span');
    badge.className = 'special-badge badge-yomtov';
    badge.textContent = 'Yom Tov';
    badgesEl.appendChild(badge);
  }

  // Havdalah badge (for Motzei Shabbat or Motzei Yom Tov)
  if (hebrewInfo.havdalah) {
    const badge = document.createElement('span');
    badge.className = 'special-badge badge-havdalah';
    badge.textContent = 'Ends: ' + hebrewInfo.havdalah;
    badge.title = 'Shabbat/Yom Tov ends';
    badgesEl.appendChild(badge);
  }

  // Fast day badge
  if (hebrewInfo.is_fast_day) {
    const badge = document.createElement('span');
    badge.className = 'special-badge badge-fast';
    badge.textContent = 'Fast Day';
    badgesEl.appendChild(badge);
  }

  // Holiday names
  if (hebrewInfo.holidays && hebrewInfo.holidays.length > 0) {
    hebrewInfo.holidays.forEach(h => {
      if (h.category !== 'parashat') { // Skip parsha, show others
        const badge = document.createElement('span');
        badge.className = 'special-badge badge-holiday';
        badge.textContent = h.title;
        badge.title = h.hebrew || h.title;
        badgesEl.appendChild(badge);
      }
    });
  }

  // Yartzheit badges
  if (hebrewInfo.yartzheits && hebrewInfo.yartzheits.length > 0) {
    hebrewInfo.yartzheits.forEach(y => {
      const badge = document.createElement('span');
      badge.className = 'special-badge badge-yartzheit';
      badge.textContent = 'Yartzheit: ' + y.name;
      if (y.relationship) badge.title = y.relationship;
      badgesEl.appendChild(badge);
    });
  }
}

// Zmanim modal
function openZmanimModal() {
  document.getElementById('zmanim-modal').classList.add('open');
  loadZmanim();
}

function closeZmanimModal() {
  document.getElementById('zmanim-modal').classList.remove('open');
}

function loadZmanim() {
  const grid = document.getElementById('zmanim-grid');
  const location = document.getElementById('zmanim-location');
  grid.innerHTML = '<div style="text-align:center;color:#888;">Loading...</div>';

  apiPost({ action: 'get_zmanim', date: currentDate }).then(data => {
    if (!data.success || data.zmanim.error) {
      grid.innerHTML = '<div style="color:#a33;">Could not load zmanim</div>';
      return;
    }

    const z = data.zmanim;
    location.textContent = z.location + ' - ' + formatPrettyDate(currentDate);

    const items = [
      ['Alot HaShachar', z.alotHaShachar],
      ['Misheyakir', z.misheyakir],
      ['Sunrise', z.sunrise],
      ['Sof Zman Shma', z.sofZmanShma],
      ['Sof Zman Tfilla', z.sofZmanTfilla],
      ['Chatzot', z.chatzot],
      ['Mincha Gedola', z.minchaGedola],
      ['Mincha Ketana', z.minchaKetana],
      ['Plag HaMincha', z.plagHaMincha],
      ['Sunset', z.sunset],
      ['Tzeit (42 min)', z.tzeit42min],
      ['Tzeit (72 min)', z.tzeit72min],
    ];

    grid.innerHTML = '';
    items.forEach(([label, time]) => {
      if (time) {
        const item = document.createElement('div');
        item.className = 'zmanim-item';
        item.innerHTML = '<span class="zmanim-label">' + label + '</span><span class="zmanim-time">' + time + '</span>';
        grid.appendChild(item);
      }
    });
  });
}

// Yartzheits modal
function openYartzheitsModal() {
  document.getElementById('yartzheits-modal').classList.add('open');
  loadAllYartzheits();
}

function closeYartzheitsModal() {
  document.getElementById('yartzheits-modal').classList.remove('open');
}

function loadAllYartzheits() {
  apiPost({ action: 'get_yartzheits' }).then(data => {
    if (data.success) {
      allYartzheits = data.yartzheits || [];
      renderYartzheitsList();
    }
  });
}

function renderYartzheitsList() {
  const container = document.getElementById('yartzheit-list');
  container.innerHTML = '';

  if (allYartzheits.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'yartzheit-empty';
    empty.textContent = 'No yartzheits recorded.';
    container.appendChild(empty);
    return;
  }

  allYartzheits.forEach(y => {
    const item = document.createElement('div');
    item.className = 'yartzheit-item';

    const info = document.createElement('div');
    info.className = 'yartzheit-info';

    const name = document.createElement('div');
    name.className = 'yartzheit-name';
    name.textContent = y.name;

    const date = document.createElement('div');
    date.className = 'yartzheit-date';
    date.textContent = y.hebrew_day + ' ' + y.hebrew_month_name;

    info.appendChild(name);
    info.appendChild(date);

    if (y.relationship) {
      const rel = document.createElement('div');
      rel.className = 'yartzheit-rel';
      rel.textContent = y.relationship;
      info.appendChild(rel);
    }

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.addEventListener('click', () => {
      if (confirm('Delete this yartzheit?')) {
        deleteYartzheit(y.id);
      }
    });

    item.appendChild(info);
    item.appendChild(deleteBtn);
    container.appendChild(item);
  });
}

function addYartzheit(name, hebrewMonth, hebrewDay, relationship) {
  apiPost({
    action: 'add_yartzheit',
    name: name,
    hebrew_month: hebrewMonth,
    hebrew_day: hebrewDay,
    relationship: relationship,
    notes: ''
  }).then(data => {
    if (!data.success) {
      showToast('Error adding yartzheit: ' + (data.error || 'Unknown'), 'error');
    } else {
      allYartzheits = data.yartzheits || [];
      renderYartzheitsList();
      // Refresh Hebrew info in case this yartzheit is today
      loadHebrewInfo();
    }
  });
}

function deleteYartzheit(id) {
  apiPost({ action: 'delete_yartzheit', id: id }).then(data => {
    if (!data.success) {
      showToast('Error deleting yartzheit', 'error');
    } else {
      allYartzheits = data.yartzheits || [];
      renderYartzheitsList();
      loadHebrewInfo();
    }
  });
}

// Recurring tasks modal
let allRecurringTasks = [];

function openRecurringModal() {
  document.getElementById('recurring-modal').classList.add('open');
  document.getElementById('recurring-anchor-date').value = currentDate;
  loadAllRecurringTasks();
}

function closeRecurringModal() {
  document.getElementById('recurring-modal').classList.remove('open');
}

function loadAllRecurringTasks() {
  apiPost({ action: 'get_recurring_tasks' }).then(data => {
    if (data.success) {
      allRecurringTasks = data.recurring_tasks || [];
      renderRecurringList();
    }
  });
}

function formatPatternDescription(rt) {
  const val = parseInt(rt.pattern_value);
  const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  switch (rt.pattern_type) {
    case 'day_of_month': return `${val}${ordinalSuffix(val)} of each month`;
    case 'day_of_week': return `Every ${weekdays[val]}`;
    case 'interval_days': return `Every ${val} day${val > 1 ? 's' : ''}`;
    case 'interval_weeks': return `Every ${val} week${val > 1 ? 's' : ''}`;
    case 'interval_months': return `Every ${val} month${val > 1 ? 's' : ''}`;
    default: return rt.pattern_type;
  }
}

function ordinalSuffix(n) {
  const s = ['th', 'st', 'nd', 'rd'];
  const v = n % 100;
  return (s[(v - 20) % 10] || s[v] || s[0]);
}

function renderRecurringList() {
  const container = document.getElementById('recurring-list');
  container.innerHTML = '';

  if (allRecurringTasks.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'recurring-empty';
    empty.textContent = 'No recurring tasks set up.';
    container.appendChild(empty);
    return;
  }

  allRecurringTasks.forEach(rt => {
    const item = document.createElement('div');
    item.className = 'recurring-item' + (rt.is_active == 0 ? ' inactive' : '');

    const info = document.createElement('div');
    info.className = 'recurring-info';

    const textEl = document.createElement('div');
    textEl.className = 'recurring-text';
    textEl.textContent = `[${rt.priority}] ${rt.text}`;
    info.appendChild(textEl);

    const patternEl = document.createElement('div');
    patternEl.className = 'recurring-pattern';
    patternEl.textContent = formatPatternDescription(rt);
    if (rt.end_date) {
      patternEl.textContent += ` (until ${rt.end_date})`;
    }
    info.appendChild(patternEl);

    const actions = document.createElement('div');
    actions.className = 'recurring-actions';

    const toggleBtn = document.createElement('button');
    toggleBtn.textContent = rt.is_active == 1 ? 'Pause' : 'Resume';
    toggleBtn.addEventListener('click', () => {
      toggleRecurringTask(rt.id, rt.is_active == 1 ? 0 : 1);
    });
    actions.appendChild(toggleBtn);

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.addEventListener('click', () => {
      if (confirm('Delete this recurring task?')) {
        deleteRecurringTask(rt.id);
      }
    });
    actions.appendChild(deleteBtn);

    item.appendChild(info);
    item.appendChild(actions);
    container.appendChild(item);
  });
}

function addRecurringTask(text, priority, patternType, patternValue, anchorDate, endDate) {
  apiPost({
    action: 'add_recurring_task',
    text: text,
    priority: priority,
    pattern_type: patternType,
    pattern_value: patternValue,
    anchor_date: anchorDate,
    end_date: endDate || null
  }).then(data => {
    if (!data.success) {
      showToast('Error adding recurring task: ' + (data.error || 'Unknown'), 'error');
    } else {
      allRecurringTasks = data.recurring_tasks || [];
      renderRecurringList();
    }
  });
}

function toggleRecurringTask(id, newActive) {
  apiPost({
    action: 'update_recurring_task',
    id: id,
    fields: { is_active: newActive }
  }).then(data => {
    if (data.success) {
      allRecurringTasks = data.recurring_tasks || [];
      renderRecurringList();
    }
  });
}

function deleteRecurringTask(id) {
  apiPost({ action: 'delete_recurring_task', id: id }).then(data => {
    if (!data.success) {
      showToast('Error deleting recurring task', 'error');
    } else {
      allRecurringTasks = data.recurring_tasks || [];
      renderRecurringList();
    }
  });
}

// Torah dropdown and modal
let torahCalendarData = null;

function loadTorahDropdown() {
  // Add preconnect hint to warm up connection to Sefaria
  if (!document.querySelector('link[href="https://www.sefaria.org"]')) {
    const preconnect = document.createElement('link');
    preconnect.rel = 'preconnect';
    preconnect.href = 'https://www.sefaria.org';
    document.head.appendChild(preconnect);
  }

  fetch('https://www.sefaria.org/api/calendars')
    .then(r => r.json())
    .then(data => {
      torahCalendarData = data.calendar_items;
      renderTorahDropdown(data.calendar_items);
    })
    .catch(err => {
      console.error('Failed to load Torah calendar:', err);
    });
}

function renderTorahDropdown(items) {
  const menu = document.getElementById('torah-dropdown-menu');
  // Define which items to show and in what order
  const showItems = [
    'Parashat Hashavua',
    'Haftarah',
    'Daf Yomi',
    'Daily Mishnah',
    'Daily Rambam',
    'Daily Rambam (3 Chapters)',
    '929',
    'Daf a Week',
    'Tanakh Yomi',
    'Tanya Yomi',
    'Yerushalmi Yomi',
    'Chok LeYisrael'
  ];

  let html = '';
  showItems.forEach(title => {
    const item = items.find(i => i.title.en === title);
    if (item) {
      const displayVal = item.displayValue?.en || item.ref || '';
      html += `<div class="torah-dropdown-item" data-title="${title}">
        <div class="torah-dropdown-item-title">${title}</div>
        <div class="torah-dropdown-item-ref">${displayVal}</div>
      </div>`;
    }
  });
  menu.innerHTML = html;

  // Add click handlers
  menu.querySelectorAll('.torah-dropdown-item').forEach(el => {
    el.addEventListener('click', () => {
      const title = el.dataset.title;
      const item = torahCalendarData.find(i => i.title.en === title);
      if (item) {
        openTorahModal(item);
      }
      closeTorahDropdown();
    });
  });
}

function toggleTorahDropdown() {
  const menu = document.getElementById('torah-dropdown-menu');
  menu.classList.toggle('open');
}

function closeTorahDropdown() {
  document.getElementById('torah-dropdown-menu').classList.remove('open');
}

function openTorahModal(item) {
  document.getElementById('torah-modal').classList.add('open');
  renderTorahContent(item);
}

function closeTorahModal() {
  document.getElementById('torah-modal').classList.remove('open');
}

function renderTorahContent(item) {
  const content = document.getElementById('torah-content');
  const title = item.title.en;
  document.getElementById('torah-modal-title').textContent = '📖 ' + title;

  // Special handling for Parashat Hashavua
  if (title === 'Parashat Hashavua') {
    renderParshaContent(item);
    return;
  }

  // Generic rendering for other items
  const displayVal = item.displayValue?.en || '';
  const displayHe = item.displayValue?.he || '';
  const ref = item.ref || '';
  const sefariaUrl = ref ? 'https://www.sefaria.org/' + ref.replace(/ /g, '_') + '?lang=bi' : '';

  let html = `
    <div class="parsha-header">
      <div class="parsha-name">${displayVal}</div>
      ${displayHe ? `<div class="parsha-name-hebrew">${displayHe}</div>` : ''}
      ${ref ? `<div class="parsha-ref">${ref}</div>` : ''}
    </div>
  `;

  if (sefariaUrl) {
    html += `
      <div class="parsha-section">
        <a href="${sefariaUrl}" target="_blank" class="parsha-aliyah-link">
          Read on Sefaria →
        </a>
      </div>
    `;
  }

  // Add description if available
  if (item.description?.en) {
    html += `
      <div class="parsha-section">
        <div style="font-size:var(--font-size-small); color:var(--text-secondary);">
          ${item.description.en}
        </div>
      </div>
    `;
  }

  content.innerHTML = html;
}

function prefetchTodayAliyah(parsha) {
  const aliyot = parsha.extraDetails?.aliyot || [];
  const dayOfWeek = new Date().getDay();
  if (aliyot.length > dayOfWeek) {
    const todayAliyah = aliyot[dayOfWeek];
    const sefariaUrl = 'https://www.sefaria.org/' + todayAliyah.replace(/ /g, '_') + '?lang=bi&with=Targum%20Onkelos&lang2=en';

    // Add prefetch hint for the actual page
    const prefetch = document.createElement('link');
    prefetch.rel = 'prefetch';
    prefetch.href = sefariaUrl;
    document.head.appendChild(prefetch);
  }
}

function renderParshaContent(parsha) {
  const content = document.getElementById('torah-content');
  const aliyot = parsha.extraDetails?.aliyot || [];
  prefetchTodayAliyah(parsha);

  // Determine today's aliyah (Sunday=0 -> aliyah 1, Saturday=6 -> aliyah 7)
  const today = new Date();
  const dayOfWeek = today.getDay(); // 0=Sunday, 6=Saturday
  const todayAliyahIndex = dayOfWeek; // 0-6 maps to aliyot 1-7
  const aliyahNames = ['Rishon', 'Sheni', 'Shlishi', 'Revi\'i', 'Chamishi', 'Shishi', 'Shevi\'i', 'Maftir'];

  // Parse the parsha name and reference
  const parshaName = parsha.displayValue.en || parsha.title.en;
  const parshaNameHebrew = parsha.displayValue.he || parsha.title.he;
  const parshaRef = parsha.ref || '';

  let html = `
    <div class="parsha-header">
      <div class="parsha-name">${parshaName}</div>
      <div class="parsha-name-hebrew">${parshaNameHebrew}</div>
      <div class="parsha-ref">${parshaRef}</div>
    </div>
  `;

  // Today's Aliyah section
  if (aliyot.length > todayAliyahIndex) {
    const todayAliyah = aliyot[todayAliyahIndex];
    const aliyahRef = todayAliyah.replace(/\./g, ' ').replace(/-/g, ' - ');
    const sefariaUrl = 'https://www.sefaria.org/' + todayAliyah.replace(/ /g, '_') + '?lang=bi&with=Targum%20Onkelos&lang2=en';

    html += `
      <div class="parsha-section">
        <div class="parsha-section-title">
          📖 Today's Reading (Ma'avir Sedra)
          <span class="parsha-day-indicator">${aliyahNames[todayAliyahIndex]}</span>
        </div>
        <div class="parsha-aliyah">
          <div class="parsha-aliyah-header">
            <span class="parsha-aliyah-num">Aliyah ${todayAliyahIndex + 1}</span>
            <span class="parsha-aliyah-ref">${aliyahRef}</span>
          </div>
          <a href="${sefariaUrl}" target="_blank" class="parsha-aliyah-link">
            Read on Sefaria (Hebrew + Targum + English) →
          </a>
        </div>
      </div>
    `;
  }

  // Quick Links section
  const parshaSlug = parshaName.replace('Parashat ', '').replace(/ /g, '_');
  html += `
    <div class="parsha-section">
      <div class="parsha-section-title">🔗 Quick Links</div>
      <div class="parsha-links">
        <a href="https://www.sefaria.org/${parshaSlug}?lang=bi" target="_blank" class="parsha-link">
          <span class="parsha-link-icon">📜</span>
          <span class="parsha-link-text">Full Parsha on Sefaria</span>
          <span class="parsha-link-arrow">→</span>
        </a>
        <a href="https://www.sefaria.org/topics/parashat-${parshaSlug.toLowerCase()}" target="_blank" class="parsha-link">
          <span class="parsha-link-icon">💬</span>
          <span class="parsha-link-text">Divrei Torah & Sheets</span>
          <span class="parsha-link-arrow">→</span>
        </a>
        <a href="https://www.sefaria.org/${parshaRef.replace(/ /g, '_').replace(/:/g, '.')}?lang=bi&with=all" target="_blank" class="parsha-link">
          <span class="parsha-link-icon">📚</span>
          <span class="parsha-link-text">Commentaries (Rashi, etc.)</span>
          <span class="parsha-link-arrow">→</span>
        </a>
      </div>
    </div>
  `;

  // All Aliyot section (collapsed by default)
  if (aliyot.length > 0) {
    html += `
      <div class="parsha-section">
        <div class="parsha-section-title">📋 All Aliyot</div>
        <div style="font-size:var(--font-size-small); color:var(--text-secondary);">
    `;
    aliyot.forEach((aliyah, idx) => {
      const isToday = idx === todayAliyahIndex;
      const style = isToday ? 'font-weight:600; color:var(--accent-primary);' : '';
      const ref = aliyah.replace(/\./g, ' ').replace(/-/g, ' - ');
      html += `<div style="${style}">${idx + 1}. ${aliyahNames[idx]}: ${ref}${isToday ? ' ← Today' : ''}</div>`;
    });
    html += '</div></div>';
  }

  content.innerHTML = html;
}

// Settings modal
function openSettingsModal() {
  document.getElementById('settings-modal').classList.add('open');
  loadSettings();
  // Initialize theme controls
  const savedPreset = localStorage.getItem('planner_theme_preset') || 'Dark (Default)';
  renderThemePresets(savedPreset);
  updateThemeControls(currentTheme);
}

function closeSettingsModal() {
  document.getElementById('settings-modal').classList.remove('open');
}

function loadSettings() {
  apiPost({ action: 'get_settings' }).then(data => {
    if (data.success) {
      appSettings = data.settings || {};
      populateSettingsForm();
    }
  });
}

function populateSettingsForm() {
  document.getElementById('setting-location').value = appSettings.location_name || '';
  document.getElementById('setting-latitude').value = appSettings.latitude || '';
  document.getElementById('setting-longitude').value = appSettings.longitude || '';
  document.getElementById('setting-timezone').value = appSettings.timezone || 'Asia/Jerusalem';
  document.getElementById('setting-elevation').value = appSettings.elevation || '';
  document.getElementById('setting-hide-done').checked = localStorage.getItem('planner_hide_done') === 'true';
  document.getElementById('setting-done-section').checked = localStorage.getItem('planner_done_section') === 'true';

  // Populate shortcuts
  populateShortcutInputs();
  initShortcutRecording();

  // Populate calendar list
  const calendarList = document.getElementById('calendar-list');
  calendarList.innerHTML = '';
  let calendars = [];
  try {
    calendars = JSON.parse(appSettings.calendar_feeds || '[]');
  } catch (e) {
    // Migration: convert old single URL to new format
    if (appSettings.calendar_ics_url) {
      calendars = [{ name: 'Calendar', url: appSettings.calendar_ics_url }];
    }
  }
  calendars.forEach((cal, index) => addCalendarEntry(cal.name, cal.url));
}

function addCalendarEntry(name = '', url = '') {
  const calendarList = document.getElementById('calendar-list');
  const entry = document.createElement('div');
  entry.className = 'calendar-entry';
  entry.innerHTML = `
    <input type="text" class="calendar-name" placeholder="Name" value="${escapeHtml(name)}">
    <input type="url" class="calendar-url" placeholder="https://calendar.google.com/calendar/ical/..." value="${escapeHtml(url)}">
    <button type="button" class="btn-remove-calendar">Remove</button>
  `;
  entry.querySelector('.btn-remove-calendar').addEventListener('click', () => entry.remove());
  calendarList.appendChild(entry);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function getCalendarFeeds() {
  const entries = document.querySelectorAll('.calendar-entry');
  const feeds = [];
  entries.forEach(entry => {
    const name = entry.querySelector('.calendar-name').value.trim();
    const url = entry.querySelector('.calendar-url').value.trim();
    if (url) {
      feeds.push({ name: name || 'Calendar', url });
    }
  });
  return feeds;
}

function saveSettings() {
  const settings = {
    location_name: document.getElementById('setting-location').value.trim(),
    latitude: document.getElementById('setting-latitude').value.trim(),
    longitude: document.getElementById('setting-longitude').value.trim(),
    timezone: document.getElementById('setting-timezone').value,
    elevation: document.getElementById('setting-elevation').value.trim(),
    calendar_feeds: JSON.stringify(getCalendarFeeds()),
  };

  // Save display settings to localStorage
  const hideDone = document.getElementById('setting-hide-done').checked;
  const doneSection = document.getElementById('setting-done-section').checked;
  localStorage.setItem('planner_hide_done', hideDone);
  localStorage.setItem('planner_done_section', doneSection);

  // Save keyboard shortcuts to localStorage
  saveShortcuts();

  apiPost({ action: 'save_settings', settings: settings }).then(data => {
    if (!data.success) {
      showToast('Error saving settings', 'error');
    } else {
      appSettings = data.settings || {};
      closeSettingsModal();
      // Refresh zmanim if modal was open
      loadHebrewInfo();
      // Re-render tasks to apply hide-done setting
      renderTasks();
    }
  });
}

// Pomodoro timer
let pomodoroState = {
  taskId: null,
  taskName: '',
  endTime: null,
  remainingMs: null,
  isPaused: false,
  intervalId: null,
  audioContext: null
};

function startPomodoro(taskId, taskText) {
  // Stop any existing timer
  if (pomodoroState.intervalId) {
    clearInterval(pomodoroState.intervalId);
  }

  // Remove active class from any previous task
  document.querySelectorAll('.task-item.pomodoro-active').forEach(el => el.classList.remove('pomodoro-active'));
  document.querySelectorAll('.pomodoro-btn.active').forEach(el => el.classList.remove('active'));

  // Set up new timer (25 minutes)
  const durationMs = 25 * 60 * 1000;
  pomodoroState = {
    taskId: taskId,
    taskName: taskText,
    endTime: Date.now() + durationMs,
    remainingMs: durationMs,
    isPaused: false,
    intervalId: null,
    audioContext: null
  };

  // Mark task as active
  const taskEl = document.querySelector(`.task-item[data-task-id="${taskId}"]`);
  if (taskEl) {
    taskEl.classList.add('pomodoro-active');
    const btn = taskEl.querySelector('.pomodoro-btn');
    if (btn) btn.classList.add('active');
  }

  // Show timer display
  const timerEl = document.getElementById('pomodoro-timer');
  timerEl.classList.add('active');
  document.getElementById('pomodoro-task-name').textContent = taskText.substring(0, 30) + (taskText.length > 30 ? '...' : '');
  document.getElementById('pomodoro-pause').textContent = 'Pause';

  // Request notification permission
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  // Start the countdown
  updatePomodoroDisplay();
  pomodoroState.intervalId = setInterval(updatePomodoroDisplay, 1000);
}

function updatePomodoroDisplay() {
  if (pomodoroState.isPaused) return;

  const now = Date.now();
  const remaining = pomodoroState.endTime - now;

  if (remaining <= 0) {
    // Timer complete!
    completePomodoroTimer();
    return;
  }

  const minutes = Math.floor(remaining / 60000);
  const seconds = Math.floor((remaining % 60000) / 1000);
  document.getElementById('pomodoro-time').textContent =
    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

  // Update tab title
  document.title = `${minutes}:${String(seconds).padStart(2, '0')} - Planner`;
}

function completePomodoroTimer() {
  clearInterval(pomodoroState.intervalId);

  // Play alarm sound
  playPomodoroAlarm();

  // Show notification
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification('Pomodoro Complete!', {
      body: `Time's up! Task: ${pomodoroState.taskName}`,
      icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">🍅</text></svg>'
    });
  }

  // Flash the tab title
  let flashCount = 0;
  const originalTitle = 'Planner';
  const flashInterval = setInterval(() => {
    document.title = flashCount % 2 === 0 ? '🍅 Time\'s up!' : originalTitle;
    flashCount++;
    if (flashCount > 10) {
      clearInterval(flashInterval);
      document.title = originalTitle;
    }
  }, 500);

  // Update display to show 00:00
  document.getElementById('pomodoro-time').textContent = '00:00';

  // Show toast
  showToast('🍅 Pomodoro complete! Take a break.', 'success');

  // Keep timer visible for a few seconds, then hide
  setTimeout(() => {
    stopPomodoro();
  }, 5000);
}

function playPomodoroAlarm() {
  try {
    // Create audio context for alarm sound
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    const ctx = new AudioContext();

    // Play a pleasant alarm sound (ascending tones)
    const playTone = (freq, startTime, duration) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.frequency.value = freq;
      osc.type = 'sine';
      gain.gain.setValueAtTime(0.3, startTime);
      gain.gain.exponentialRampToValueAtTime(0.01, startTime + duration);
      osc.start(startTime);
      osc.stop(startTime + duration);
    };

    // Play a short melody
    const now = ctx.currentTime;
    playTone(523.25, now, 0.2);       // C5
    playTone(659.25, now + 0.2, 0.2); // E5
    playTone(783.99, now + 0.4, 0.2); // G5
    playTone(1046.5, now + 0.6, 0.4); // C6

    // Repeat the melody
    playTone(523.25, now + 1.2, 0.2);
    playTone(659.25, now + 1.4, 0.2);
    playTone(783.99, now + 1.6, 0.2);
    playTone(1046.5, now + 1.8, 0.4);

    pomodoroState.audioContext = ctx;
  } catch (e) {
    console.log('Audio not available:', e);
  }
}

function pausePomodoro() {
  if (!pomodoroState.intervalId) return;

  const btn = document.getElementById('pomodoro-pause');

  if (pomodoroState.isPaused) {
    // Resume
    pomodoroState.isPaused = false;
    pomodoroState.endTime = Date.now() + pomodoroState.remainingMs;
    btn.textContent = 'Pause';
  } else {
    // Pause
    pomodoroState.isPaused = true;
    pomodoroState.remainingMs = pomodoroState.endTime - Date.now();
    btn.textContent = 'Resume';
  }
}

function stopPomodoro() {
  if (pomodoroState.intervalId) {
    clearInterval(pomodoroState.intervalId);
  }

  // Clean up audio context
  if (pomodoroState.audioContext) {
    pomodoroState.audioContext.close();
  }

  // Remove active class from task
  document.querySelectorAll('.task-item.pomodoro-active').forEach(el => el.classList.remove('pomodoro-active'));
  document.querySelectorAll('.pomodoro-btn.active').forEach(el => el.classList.remove('active'));

  // Hide timer display
  document.getElementById('pomodoro-timer').classList.remove('active');
  document.getElementById('pomodoro-time').textContent = '25:00';
  document.getElementById('pomodoro-task-name').textContent = '';

  // Reset title
  document.title = 'Planner';

  // Reset state
  pomodoroState = {
    taskId: null,
    taskName: '',
    endTime: null,
    remainingMs: null,
    isPaused: false,
    intervalId: null,
    audioContext: null
  };
}

// Pomodoro control event listeners
document.getElementById('pomodoro-pause').addEventListener('click', pausePomodoro);
document.getElementById('pomodoro-stop').addEventListener('click', stopPomodoro);

// Event wiring
document.getElementById('add-task-form').addEventListener('submit', e => {
  e.preventDefault();
  const textInput = document.getElementById('new-task-text');
  const priSelect = document.getElementById('new-task-priority');
  const text = textInput.value.trim();
  if (!text) return;
  addTask(text, priSelect.value);
  textInput.value = '';
  textInput.focus();
});

document.getElementById('save-journal').addEventListener('click', e => {
  e.preventDefault();
  saveJournal();
});

// Journal Ctrl+Enter is handled by global keyboard shortcuts system

// Journal search
function searchJournal() {
  const query = document.getElementById('journal-search-input').value.trim();
  if (query.length < 2) {
    showToast('Search query must be at least 2 characters', 'error');
    return;
  }

  apiPost({ action: 'search_journal', query }).then(data => {
    if (!data.success) {
      showToast(data.error || 'Search failed', 'error');
      return;
    }

    const resultsDiv = document.getElementById('journal-search-results');
    if (data.results.length === 0) {
      resultsDiv.innerHTML = '<div class="search-no-results">No results found</div>';
    } else {
      resultsDiv.innerHTML = data.results.map(r => {
        const date = new Date(r.date + 'T00:00:00');
        const dateStr = date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
        // Highlight search term in snippet
        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const highlightedSnippet = r.snippet.replace(new RegExp('(' + escapedQuery + ')', 'gi'), '<mark>$1</mark>');
        return `<div class="search-result" data-date="${r.date}">
          <div class="search-result-date">${dateStr}</div>
          <div class="search-result-snippet">${highlightedSnippet}</div>
        </div>`;
      }).join('');

      // Add click handlers to navigate to date
      resultsDiv.querySelectorAll('.search-result').forEach(el => {
        el.addEventListener('click', () => {
          loadDay(el.dataset.date);
          closeJournalSearchModal();
        });
      });
    }

    document.getElementById('journal-search-modal').classList.add('open');
  });
}

function closeJournalSearchModal() {
  document.getElementById('journal-search-modal').classList.remove('open');
}

document.getElementById('journal-search-btn').addEventListener('click', searchJournal);
document.getElementById('journal-search-input').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    searchJournal();
  }
});
document.getElementById('journal-search-modal-close').addEventListener('click', closeJournalSearchModal);
document.getElementById('journal-search-modal').addEventListener('click', e => {
  if (e.target.id === 'journal-search-modal') closeJournalSearchModal();
});

// Dropzone events
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');

dropzone.addEventListener('click', () => {
  fileInput.click();
});

fileInput.addEventListener('change', (e) => {
  if (e.target.files.length > 0) {
    uploadFiles(e.target.files);
    e.target.value = ''; // Reset input
  }
});

dropzone.addEventListener('dragover', (e) => {
  e.preventDefault();
  e.stopPropagation();
  dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', (e) => {
  e.preventDefault();
  e.stopPropagation();
  dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  e.stopPropagation();
  dropzone.classList.remove('dragover');

  const files = e.dataTransfer.files;
  if (files.length > 0) {
    uploadFiles(files);
  }
});

// Index entry form
document.getElementById('add-index-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const input = document.getElementById('new-index-summary');
  const summary = input.value.trim();
  if (summary) {
    addIndexEntry(summary);
    input.value = '';
  }
});

// Search modal events
document.getElementById('search-btn').addEventListener('click', openSearchModal);
document.getElementById('search-modal-close').addEventListener('click', closeSearchModal);
document.getElementById('search-go').addEventListener('click', performSearch);
document.getElementById('search-query').addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    performSearch();
  }
});
document.getElementById('search-modal').addEventListener('click', (e) => {
  if (e.target.id === 'search-modal') closeSearchModal();
});

// Monthly index modal events
document.getElementById('month-index-btn').addEventListener('click', openMonthIndexModal);
document.getElementById('month-index-modal-close').addEventListener('click', closeMonthIndexModal);
document.getElementById('month-index-modal').addEventListener('click', (e) => {
  if (e.target.id === 'month-index-modal') closeMonthIndexModal();
});

document.getElementById('index-prev-year').addEventListener('click', () => {
  indexViewDate.setFullYear(indexViewDate.getFullYear() - 1);
  loadMonthIndex();
});
document.getElementById('index-next-year').addEventListener('click', () => {
  indexViewDate.setFullYear(indexViewDate.getFullYear() + 1);
  loadMonthIndex();
});
document.getElementById('index-prev-month').addEventListener('click', () => {
  indexViewDate.setMonth(indexViewDate.getMonth() - 1);
  loadMonthIndex();
});
document.getElementById('index-next-month').addEventListener('click', () => {
  indexViewDate.setMonth(indexViewDate.getMonth() + 1);
  loadMonthIndex();
});

// Zmanim modal events
document.getElementById('zmanim-btn').addEventListener('click', openZmanimModal);
document.getElementById('zmanim-modal-close').addEventListener('click', closeZmanimModal);
document.getElementById('zmanim-modal').addEventListener('click', (e) => {
  if (e.target.id === 'zmanim-modal') closeZmanimModal();
});

// Yartzheits modal events
document.getElementById('yartzheits-btn').addEventListener('click', openYartzheitsModal);
document.getElementById('yartzheits-modal-close').addEventListener('click', closeYartzheitsModal);
document.getElementById('yartzheits-modal').addEventListener('click', (e) => {
  if (e.target.id === 'yartzheits-modal') closeYartzheitsModal();
});

document.getElementById('yartzheit-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const name = document.getElementById('yartzheit-name').value.trim();
  const month = parseInt(document.getElementById('yartzheit-month').value);
  const day = parseInt(document.getElementById('yartzheit-day').value);
  const relationship = document.getElementById('yartzheit-relationship').value.trim();

  if (name && month && day) {
    addYartzheit(name, month, day, relationship);
    document.getElementById('yartzheit-name').value = '';
    document.getElementById('yartzheit-month').value = '';
    document.getElementById('yartzheit-day').value = '';
    document.getElementById('yartzheit-relationship').value = '';
  }
});

// Recurring tasks modal events
document.getElementById('recurring-btn').addEventListener('click', openRecurringModal);
document.getElementById('recurring-modal-close').addEventListener('click', closeRecurringModal);
document.getElementById('recurring-modal').addEventListener('click', (e) => {
  if (e.target.id === 'recurring-modal') closeRecurringModal();
});

document.getElementById('recurring-pattern-type').addEventListener('change', (e) => {
  const type = e.target.value;
  const valueInput = document.getElementById('recurring-pattern-value');
  const weekdaySelect = document.getElementById('recurring-weekday');

  if (type === 'day_of_week') {
    valueInput.style.display = 'none';
    weekdaySelect.style.display = 'block';
  } else {
    valueInput.style.display = 'block';
    weekdaySelect.style.display = 'none';
    if (type === 'day_of_month') {
      valueInput.placeholder = 'Day (1-31)';
      valueInput.max = 31;
    } else {
      valueInput.placeholder = 'Interval';
      valueInput.max = 365;
    }
  }
});

document.getElementById('recurring-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const text = document.getElementById('recurring-text').value.trim();
  const priority = document.getElementById('recurring-priority').value;
  const patternType = document.getElementById('recurring-pattern-type').value;
  const anchorDate = document.getElementById('recurring-anchor-date').value;
  const endDate = document.getElementById('recurring-end-date').value;

  let patternValue;
  if (patternType === 'day_of_week') {
    patternValue = parseInt(document.getElementById('recurring-weekday').value);
  } else {
    patternValue = parseInt(document.getElementById('recurring-pattern-value').value);
  }

  if (text && patternType && patternValue && anchorDate) {
    addRecurringTask(text, priority, patternType, patternValue, anchorDate, endDate);
    document.getElementById('recurring-text').value = '';
    document.getElementById('recurring-pattern-type').value = '';
    document.getElementById('recurring-pattern-value').value = '';
  }
});

// Torah dropdown and modal events
document.getElementById('torah-btn').addEventListener('click', (e) => {
  e.stopPropagation();
  toggleTorahDropdown();
});
document.getElementById('torah-modal-close').addEventListener('click', closeTorahModal);
document.getElementById('torah-modal').addEventListener('click', (e) => {
  if (e.target.id === 'torah-modal') closeTorahModal();
});
document.addEventListener('click', (e) => {
  if (!document.getElementById('torah-dropdown').contains(e.target)) {
    closeTorahDropdown();
  }
});

// Load Torah dropdown on page load
loadTorahDropdown();

// Settings modal events
document.getElementById('settings-btn').addEventListener('click', () => {
  closeUserDropdown();
  openSettingsModal();
});
document.getElementById('settings-modal-close').addEventListener('click', closeSettingsModal);
document.getElementById('settings-cancel').addEventListener('click', closeSettingsModal);
document.getElementById('settings-modal').addEventListener('click', (e) => {
  if (e.target.id === 'settings-modal') closeSettingsModal();
});
document.getElementById('settings-form').addEventListener('submit', (e) => {
  e.preventDefault();
  saveSettings();
});
document.getElementById('add-calendar-btn').addEventListener('click', () => {
  addCalendarEntry();
});
document.getElementById('reset-shortcuts-btn').addEventListener('click', () => {
  resetShortcuts();
});

// Change password modal events
function openChangePasswordModal() {
  document.getElementById('change-password-form').reset();
  document.getElementById('change-password-modal').classList.add('open');
  document.getElementById('current-password').focus();
}
function closeChangePasswordModal() {
  document.getElementById('change-password-modal').classList.remove('open');
}

document.getElementById('change-password-btn').addEventListener('click', () => {
  closeUserDropdown();
  openChangePasswordModal();
});
document.getElementById('change-password-modal-close').addEventListener('click', closeChangePasswordModal);
document.getElementById('change-password-cancel').addEventListener('click', closeChangePasswordModal);
document.getElementById('change-password-modal').addEventListener('click', (e) => {
  if (e.target.id === 'change-password-modal') closeChangePasswordModal();
});
document.getElementById('change-password-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const currentPassword = document.getElementById('current-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;

  if (newPassword !== confirmPassword) {
    showToast('New passwords do not match', 'error');
    return;
  }
  if (newPassword.length < 8) {
    showToast('Password must be at least 8 characters', 'error');
    return;
  }

  apiPost({
    action: 'change_password',
    current_password: currentPassword,
    new_password: newPassword
  }).then(data => {
    if (data.success) {
      showToast('Password changed successfully', 'success');
      closeChangePasswordModal();
    } else {
      showToast(data.error || 'Failed to change password', 'error');
    }
  });
});

document.getElementById('export-btn').addEventListener('click', () => {
  closeUserDropdown();
  apiPost({ action: 'export_all' }).then(data => {
    if (!data.success) {
      showToast('Export failed', 'error');
      return;
    }
    const blob = new Blob([JSON.stringify(data.export, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'planner-backup-' + new Date().toISOString().slice(0, 10) + '.json';
    a.click();
    URL.revokeObjectURL(url);
  });
});

// User menu events
function toggleUserDropdown() {
  document.getElementById('user-dropdown').classList.toggle('open');
}
function closeUserDropdown() {
  document.getElementById('user-dropdown').classList.remove('open');
}

document.getElementById('user-menu-btn').addEventListener('click', (e) => {
  e.stopPropagation();
  toggleUserDropdown();
});

document.addEventListener('click', (e) => {
  if (!document.getElementById('user-menu').contains(e.target)) {
    closeUserDropdown();
  }
});

document.getElementById('profile-photo-btn').addEventListener('click', () => {
  document.getElementById('profile-photo-input').click();
});

document.getElementById('profile-photo-input').addEventListener('change', (e) => {
  if (e.target.files.length === 0) return;
  const file = e.target.files[0];
  const formData = new FormData();
  formData.append('action', 'upload_profile_photo');
  formData.append('file', file);

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  fetch('api.php', {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    credentials: 'same-origin',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Update the avatar image
      const btn = document.getElementById('user-menu-btn');
      btn.innerHTML = '<img src="' + data.path + '?t=' + Date.now() + '" alt="Profile" class="user-avatar">';
      closeUserDropdown();
    } else {
      showToast('Upload failed: ' + (data.error || 'Unknown error'), 'error');
    }
  })
  .catch(err => showToast('Upload error: ' + err.message, 'error'));

  e.target.value = ''; // Reset input
});

// Theme control events
document.getElementById('theme-font-family').addEventListener('change', onThemeCustomChange);
document.getElementById('theme-font-size').addEventListener('input', (e) => {
  document.getElementById('font-size-display').textContent = e.target.value + 'px';
  onThemeCustomChange();
});
document.getElementById('theme-bg-primary').addEventListener('input', onThemeCustomChange);
document.getElementById('theme-bg-secondary').addEventListener('input', onThemeCustomChange);
document.getElementById('theme-text-primary').addEventListener('input', onThemeCustomChange);
document.getElementById('theme-accent-primary').addEventListener('input', onThemeCustomChange);

// Task details modal events
document.getElementById('task-details-modal-close').addEventListener('click', closeTaskDetails);
document.getElementById('task-details-cancel').addEventListener('click', closeTaskDetails);
document.getElementById('task-details-modal').addEventListener('click', (e) => {
  if (e.target.id === 'task-details-modal') closeTaskDetails();
});
document.getElementById('task-details-form').addEventListener('submit', (e) => {
  e.preventDefault();
  saveTaskDetails();
});
document.getElementById('task-copy-btn').addEventListener('click', () => {
  const input = document.getElementById('task-copy-date');
  const taskId = document.getElementById('task-detail-id').value;
  // Try to get ISO date from dataset, or parse from input
  let targetDate = input.dataset.isoDate;
  if (!targetDate) {
    targetDate = parseDateInput(input.value);
  }
  if (targetDate && taskId) {
    copyTaskToDate(parseInt(taskId), targetDate);
  } else {
    showToast('Invalid date format. Use YYYY-MM-DD or YYYY-Month-DD', 'error');
  }
});

document.getElementById('task-copy-date').addEventListener('input', (e) => {
  const parsed = parseDateInput(e.target.value);
  if (parsed) {
    e.target.dataset.isoDate = parsed;
  } else {
    delete e.target.dataset.isoDate;
  }
});

function parseDateInput(input) {
  if (!input) return null;
  const trimmed = input.trim();

  // ISO format: YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    return trimmed;
  }

  // Format: YYYY-Month-DD (e.g., 2024-December-02)
  const monthNames = ['january','february','march','april','may','june','july','august','september','october','november','december'];
  const match = trimmed.match(/^(\d{4})-([A-Za-z]+)-(\d{2})$/);
  if (match) {
    const [, year, month, day] = match;
    const monthIdx = monthNames.indexOf(month.toLowerCase());
    if (monthIdx >= 0) {
      return year + '-' + String(monthIdx + 1).padStart(2, '0') + '-' + day;
    }
  }

  return null;
}

// Calendar functions
function openCalendar() {
  const dropdown = document.getElementById('calendar-dropdown');
  // Set calendar view to current selected date
  const [y, m, d] = currentDate.split('-').map(Number);
  calendarViewDate = new Date(y, m - 1, 1);
  renderCalendar();
  document.getElementById('date-input').value = currentDate;
  dropdown.classList.add('open');
}

function closeCalendar() {
  document.getElementById('calendar-dropdown').classList.remove('open');
}

function toggleCalendar() {
  const dropdown = document.getElementById('calendar-dropdown');
  if (dropdown.classList.contains('open')) {
    closeCalendar();
  } else {
    openCalendar();
  }
}

function renderCalendar() {
  const grid = document.getElementById('calendar-grid');
  const monthYearLabel = document.getElementById('cal-month-year');

  const year = calendarViewDate.getFullYear();
  const month = calendarViewDate.getMonth();

  monthYearLabel.textContent = calendarViewDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

  grid.innerHTML = '';

  // Day headers
  const dayNames = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
  dayNames.forEach(name => {
    const header = document.createElement('div');
    header.className = 'day-header';
    header.textContent = name;
    grid.appendChild(header);
  });

  // First day of the month and how many days
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();

  // Today's date string for comparison
  const todayStr = new Date().toISOString().slice(0, 10);

  // Previous month days
  for (let i = firstDay - 1; i >= 0; i--) {
    const cell = document.createElement('div');
    cell.className = 'day-cell other-month';
    cell.textContent = daysInPrevMonth - i;
    const prevMonth = month === 0 ? 11 : month - 1;
    const prevYear = month === 0 ? year - 1 : year;
    const dateStr = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-${String(daysInPrevMonth - i).padStart(2, '0')}`;
    cell.addEventListener('click', () => selectCalendarDate(dateStr));
    grid.appendChild(cell);
  }

  // Current month days
  for (let day = 1; day <= daysInMonth; day++) {
    const cell = document.createElement('div');
    cell.className = 'day-cell';
    cell.textContent = day;
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    if (dateStr === todayStr) cell.classList.add('today');
    if (dateStr === currentDate) cell.classList.add('selected');
    cell.addEventListener('click', () => selectCalendarDate(dateStr));
    grid.appendChild(cell);
  }

  // Next month days to fill grid (6 rows total = 42 cells)
  const totalCells = 42;
  const filledCells = firstDay + daysInMonth;
  for (let i = 1; i <= totalCells - filledCells; i++) {
    const cell = document.createElement('div');
    cell.className = 'day-cell other-month';
    cell.textContent = i;
    const nextMonth = month === 11 ? 0 : month + 1;
    const nextYear = month === 11 ? year + 1 : year;
    const dateStr = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
    cell.addEventListener('click', () => selectCalendarDate(dateStr));
    grid.appendChild(cell);
  }
}

function selectCalendarDate(dateStr) {
  closeCalendar();
  loadDay(dateStr);
}

function parseFlexibleDate(input) {
  // Try various formats
  const trimmed = input.trim();

  // ISO format: YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    return trimmed;
  }

  // MM/DD/YYYY or M/D/YYYY
  const slashMatch = trimmed.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (slashMatch) {
    const [, m, d, y] = slashMatch;
    return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
  }

  // Try Date.parse as fallback
  const parsed = new Date(trimmed);
  if (!isNaN(parsed.getTime())) {
    return parsed.toISOString().slice(0, 10);
  }

  return null;
}

// Calendar event listeners
document.getElementById('pretty-date').addEventListener('click', toggleCalendar);

document.getElementById('cal-prev-year').addEventListener('click', (e) => {
  e.stopPropagation();
  calendarViewDate.setFullYear(calendarViewDate.getFullYear() - 1);
  renderCalendar();
});

document.getElementById('cal-next-year').addEventListener('click', (e) => {
  e.stopPropagation();
  calendarViewDate.setFullYear(calendarViewDate.getFullYear() + 1);
  renderCalendar();
});

document.getElementById('cal-prev-month').addEventListener('click', (e) => {
  e.stopPropagation();
  calendarViewDate.setMonth(calendarViewDate.getMonth() - 1);
  renderCalendar();
});

document.getElementById('cal-next-month').addEventListener('click', (e) => {
  e.stopPropagation();
  calendarViewDate.setMonth(calendarViewDate.getMonth() + 1);
  renderCalendar();
});

document.getElementById('date-input-go').addEventListener('click', (e) => {
  e.stopPropagation();
  const input = document.getElementById('date-input').value;
  const dateStr = parseFlexibleDate(input);
  if (dateStr) {
    selectCalendarDate(dateStr);
  } else {
    showToast('Invalid date format. Try YYYY-MM-DD or MM/DD/YYYY', 'error');
  }
});

document.getElementById('date-input').addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('date-input-go').click();
  }
});

// Close calendar when clicking outside
document.addEventListener('click', (e) => {
  const dropdown = document.getElementById('calendar-dropdown');
  const prettyDate = document.getElementById('pretty-date');
  if (!dropdown.contains(e.target) && e.target !== prettyDate) {
    closeCalendar();
  }
});

document.getElementById('prev-day').addEventListener('click', () => {
  const d = new Date(currentDate);
  d.setDate(d.getDate() - 1);
  const newDate = d.toISOString().slice(0,10);
  loadDay(newDate);
});

document.getElementById('next-day').addEventListener('click', () => {
  const d = new Date(currentDate);
  d.setDate(d.getDate() + 1);
  const newDate = d.toISOString().slice(0,10);
  loadDay(newDate);
});

document.getElementById('today-btn').addEventListener('click', () => {
  const today = new Date().toISOString().slice(0,10);
  loadDay(today);
});

// Load theme from localStorage on page load
loadThemeFromStorage();

// Initialize keyboard shortcuts
loadShortcuts();
document.addEventListener('keydown', handleGlobalShortcuts);

// Initial load
loadDay(currentDate);
</script>

</body>
</html>
