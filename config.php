<?php
declare(strict_types=1);

session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'inventory_db';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function go(string $url): never {
    header('Location: ' . $url);
    exit;
}
function logged_in(): bool {
    return isset($_SESSION['user']);
}
function require_login(): void {
    if (!logged_in()) go('login.php');
}
function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit('403 — Akses ditolak. Role akun ini tidak diizinkan membuka halaman tersebut.');
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Token formulir tidak valid. Silakan kembali dan coba lagi.');
    }
}
function flash(string $message, string $type='success'): void {
    $_SESSION['flash'] = ['message'=>$message, 'type'=>$type];
}
function take_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
function layout_start(string $title): void {
    $flash = take_flash();
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>'.e($title).' | Inventory System</title><style>
    *{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#172033;font:15px/1.5 Arial,sans-serif}
    header{background:#173b70;color:white;padding:17px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
    header a{color:white;text-decoration:none;margin-right:12px}main{max-width:1150px;margin:25px auto;padding:0 16px}
    .card{background:white;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:18px;box-shadow:0 3px 12px #17203308}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
    .metric{font-size:26px;font-weight:700;color:#173b70}.muted{color:#64748b}
    input,select,textarea{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;margin:5px 0 12px;font:inherit}
    button,.btn{display:inline-block;background:#245bb5;color:white;border:0;border-radius:8px;padding:9px 13px;text-decoration:none;cursor:pointer;font:inherit}
    .secondary{background:#e8eef8;color:#173b70}.danger{background:#b42318}.inline{display:inline}
    table{width:100%;border-collapse:collapse;background:white}th,td{text-align:left;padding:11px;border-bottom:1px solid #e2e8f0;vertical-align:top}th{background:#f8fafc}
    .table-wrap{overflow-x:auto}.alert{padding:12px;border-radius:8px;background:#e8f6ed;margin-bottom:14px}.alert.error{background:#fff0f0}
    .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}
    h1{font-size:26px;margin-top:0}h2{font-size:19px}.tag{padding:4px 8px;border-radius:20px;background:#fff0cc;color:#8a5800;font-size:12px}
    .actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}footer{text-align:center;color:#64748b;padding:25px}
    </style></head><body><header><div><strong>Inventory Management System</strong></div><nav>';
    if (logged_in()) {
        echo '<a href="index.php">Dashboard</a><a href="categories.php">Kategori</a><a href="products.php">Produk</a><a href="movements.php">Barang Masuk/Keluar</a>';
        if ($_SESSION['user']['role']==='admin') echo '<a href="users.php">Akun</a>';
        echo '<a href="logout.php">Keluar ('.e($_SESSION['user']['name']).')</a>';
    }
    echo '</nav></header><main>';
    if ($flash) echo '<div class="alert '.($flash['type']==='error'?'error':'').'">'.e($flash['message']).'</div>';
}
function layout_end(): void { echo '</main><footer>Inventory Management System — Tugas Web 4</footer></body></html>'; }
