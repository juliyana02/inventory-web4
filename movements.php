<?php
require __DIR__.'/config.php'; require_role('admin','staff'); $pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $product=(int)($_POST['product_id']??0);$qty=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT);$type=$_POST['movement_type']??'';$note=trim($_POST['note']??'');
 if($product<1||$qty===false||$qty<1||!in_array($type,['in','out'],true)){flash('Data pergerakan stok tidak valid.','error');go('movements.php');}
 try{
  $pdo->beginTransaction();
  $s=$pdo->prepare('SELECT id,stock FROM products WHERE id=? FOR UPDATE');$s->execute([$product]);$p=$s->fetch();
  if(!$p)throw new RuntimeException('Produk tidak ditemukan.');
  if($type==='out'&&(int)$p['stock']<$qty)throw new RuntimeException('Stok tidak cukup untuk barang keluar.');
  $newStock=$type==='in'?(int)$p['stock']+$qty:(int)$p['stock']-$qty;
  $s=$pdo->prepare('UPDATE products SET stock=? WHERE id=?');$s->execute([$newStock,$product]);
  $s=$pdo->prepare('INSERT INTO stock_movements(product_id,user_id,movement_type,quantity,note) VALUES(?,?,?,?,?)');
  $s->execute([$product,$_SESSION['user']['id'],$type,$qty,$note]);
  $pdo->commit();flash('Pergerakan stok berhasil dicatat dan stok telah diperbarui.');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash($e instanceof RuntimeException?$e->getMessage():'Transaksi gagal. Tidak ada perubahan stok yang disimpan.','error');}
 go('movements.php');
}
$products=$pdo->query('SELECT id,name,sku,stock FROM products ORDER BY name')->fetchAll();
$rows=$pdo->query('SELECT sm.*,p.name product_name,p.sku,u.name user_name FROM stock_movements sm JOIN products p ON p.id=sm.product_id JOIN users u ON u.id=sm.user_id ORDER BY sm.id DESC LIMIT 100')->fetchAll();
layout_start('Barang Masuk/Keluar');
echo '<h1>Barang Masuk dan Barang Keluar</h1><div class="card"><h2>Catat Pergerakan Stok</h2><form method="post"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><div class="form-grid"><div><label>Produk</label><select name="product_id" required><option value="">Pilih produk</option>';
foreach($products as $p)echo '<option value="'.e($p['id']).'">'.e($p['sku'].' — '.$p['name'].' (stok '.$p['stock'].')').'</option>';
echo '</select></div><div><label>Jenis pergerakan</label><select name="movement_type"><option value="in">Barang masuk</option><option value="out">Barang keluar</option></select></div><div><label>Jumlah</label><input type="number" name="quantity" min="1" required></div><div><label>Catatan</label><input name="note" maxlength="255" placeholder="Contoh: pembelian pemasok"></div></div><button>Simpan Pergerakan</button></form></div><div class="card"><h2>Riwayat Pergerakan</h2><div class="table-wrap"><table><tr><th>Tanggal</th><th>SKU</th><th>Produk</th><th>Jenis</th><th>Jumlah</th><th>Catatan</th><th>Petugas</th></tr>';
foreach($rows as $r)echo '<tr><td>'.e($r['created_at']).'</td><td>'.e($r['sku']).'</td><td>'.e($r['product_name']).'</td><td>'.($r['movement_type']==='in'?'Masuk':'Keluar').'</td><td>'.e($r['quantity']).'</td><td>'.e($r['note']).'</td><td>'.e($r['user_name']).'</td></tr>';
if(!$rows)echo '<tr><td colspan="7">Belum ada riwayat.</td></tr>';
echo '</table></div></div>';layout_end();
