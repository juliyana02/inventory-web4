<?php
require __DIR__.'/config.php';
if (logged_in()) go('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT id,name,username,password,role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        go('index.php');
    }
    $error = 'Username atau password salah.';
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Inventory</title><style>
body{font-family:Arial;background:#edf2f9;display:grid;place-items:center;min-height:100vh;margin:0}.box{background:white;padding:30px;border-radius:15px;width:min(420px,92vw);box-shadow:0 8px 30px #1232}input{width:100%;padding:12px;margin:8px 0 15px;box-sizing:border-box;border:1px solid #ccd5e1;border-radius:8px}button{width:100%;padding:12px;background:#173b70;color:white;border:0;border-radius:8px;font-size:16px}.error{color:#b42318}
</style></head><body><form class="box" method="post"><h1>Inventory System</h1><p>Silakan masuk ke sistem persediaan barang.</p><?php if($error): ?><p class="error"><?=e($error)?></p><?php endif; ?><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Username</label><input name="username" required autofocus><label>Password</label><input name="password" type="password" required><button>Login</button><p style="font-size:13px;color:#64748b">Demo: admin/admin123 atau staff/staff123</p></form></body></html>
