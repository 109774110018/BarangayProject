<?php
// Auto Backup System 
// Backs up the database automatically on a schedule (daily).
// Backup files are saved as: backups/backup_YYYY_MM_DD.sql


define('BACKUP_DIR',       __DIR__ . '/../backups/');
define('BACKUP_SCHEDULE',  'daily');   // 'daily' or 'weekly'
define('BACKUP_KEEP_DAYS', 30);        // how many days to retain old backups

/**
 * Returns the path of the backup file that SHOULD exist for today (daily)
 * or this week's Monday (weekly).
 */
function backup_expected_filename(): string
{
    if (BACKUP_SCHEDULE === 'weekly') {
        // Pin to Monday of the current ISO week
        $monday = date('Y_m_d', strtotime('monday this week'));
        return BACKUP_DIR . "backup_{$monday}.sql";
    }
    // Daily: one file per calendar day
    return BACKUP_DIR . 'backup_' . date('Y_m_d') . '.sql';
}

/**
 * Core backup routine.
 * Exports the full database to an SQL file using PHP/MySQLi (no shell tools
 * needed), so it works on shared hosts that block exec/mysqldump.
 *
 * Returns true on success, false on failure.
 */
function run_backup(): bool
{
    // 1. Ensure backup directory exists
    if (!is_dir(BACKUP_DIR)) {
        if (!mkdir(BACKUP_DIR, 0755, true)) {
            error_log('[Backup] Cannot create backup directory: ' . BACKUP_DIR);
            return false;
        }
        // Drop an .htaccess so Apache won't serve raw SQL files directly
        file_put_contents(BACKUP_DIR . '.htaccess', "Deny from all\n");
    }

    $filename = backup_expected_filename();

    // 2. Dump using MySQLi 
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('[Backup] DB connect failed: ' . $conn->connect_error);
        return false;
    }
    $conn->set_charset('utf8mb4');

    $sql  = "-- ============================================================\n";
    $sql .= "--  Barangay System — Auto Backup\n";
    $sql .= "--  Database : " . DB_NAME . "\n";
    $sql .= "--  Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "--  Schedule : " . BACKUP_SCHEDULE . "\n";
    $sql .= "-- ============================================================\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Fetch all tables
    $tables = [];
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_row()) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        // CREATE TABLE statement 
        $createRes = $conn->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createRes->fetch_assoc();
        $createStmt = $createRow['Create Table'] ?? $createRow[array_keys($createRow)[1]];

        $sql .= "-- Table: `{$table}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createStmt . ";\n\n";

        // INSERT rows / export rows
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

    //  3. Write file
    if (file_put_contents($filename, $sql) === false) {
        error_log('[Backup] Cannot write backup file: ' . $filename);
        return false;
    }

    // 4. Purge old backups beyond retention window
    purge_old_backups();

    return true;
}

/**
 * Deletes backup files older than BACKUP_KEEP_DAYS days.
 */
function purge_old_backups(): void
{
    $cutoff = strtotime('-' . BACKUP_KEEP_DAYS . ' days');
    foreach (glob(BACKUP_DIR . 'backup_*.sql') as $file) {
        if (filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

/**
 * Called on every logout (and optionally on login).
 * Only triggers the actual dump when a backup for today/this-week
 * doesn't already exist — so it won't re-dump multiple times a day.
 */
function maybe_run_backup(): void
{
    $expected = backup_expected_filename();
    if (!file_exists($expected)) {
        run_backup();
    }
}
