<?php
// ─── Database Configuration ───────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_db');

// ─── App Settings ─────────────────────────────────────────────────────────
define('APP_NAME',    'Barangay System');
define('APP_TAGLINE', 'Complaint & Request Management');
define('BARANGAY',    'Barangay San Jose');

// ─── Session Start ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── DB Connection ────────────────────────────────────────────────────────
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div class="alert alert-danger m-4">Database connection failed: '
                . $conn->connect_error . '</div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ─── Query Helpers ────────────────────────────────────────────────────────
function db_fetch_all(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function db_fetch_one(string $sql, array $params = []): ?array {
    $rows = db_fetch_all($sql, $params);
    return $rows[0] ?? null;
}

function db_execute(string $sql, array $params = []): bool {
    $stmt = db()->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    return $stmt->execute();
}

function db_insert_id(): int {
    return (int) db()->insert_id;
}

// ─── Auth Helpers ─────────────────────────────────────────────────────────
function is_admin(): bool {
    return isset($_SESSION['admin_id']);
}

function is_resident(): bool {
    return isset($_SESSION['resident_account_id']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: /BarangayProject/index.php');
        exit;
    }
}

function require_resident(): void {
    if (!is_resident()) {
        header('Location: /BarangayProject/index.php');
        exit;
    }
}

function current_admin(): ?array {
    if (!is_admin()) return null;
    return db_fetch_one('SELECT * FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
}

function current_resident(): ?array {
    if (!is_resident()) return null;
    return db_fetch_one('SELECT * FROM resident_accounts WHERE id = ?',
                        [$_SESSION['resident_account_id']]);
}

// ─── Utilities ────────────────────────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function generate_id(string $prefix = 'REC'): string {
    return $prefix . '-' . strtoupper(substr(uniqid(), -6));
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function status_badge(string $status): string {
    $map = [
        'Pending'  => 'warning',
        'Approved' => 'primary',
        'Done'     => 'success',
        'Rejected' => 'danger',
    ];
    $cls = $map[$status] ?? 'secondary';
    return "<span class='badge bg-{$cls}'>" . e($status) . "</span>";
}
