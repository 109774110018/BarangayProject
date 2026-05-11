<?php
// ============================================================
//  Barangay San Rafael — Enhanced Auto Backup System
//  - Backs up ALL tables including archives
//  - Triggered on: logout, permanent delete, empty trash
//  - Keeps last 30 days of backups
//  - Admin can view history and download any backup
// ============================================================

define('BACKUP_DIR',       __DIR__ . '/../backups/');
define('BACKUP_SCHEDULE',  'daily');
define('BACKUP_KEEP_DAYS', 30);

// All critical tables to always include in backup
define('BACKUP_PRIORITY_TABLES', [
    'admins',
    'residents',
    'resident_accounts',
    'records',
    'notifications',
    'archives',
]);

function backup_expected_filename(): string {
    if (BACKUP_SCHEDULE === 'weekly') {
        $monday = date('Y_m_d', strtotime('monday this week'));
        return BACKUP_DIR . "backup_{$monday}.sql";
    }
    return BACKUP_DIR . 'backup_' . date('Y_m_d') . '.sql';
}

// ── Core backup routine ──────────────────────────────────────
// trigger: 'logout' | 'archive' | 'empty_trash' | 'manual'
function run_backup(string $trigger = 'logout'): bool {
    if (!is_dir(BACKUP_DIR)) {
        if (!mkdir(BACKUP_DIR, 0755, true)) {
            error_log('[Backup] Cannot create backup directory: ' . BACKUP_DIR);
            return false;
        }
        file_put_contents(BACKUP_DIR . '.htaccess', "Deny from all\n");
    }

    // For archive/empty_trash triggers, always create a fresh timestamped file
    if (in_array($trigger, ['archive','empty_trash','manual'])) {
        $filename = BACKUP_DIR . 'backup_' . date('Y_m_d_His') . "_{$trigger}.sql";
    } else {
        $filename = backup_expected_filename();
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('[Backup] DB connect failed: ' . $conn->connect_error);
        return false;
    }
    $conn->set_charset('utf8mb4');

    $sql  = "-- ============================================================\n";
    $sql .= "--  Barangay San Rafael — Database Backup\n";
    $sql .= "--  Database : " . DB_NAME . "\n";
    $sql .= "--  Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "--  Trigger  : {$trigger}\n";
    $sql .= "--  Schedule : " . BACKUP_SCHEDULE . "\n";
    $sql .= "-- ============================================================\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables — priority tables first, then the rest
    $all_tables = [];
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_row()) $all_tables[] = $row[0];

    // Sort: priority tables first
    $priority = array_intersect(BACKUP_PRIORITY_TABLES, $all_tables);
    $others   = array_diff($all_tables, BACKUP_PRIORITY_TABLES);
    $tables   = array_merge($priority, $others);

    foreach ($tables as $table) {
        $createRes  = $conn->query("SHOW CREATE TABLE `{$table}`");
        $createRow  = $createRes->fetch_assoc();
        $createStmt = $createRow['Create Table'] ?? $createRow[array_keys($createRow)[1]];

        $sql .= "-- Table: `{$table}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createStmt . ";\n\n";

        $dataRes = $conn->query("SELECT * FROM `{$table}`");
        if ($dataRes && $dataRes->num_rows > 0) {
            $sql .= "INSERT INTO `{$table}` VALUES\n";
            $rows = [];
            while ($dataRow = $dataRes->fetch_row()) {
                $escaped = array_map(function ($v) use ($conn) {
                    if ($v === null) return 'NULL';
                    return "'" . $conn->real_escape_string($v) . "'";
                }, $dataRow);
                $rows[] = '(' . implode(', ', $escaped) . ')';
            }
            $sql .= implode(",\n", $rows) . ";\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $conn->close();

    if (file_put_contents($filename, $sql) === false) {
        error_log('[Backup] Cannot write backup file: ' . $filename);
        return false;
    }

    // Log backup event
    log_backup_event($trigger, basename($filename), strlen($sql));

    purge_old_backups();
    return true;
}

// ── Log backup to a JSON history file ───────────────────────
function log_backup_event(string $trigger, string $filename, int $size): void {
    $log_file = BACKUP_DIR . 'backup_history.json';
    $history  = [];
    if (file_exists($log_file)) {
        $history = json_decode(file_get_contents($log_file), true) ?? [];
    }
    array_unshift($history, [
        'filename'  => $filename,
        'trigger'   => $trigger,
        'size_bytes'=> $size,
        'created_at'=> date('Y-m-d H:i:s'),
    ]);
    // Keep only last 100 entries
    $history = array_slice($history, 0, 100);
    file_put_contents($log_file, json_encode($history, JSON_PRETTY_PRINT));
}

// ── Get backup history ───────────────────────────────────────
function get_backup_history(): array {
    $log_file = BACKUP_DIR . 'backup_history.json';
    if (!file_exists($log_file)) return [];
    return json_decode(file_get_contents($log_file), true) ?? [];
}

// ── Get all backup files on disk ─────────────────────────────
function get_backup_files(): array {
    $files = glob(BACKUP_DIR . 'backup_*.sql') ?: [];
    usort($files, fn($a,$b) => filemtime($b) - filemtime($a));
    return $files;
}

// ── Format file size ─────────────────────────────────────────
function format_size(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes/1048576,1) . ' MB';
    if ($bytes >= 1024)    return round($bytes/1024,1) . ' KB';
    return $bytes . ' B';
}

// ── Trigger label ────────────────────────────────────────────
function trigger_badge(string $trigger): string {
    $map = [
        'logout'      => ['secondary', 'Logout'],
        'archive'     => ['warning',   'Archive'],
        'empty_trash' => ['danger',    'Empty Trash'],
        'manual'      => ['primary',   'Manual'],
    ];
    [$cls, $label] = $map[$trigger] ?? ['secondary', ucfirst($trigger)];
    return "<span class='badge bg-{$cls}'>{$label}</span>";
}

// ── Purge old backups ────────────────────────────────────────
function purge_old_backups(): void {
    $cutoff = strtotime('-' . BACKUP_KEEP_DAYS . ' days');
    foreach (glob(BACKUP_DIR . 'backup_*.sql') as $file) {
        // Never delete manually triggered or archive backups automatically
        if (strpos($file, '_manual') || strpos($file, '_archive') || strpos($file, '_empty_trash')) continue;
        if (filemtime($file) < $cutoff) @unlink($file);
    }
}

// ── Called on logout — only runs once per day ────────────────
function maybe_run_backup(): void {
    $expected = backup_expected_filename();
    if (!file_exists($expected)) run_backup('logout');
}
