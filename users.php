<?php
require __DIR__.'/config.php'; require_role('admin'); $pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$name=trim($_POST['name']??'');$username=trim($_POST['username']??'');$password=$_POST['password']??'';$role=$_POST['role']??'staff';
 if($name===''||$username===''||strlen($password)<6||!in_array($role,['admin','staff'],true)){flash('Isi semua data dengan benar. Password minimal 6 karakter.','error');go('users.php');}
 try{$s=$pdo->prepare('INSERT INTO users(name,username,password,role) VALUES(?,?,?,?)');$s->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role]);flash('Akun berhasil dibuat.');}
 catch(PDOException $e){flash('Username sudah digunakan atau data tidak valid.','error');}
 go('users.php');
}
$rows=$pdo->query('SELECT id,name,username,role,created_at FROM users ORDER BY id')->fetchAll();
layout_start('Akun Pengguna');echo '<h1>Akun Pengguna</h1><div class="card"><h2>Tambah akun</h2><form method="post"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><div class="form-grid"><div><label>Nama</label><input name="name" required></div><div><label>Username</label><input name="username" required></div><div><label>Password (min. 6 karakter)</label><input type="password" name="password" minlength="6" required></div><div><label>Role</label><select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></div></div><button>Buat akun</button></form></div><div class="card"><table><tr><th>Nama</th><th>Username</th><th>Role</th><th>Dibuat</th></tr>';
foreach($rows as $r)echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['username']).'</td><td>'.e($r['role']).'</td><td>'.e($r['created_at']).'</td></tr>';
echo '</table></div>';layout_end();
