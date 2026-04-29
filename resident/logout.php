<?php
// ─── Resident Logout ──────────────────────────────────────────────────────
// Triggers an automatic database backup before ending the session.
// The backup only runs once per day (or week, depending on the schedule
// set in backup.php), so logging out multiple times won't create duplicates.
// ─────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/backup.php';

// ── 1. Auto Backup on Logout ──────────────────────────────────────────────
//    Silently backs up the database in the background.
//    If the backup fails it is logged to PHP's error log but the resident
//    is still logged out normally so the session is never stuck.
maybe_run_backup();

// ── 2. Destroy the Session ────────────────────────────────────────────────
session_unset();
session_destroy();

// ── 3. Redirect to Login Page ─────────────────────────────────────────────
header('Location: /BarangayProject/index.php');
exit;
