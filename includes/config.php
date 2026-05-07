<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_db');
define('APP_NAME',  'Brgy. San Rafael System');
define('BARANGAY',  'Barangay San Rafael');
define('BASE_PATH', '/BarangayProject');
define('PER_PAGE',  10);

function start_admin_session(): void {
    if (session_status()===PHP_SESSION_ACTIVE && session_name()==='BRGY_ADMIN') return;
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    session_name('BRGY_ADMIN');
    session_set_cookie_params(['lifetime'=>0,'path'=>BASE_PATH.'/admin/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
function start_resident_session(): void {
    if (session_status()===PHP_SESSION_ACTIVE && session_name()==='BRGY_RESIDENT') return;
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    session_name('BRGY_RESIDENT');
    session_set_cookie_params(['lifetime'=>0,'path'=>BASE_PATH.'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
function db(): mysqli {
    static $c=null;
    if (!$c) {
        $c=new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
        if ($c->connect_error) die('<div class="alert alert-danger m-4">DB Error: '.$c->connect_error.'</div>');
        $c->set_charset('utf8mb4');
    }
    return $c;
}
function db_fetch_all(string $sql,array $p=[]): array {
    $s=db()->prepare($sql);
    if ($p) $s->bind_param(str_repeat('s',count($p)),...$p);
    $s->execute(); $r=$s->get_result(); $rows=[];
    while ($row=$r->fetch_assoc()) $rows[]=$row;
    return $rows;
}
function db_fetch_one(string $sql,array $p=[]): ?array { $r=db_fetch_all($sql,$p); return $r[0]??null; }
function db_execute(string $sql,array $p=[]): bool {
    $s=db()->prepare($sql);
    if ($p) $s->bind_param(str_repeat('s',count($p)),...$p);
    return $s->execute();
}
function db_insert_id(): int { return (int)db()->insert_id; }
function is_admin(): bool    { return session_name()==='BRGY_ADMIN'    && isset($_SESSION['admin_id']); }
function is_resident(): bool { return session_name()==='BRGY_RESIDENT' && isset($_SESSION['resident_account_id']); }
function require_admin(): void    { start_admin_session();    if (!is_admin())    { header('Location:'.BASE_PATH.'/admin/login.php'); exit; } }
function require_resident(): void { start_resident_session(); if (!is_resident()) { header('Location:'.BASE_PATH.'/index.php');       exit; } }
function current_admin(): ?array    { if (!is_admin())    return null; return db_fetch_one('SELECT * FROM admins WHERE id=?',[$_SESSION['admin_id']]); }
function current_resident(): ?array { if (!is_resident()) return null; return db_fetch_one('SELECT * FROM resident_accounts WHERE id=?',[$_SESSION['resident_account_id']]); }
function e(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
function generate_id(string $prefix='REC'): string { return $prefix.'-'.strtoupper(substr(uniqid(),-6)); }
function flash(string $type,string $msg): void { $_SESSION['flash']=['type'=>$type,'msg'=>$msg]; }
function get_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function status_badge(string $s): string {
    $m=['Pending'=>'warning','Approved'=>'primary','Done'=>'success','Rejected'=>'danger'];
    return "<span class='badge bg-".($m[$s]??'secondary')."'>".e($s)."</span>";
}
// Password helpers
function hash_password(string $p): string { return password_hash($p,PASSWORD_BCRYPT); }
function verify_password(string $plain,string $stored): bool {
    if (password_verify($plain,$stored)) return true;
    return $plain===$stored; // legacy plain-text fallback
}
function maybe_upgrade_hash(string $plain,string $stored,int $id,string $tbl='resident_accounts'): void {
    if (!str_starts_with($stored,'$2')) db_execute("UPDATE {$tbl} SET password=? WHERE id=?",[hash_password($plain),$id]);
}
// CSRF
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.csrf_token().'">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')) { http_response_code(403); die('Security check failed.'); } }
// Pagination
function paginate(array $items,int $per,int $page): array {
    $total=count($items); $pages=max(1,(int)ceil($total/$per));
    $page=max(1,min($page,$pages));
    return ['items'=>array_slice($items,($page-1)*$per,$per),'total'=>$total,'pages'=>$pages,'current'=>$page];
}
function pagination_html(int $pages,int $cur,string $base): string {
    if ($pages<=1) return '';
    $h='<nav><ul class="pagination pagination-sm justify-content-end mb-0 flex-wrap">';
    $h.='<li class="page-item'.($cur<=1?' disabled':'').'"><a class="page-link" href="'.$base.'&page='.($cur-1).'">‹</a></li>';
    for ($i=1;$i<=$pages;$i++) {
        if ($pages>7&&$i>2&&$i<$pages-1&&abs($i-$cur)>1){if($i===3||$i===$pages-2)$h.='<li class="page-item disabled"><span class="page-link">…</span></li>';continue;}
        $h.='<li class="page-item'.($i===$cur?' active':'').'"><a class="page-link" href="'.$base.'&page='.$i.'">'.$i.'</a></li>';
    }
    $h.='<li class="page-item'.($cur>=$pages?' disabled':'').'"><a class="page-link" href="'.$base.'&page='.($cur+1).'">›</a></li>';
    return $h.'</ul></nav>';
}
// Validation
function validate_contact(string $v): bool { return (bool)preg_match('/^(09|\+639)\d{9}$/',$v); }
function validate_password(string $p): ?string { return strlen($p)<6?'Password must be at least 6 characters.':null; }
