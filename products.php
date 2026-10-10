<?php
require __DIR__.'/config.php'; require_role('admin','staff');
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_role('admin'); verify_csrf(); $id=(int)($_POST['id']??0);
 $name=trim($_POST['name']??'');$sku=trim($_POST['sku']??'');$category=(int)($_POST['category_id']??0);$desc=trim($_POST['description']??'');
 $price=filter_var($_POST['price']??null,FILTER_VALIDATE_FLOAT);$stock=filter_var($_POST['stock']??null,FILTER_VALIDATE_INT);$min=filter_var($_POST['min_stock']??null,FILTER_VALIDATE_INT);
 if($name===''||$sku===''||$category<1||$price===false||$price<0||$stock===false||$stock<0||$min===false||$min<0){flash('Input tidak valid. Isi nama, SKU, kategori, harga, stok, dan batas minimum dengan benar.','error');go('products.php'.($id?'?edit='.$id:''));}
 try{
  if($id){$s=$pdo->prepare('UPDATE products SET category_id=?,name=?,sku=?,description=?,price=?,stock=?,min_stock=? WHERE id=?');$s->execute([$category,$name,$sku,$desc,$price,$stock,$min,$id]);}
  else{$s=$pdo->prepare('INSERT INTO products(category_id,name,sku,description,price,stock,min_stock) VALUES(?,?,?,?,?,?,?)');$s->execute([$category,$name,$sku,$desc,$price,$stock,$min]);}
  flash('Produk berhasil disimpan.');
 }catch(PDOException $e){flash('Gagal menyimpan. Pastikan SKU unik dan kategori tersedia.','error');}
 go('products.php');
}
$edit=null;if(isset($_GET['edit'])&&$_SESSION['user']['role']==='admin'){$s=$pdo->prepare('SELECT * FROM products WHERE id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$cats=$pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$q=trim($_GET['q']??'');$cat=(int)($_GET['category']??0);
$sort=$_GET['sort']??'name';$dir=strtolower($_GET['dir']??'asc')==='desc'?'DESC':'ASC';
$sortMap=['name'=>'p.name','price'=>'p.price','stock'=>'p.stock','sku'=>'p.sku'];$order=$sortMap[$sort]??'p.name';
$where=[];$params=[];if($q!==''){$where[]='(p.name LIKE ? OR p.sku LIKE ?)';$params[]="%$q%";$params[]="%$q%";}if($cat){$where[]='p.category_id=?';$params[]=$cat;}
$sqlWhere=$where?'WHERE '.implode(' AND ',$where):'';
$count=$pdo->prepare("SELECT COUNT(*) FROM products p $sqlWhere");$count->execute($params);$total=(int)$count->fetchColumn();
$page=max(1,(int)($_GET['page']??1));$perPage=5;$pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
$s=$pdo->prepare("SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id $sqlWhere ORDER BY $order $dir LIMIT $perPage OFFSET $offset");$s->execute($params);$rows=$s->fetchAll();
function product_url(array $replace=[]): string { $a=array_merge($_GET,$replace);return 'products.php?'.http_build_query($a); }
layout_start('Produk');
echo '<h1>Data Produk</h1>';
if($_SESSION['user']['role']==='admin'){
 echo '<div class="card"><h2>'.($edit?'Edit':'Tambah').' Produk</h2><form method="post"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="id" value="'.e($edit['id']??'').'"><div class="form-grid"><div><label>Nama produk</label><input name="name" required value="'.e($edit['name']??'').'"></div><div><label>SKU</label><input name="sku" required value="'.e($edit['sku']??'').'"></div><div><label>Kategori</label><select name="category_id" required><option value="">Pilih kategori</option>';
 foreach($cats as $c) echo '<option value="'.e($c['id']).'" '.(($edit['category_id']??'')==$c['id']?'selected':'').'>'.e($c['name']).'</option>';
 echo '</select></div><div><label>Harga (Rp)</label><input name="price" type="number" min="0" step="0.01" required value="'.e($edit['price']??'0').'"></div><div><label>Stok awal</label><input name="stock" type="number" min="0" required value="'.e($edit['stock']??'0').'"></div><div><label>Batas stok minimum</label><input name="min_stock" type="number" min="0" required value="'.e($edit['min_stock']??'5').'"></div></div><label>Deskripsi</label><textarea name="description">'.e($edit['description']??'').'</textarea><button>Simpan Produk</button></form></div>';
}else echo '<p class="muted">Akun staff dapat melihat dan mencari produk, tetapi tidak dapat mengubah master produk.</p>';
echo '<div class="card"><form method="get" class="form-grid"><div><label>Cari nama/SKU</label><input name="q" value="'.e($q).'" placeholder="Contoh: Laptop"></div><div><label>Filter kategori</label><select name="category"><option value="0">Semua kategori</option>';
foreach($cats as $c)echo '<option value="'.e($c['id']).'" '.($cat===$c['id']?'selected':'').'>'.e($c['name']).'</option>';
echo '</select></div><div><label>Urutkan</label><select name="sort">';
foreach(['name'=>'Nama','sku'=>'SKU','price'=>'Harga','stock'=>'Stok'] as $k=>$v)echo '<option value="'.e($k).'" '.($sort===$k?'selected':'').'>'.e($v).'</option>';
echo '</select></div><div><label>Arah urutan</label><select name="dir"><option value="asc" '.($dir==='ASC'?'selected':'').'>Naik</option><option value="desc" '.($dir==='DESC'?'selected':'').'>Turun</option></select></div></div><p><button>Filter</button> <a class="btn secondary" href="products.php">Reset</a></p></form></div>';
echo '<div class="card"><p class="muted">Ditemukan '.e($total).' produk — halaman '.e($page).' dari '.e($pages).'</p><div class="table-wrap"><table><tr><th>SKU</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Nilai Stok</th><th>Status</th><th>Aksi</th></tr>';
foreach($rows as $r){$critical=(int)$r['stock']<=(int)$r['min_stock'];echo '<tr><td>'.e($r['sku']).'</td><td>'.e($r['name']).'<br><small class="muted">'.e($r['description']).'</small></td><td>'.e($r['category_name']).'</td><td>Rp '.number_format((float)$r['price'],0,',','.').'</td><td>'.e($r['stock']).'</td><td>Rp '.number_format((float)$r['price']*(int)$r['stock'],0,',','.').'</td><td><span class="tag">'.($critical?'Stok Kritis':'Normal').'</span></td><td>';
if($_SESSION['user']['role']==='admin')echo '<a class="btn secondary" href="products.php?edit='.e($r['id']).'">Edit</a>';
echo '</td></tr>';}
if(!$rows)echo '<tr><td colspan="8">Produk tidak ditemukan.</td></tr>';
echo '</table></div><p class="actions">';
for($i=1;$i<=$pages;$i++)echo '<a class="btn '.($i===$page?'':'secondary').'" href="'.e(product_url(['page'=>$i])).'">'.e($i).'</a>';
echo '</p></div>';layout_end();
