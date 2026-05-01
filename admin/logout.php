<?php
require_once __DIR__ . '/../includes/config.php';
start_admin_session();
require_admin();

// Auto-backup on logout
require_once __DIR__ . '/../includes/backup.php';
run_backup();

session_unset();
session_destroy();
header('Location: /BarangayProject/admin/login.php');
exit;
