<?php
require __DIR__.'/config.php'; require_role('admin','staff');
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); require_role('admin');
 $action=$_POST['action']??'';
 if($action==='save'){
  $id=(int)($_POST['id']??0); $name=trim($_POST['name']??''); $desc=trim($_POST['description']??'');
  if($name===''){flash('Nama kategori wajib diisi.','error');go('categories.php');}
  try { if($id){$s=$pdo->prepare('UPDATE categories SET name=?,description=? WHERE id=?');$s->execute([$name,$desc,$id]);}else{$s=$pdo->prepare('INSERT INTO categories(name,description) VALUES(?,?)');$s->execute([$name,$desc]);} flash('Kategori berhasil disimpan.');}
  catch(PDOException $e){flash('Gagal menyimpan. Nama kategori mungkin sudah digunakan.','error');}
 } elseif($action==='delete'){
  try{$s=$pdo->prepare('DELETE FROM categories WHERE id=?');$s->execute([(int)$_POST['id']]);flash('Kategori dihapus.');}
  catch(PDOException $e){flash('Kategori tidak bisa dihapus karena masih dipakai produk.','error');}
 }
 go('categories.php');
}
$edit=null;
if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM categories WHERE id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$rows=$pdo->query('SELECT c.*,COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
layout_start('Kategori');
echo '<h1>Data Kategori</h1>';
if($_SESSION['user']['role']==='admin'){echo '<div class="card"><h2>'.($edit?'Edit':'Tambah').' Kategori</h2><form method="post"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.e($edit['id']??'').'"><label>Nama kategori</label><input name="name" required maxlength="100" value="'.e($edit['name']??'').'"><label>Deskripsi</label><input name="description" maxlength="255" value="'.e($edit['description']??'').'"><button>Simpan</button></form></div>';}
else echo '<p class="muted">Akun staff hanya dapat melihat data kategori.</p>';
echo '<div class="card table-wrap"><table><tr><th>Nama</th><th>Deskripsi</th><th>Jumlah Produk</th><th>Aksi</th></tr>';
foreach($rows as $r){echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['description']).'</td><td>'.e($r['product_count']).'</td><td class="actions">';
if($_SESSION['user']['role']==='admin'){echo '<a class="btn secondary" href="?edit='.e($r['id']).'">Edit</a><form method="post" class="inline" onsubmit="return confirm(\'Hapus kategori ini?\')"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.e($r['id']).'"><button class="danger">Hapus</button></form>';}
echo '</td></tr>';}
echo '</table></div>';layout_end();
