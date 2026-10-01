<?php
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

$kas = pendapatan_sejak($db, batas_kas($db));
$rt = $db->query("SELECT MAX(waktu) AS w FROM tutup_kas WHERE waktu >= CURDATE()")->fetch_assoc();
$reset_terakhir = $rt['w'];
$data = $db->query("SELECT s.*, u.nama, u.jenis, m.nama AS member_nama FROM sesi s JOIN unit u ON u.id = s.unit_id LEFT JOIN member m ON m.id = s.member_id
                    WHERE s.waktu_selesai IS NOT NULL ORDER BY s.id DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat - D1vzz PlayStation</title><link rel="stylesheet" href="style.css"><script src="tema.js"></script></head>
<body>
  <header>
    <h1><svg class="logo-ps" viewBox="0 0 74 16" width="56" height="12" fill="none" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"><path class="s1" d="M8 2 L14 13 L2 13 Z"/><circle class="s2" cx="28" cy="8" r="5.5"/><path class="s3" d="M40 3 L51 13 M51 3 L40 13"/><rect class="s4" x="61" y="3" width="10" height="10" rx="1"/></svg>D1vzz PlayStation</h1>
    <nav><a href="index.php">Dashboard</a><a href="member.php">Member</a><a href="riwayat.php" class="aktif">Riwayat</a><?= menu_tambahan($db) ?><a href="logout.php">Keluar</a>
      <button id="tema" class="tema" type="button">Mode gelap</button></nav>
  </header>
  <main>
    <?php pesan(); ?>
    <div class="ringkasan">
      Pendapatan hari ini: <strong>Rp <?= number_format($kas['total'], 0, ',', '.') ?></strong>
      (<?= $kas['sesi_jml'] ?> sesi Rp <?= number_format($kas['sesi_total'], 0, ',', '.') ?>,
       <?= $kas['member_jml'] ?> pendaftaran member Rp <?= number_format($kas['member_total'], 0, ',', '.') ?>)
      <?php if ($reset_terakhir): ?><br><small>Dihitung sejak reset terakhir: <?= $reset_terakhir ?></small><?php endif; ?>
    </div>

    <?php if (is_admin()): ?>
    <div class="kotak">
      <h2>Unduh laporan pendapatan</h2>
      <p>Pilih rentang tanggal, lalu unduh dalam format CSV (bisa dibuka di Excel).</p>
      <form action="export.php" method="get" class="form-baris">
        <div><label>Dari tanggal</label><input type="date" name="dari" value="<?= date('Y-m-d') ?>"></div>
        <div><label>Sampai tanggal</label><input type="date" name="sampai" value="<?= date('Y-m-d') ?>"></div>
        <button class="btn btn-start">Unduh CSV</button>
      </form>
    </div>

    <div class="kotak">
      <h2>Reset pendapatan hari ini</h2>
      <p>Mengembalikan hitungan di dashboard ke Rp 0. Riwayat transaksi tidak dihapus dan tetap bisa diunduh. Sebaiknya unduh laporannya dulu.</p>
      <form action="aksi.php" method="post" onsubmit="return confirm('Reset pendapatan hari ini ke Rp 0? Riwayat transaksi tetap tersimpan.')">
        <input type="hidden" name="aksi" value="reset_kas">
        <button class="btn btn-stop btn-inline">Reset pendapatan</button>
      </form>
    </div>
    <?php else:
      $menunggu_reset = ada_pending($db, 'reset_kas');
      $terakhir = $db->query("SELECT status FROM permintaan WHERE jenis='reset_kas' AND status<>'menunggu' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    ?>
    <div class="kotak">
      <h2>Reset pendapatan hari ini</h2>
      <p>Reset harus disetujui admin (pemilik). Ajukan permintaan, lalu tunggu persetujuan.</p>
      <?php if ($menunggu_reset): ?>
        <span class="menunggu">Permintaan sedang menunggu persetujuan admin</span>
      <?php else: ?>
        <form action="aksi.php" method="post" onsubmit="return confirm('Ajukan reset pendapatan ke admin?')">
          <input type="hidden" name="aksi" value="minta_reset">
          <button class="btn btn-stop btn-inline">Ajukan reset</button>
        </form>
        <?php if ($terakhir): ?><p class="info-kecil">Permintaan terakhir: <?= $terakhir['status'] ?></p><?php endif; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="tabel-wrap">
    <table>
      <tr><th>No</th><th>Unit</th><th>Pelanggan</th><th>Mulai</th><th>Selesai</th><th>Durasi</th><th>Total</th></tr>
      <?php $no = 1; while ($r = $data->fetch_assoc()): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= $r['nama'] ?> (<?= $r['jenis'] ?>)</td>
        <td><?= $r['member_nama'] ? htmlspecialchars($r['member_nama']) . ' (-' . (int)$r['diskon_persen'] . '%)' : 'Umum' ?></td>
        <td><?= $r['waktu_mulai'] ?></td>
        <td><?= $r['waktu_selesai'] ?></td>
        <td><?= $r['durasi_menit'] ?> menit</td>
        <td>Rp <?= number_format($r['total_biaya'], 0, ',', '.') ?></td>
      </tr>
      <?php endwhile; ?>
    </table>
    </div>
  </main>
</body>
</html>