<?php
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
wajib_admin();

$menunggu = $db->query("SELECT p.*, m.nama AS nama_member FROM permintaan p LEFT JOIN member m ON m.id = p.member_id
                        WHERE p.status = 'menunggu' ORDER BY p.id")->fetch_all(MYSQLI_ASSOC);
$riwayat = $db->query("SELECT p.*, m.nama AS nama_member FROM permintaan p LEFT JOIN member m ON m.id = p.member_id
                       WHERE p.status <> 'menunggu' ORDER BY p.waktu_putus DESC LIMIT 15")->fetch_all(MYSQLI_ASSOC);
function label($p) {
  return $p['jenis'] == 'reset_kas' ? 'Reset pendapatan hari ini' : 'Hapus member: ' . htmlspecialchars($p['nama_member'] ?? '-');
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Persetujuan - D1vzz PlayStation</title><link rel="stylesheet" href="style.css"><script src="tema.js"></script></head>
<body>
  <header>
    <h1><svg class="logo-ps" viewBox="0 0 74 16" width="56" height="12" fill="none" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"><path class="s1" d="M8 2 L14 13 L2 13 Z"/><circle class="s2" cx="28" cy="8" r="5.5"/><path class="s3" d="M40 3 L51 13 M51 3 L40 13"/><rect class="s4" x="61" y="3" width="10" height="10" rx="1"/></svg>D1vzz PlayStation</h1>
    <nav>
      <a href="index.php">Dashboard</a>
      <a href="member.php">Member</a>
      <a href="riwayat.php">Riwayat</a>
      <?= menu_tambahan($db, 'persetujuan') ?>
      <a href="logout.php">Keluar</a>
      <button id="tema" class="tema" type="button">Mode gelap</button>
    </nav>
  </header>
  <main>
    <?php pesan(); ?>

    <div class="kotak">
      <h2>Menunggu persetujuan</h2>
      <p>Permintaan dari kasir baru dijalankan setelah admin setujui. Kalau ditolak, tidak ada yang berubah.</p>
      <?php if (!$menunggu): ?>
        <p class="info-kecil">Tidak ada permintaan.</p>
      <?php else: ?>
      <div class="tabel-wrap"><table>
        <tr><th>Waktu</th><th>Diminta oleh</th><th>Permintaan</th><th></th></tr>
        <?php foreach ($menunggu as $p): ?>
        <tr>
          <td><?= $p['waktu_minta'] ?></td>
          <td><?= htmlspecialchars($p['diminta_oleh']) ?></td>
          <td><?= label($p) ?></td>
          <td class="sel-aksi">
            <form action="aksi.php" method="post" class="baris-tombol">
              <input type="hidden" name="aksi" value="putuskan"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button name="keputusan" value="setuju" class="btn-kecil btn-setuju" onclick="return confirm('Setujui dan jalankan permintaan ini?')">Setujui</button>
              <button name="keputusan" value="tolak" class="btn-kecil">Tolak</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table></div>
      <?php endif; ?>
    </div>

    <div class="kotak">
      <h2>Keputusan terakhir</h2>
      <?php if (!$riwayat): ?>
        <p class="info-kecil">Belum ada.</p>
      <?php else: ?>
      <div class="tabel-wrap"><table>
        <tr><th>Diputuskan</th><th>Diminta oleh</th><th>Permintaan</th><th>Hasil</th></tr>
        <?php foreach ($riwayat as $p): ?>
        <tr>
          <td><?= $p['waktu_putus'] ?></td>
          <td><?= htmlspecialchars($p['diminta_oleh']) ?></td>
          <td><?= label($p) ?></td>
          <td><?= $p['status'] ?></td>
        </tr>
        <?php endforeach; ?>
      </table></div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>