<?php
require __DIR__.'/config.php'; require_login();
$pdo=db();
$totalProducts=(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalStock=(int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM products')->fetchColumn();
$asset=(float)$pdo->query('SELECT COALESCE(SUM(price*stock),0) FROM products')->fetchColumn();
$low=(int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock <= min_stock')->fetchColumn();
$recent=$pdo->query('SELECT sm.*,p.name product_name,u.name user_name FROM stock_movements sm JOIN products p ON p.id=sm.product_id JOIN users u ON u.id=sm.user_id ORDER BY sm.id DESC LIMIT 6')->fetchAll();
$critical=$pdo->query('SELECT name,stock,min_stock FROM products WHERE stock <= min_stock ORDER BY stock ASC LIMIT 6')->fetchAll();

$cat=$pdo->query('SELECT c.name, COALESCE(SUM(p.stock),0) total FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id,c.name ORDER BY c.name')->fetchAll();
$catLabels=array_column($cat,'name');
$catData=array_map('intval',array_column($cat,'total'));

$mv=$pdo->query("SELECT COALESCE(SUM(CASE WHEN movement_type='in' THEN quantity END),0) masuk, COALESCE(SUM(CASE WHEN movement_type='out' THEN quantity END),0) keluar FROM stock_movements")->fetch();
$mvData=[(int)$mv['masuk'],(int)$mv['keluar']];

$J=JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
$stats=[
  ['Jumlah Produk',$totalProducts,'📦','s1'],
  ['Total Stok',$totalStock,'📊','s2'],
  ['Nilai Aset Gudang','Rp '.number_format($asset,0,',','.'),'💰','s3'],
  ['Produk Stok Kritis',$low,'⚠️','s4'],
];
layout_start('Dashboard');
?>
<style>
:root{--biru:#245bb5;--biru-tua:#173b70;--oranye:#f97316}
.hero{background:linear-gradient(120deg,var(--biru-tua),var(--biru));color:#fff;border-radius:16px;padding:26px 28px;margin-bottom:20px;box-shadow:0 10px 30px #245bb540}
.hero h1{margin:0 0 4px;color:#fff;font-size:28px}.hero p{margin:0;opacity:.9}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:20px}
.stat{display:flex;align-items:center;gap:14px;border-radius:16px;padding:20px;color:#fff;background:var(--biru);box-shadow:0 8px 22px #0002;transition:transform .2s}
.stat:hover{transform:translateY(-4px)}
.stat .ico{font-size:32px;background:#ffffff33;border-radius:14px;width:58px;height:58px;display:flex;align-items:center;justify-content:center}
.stat .lbl{font-size:13px;opacity:.92}.stat .val{font-size:24px;font-weight:700}
.s4{background:var(--oranye)}
.row{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;margin-bottom:18px}
.box{background:#fff;border-radius:16px;padding:20px;box-shadow:0 6px 20px #1720330d;border-top:4px solid var(--biru)}
.box.b4{border-top-color:var(--oranye)}
.box h2{margin:0 0 14px;font-size:18px}
.badge{padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700}
.in{background:#dbeafe;color:#1d4ed8}.out,.warn{background:#ffedd5;color:#c2410c}
.box tr:hover td{background:#f8fafc}
</style>

<div class="hero">
  <h1>👋 Halo, <?= e($_SESSION['user']['name']) ?>!</h1>
  <p>Ringkasan persediaan barang saat ini.</p>
</div>

<div class="stats">
<?php foreach($stats as [$label,$value,$icon,$cls]): ?>
  <div class="stat <?= $cls ?>">
    <div class="ico"><?= $icon ?></div>
    <div><div class="lbl"><?= e($label) ?></div><div class="val"><?= e($value) ?></div></div>
  </div>
<?php endforeach; ?>
</div>

<div class="row">
  <div class="box chart-box"><h2>📊 Total Stok per Kategori</h2><canvas id="chartKategori" height="230"></canvas></div>
  <div class="box chart-box"><h2>🔄 Barang Masuk vs Keluar</h2><canvas id="chartMovement" height="230"></canvas></div>
</div>

<div class="row">
  <div class="box">
    <h2>🕒 Pergerakan Stok Terbaru</h2>
    <div class="table-wrap"><table>
      <tr><th>Waktu</th><th>Produk</th><th>Jenis</th><th>Jumlah</th><th>Petugas</th></tr>
      <?php foreach($recent as $r): ?>
      <tr>
        <td><?= e($r['created_at']) ?></td>
        <td><?= e($r['product_name']) ?></td>
        <td><?php if($r['movement_type']==='in'): ?><span class="badge in">Masuk</span><?php else: ?><span class="badge out">Keluar</span><?php endif; ?></td>
        <td><?= e($r['quantity']) ?></td>
        <td><?= e($r['user_name']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$recent): ?><tr><td colspan="5">Belum ada pergerakan stok.</td></tr><?php endif; ?>
    </table></div>
  </div>
  <div class="box b4">
    <h2>🚨 Stok Kritis</h2>
    <div class="table-wrap"><table>
      <tr><th>Produk</th><th>Stok</th><th>Minimum</th></tr>
      <?php foreach($critical as $c): ?>
      <tr><td><?= e($c['name']) ?></td><td><span class="badge warn"><?= e($c['stock']) ?></span></td><td><?= e($c['min_stock']) ?></td></tr>
      <?php endforeach; ?>
      <?php if(!$critical): ?><tr><td colspan="3">Semua stok aman 👍</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
if (typeof Chart === 'undefined') {
  document.querySelectorAll('.chart-box').forEach(function(b){
    b.insertAdjacentHTML('beforeend','<p class="muted">Grafik gagal dimuat. Cek koneksi internet lalu tekan Ctrl+F5.</p>');
  });
} else {
    var catLabels=<?= json_encode($catLabels,$J) ?>;
  new Chart(document.getElementById('chartKategori'),{
    type:'bar',
    data:{labels:catLabels,datasets:[{label:'Total stok',data:<?= json_encode($catData,$J) ?>,backgroundColor:'#245bb5',borderRadius:8}]},
    options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}
  });
  new Chart(document.getElementById('chartMovement'),{
    type:'doughnut',
    data:{labels:['Barang masuk','Barang keluar'],datasets:[{data:<?= json_encode($mvData,$J) ?>,backgroundColor:['#245bb5','#f97316'],borderWidth:0}]},
    options:{cutout:'62%',plugins:{legend:{position:'bottom'}}}
  });
}
</script>
<?php layout_end();