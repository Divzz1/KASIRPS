<?php
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

$data = $db->query("SELECT m.*, COUNT(s.id) AS jml_sesi
                    FROM member m LEFT JOIN sesi s ON s.member_id = m.id AND s.waktu_selesai IS NOT NULL
                    WHERE m.aktif = 1 GROUP BY m.id ORDER BY m.id DESC");
$pending_hapus = array_column($db->query("SELECT member_id FROM permintaan WHERE jenis='hapus_member' AND status='menunggu'")->fetch_all(MYSQLI_ASSOC), 'member_id');
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Member - D1vzz PlayStation</title><link rel="stylesheet" href="style.css"><script src="tema.js"></script></head>
<body>
  <header>
    <h1><svg class="logo-ps" viewBox="0 0 74 16" width="56" height="12" fill="none" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"><path class="s1" d="M8 2 L14 13 L2 13 Z"/><circle class="s2" cx="28" cy="8" r="5.5"/><path class="s3" d="M40 3 L51 13 M51 3 L40 13"/><rect class="s4" x="61" y="3" width="10" height="10" rx="1"/></svg>D1vzz PlayStation</h1>
    <nav>
      <a href="index.php">Dashboard</a>
      <a href="member.php" class="aktif">Member</a>
      <a href="riwayat.php">Riwayat</a>
      <?= menu_tambahan($db) ?>
      <a href="logout.php">Keluar</a>
      <button id="tema" class="tema" type="button">Mode gelap</button>
    </nav>
  </header>
  <main>
    <?php pesan(); ?>
    <div class="kotak">
      <h2>Daftar member baru</h2>
      <p>Biaya pendaftaran Rp <?= number_format(BIAYA_DAFTAR, 0, ',', '.') ?> (sekali bayar). Member mendapat diskon <?= DISKON_MEMBER ?>% untuk setiap sesi.</p>
      <form action="aksi.php" method="post" class="form-baris" onsubmit="return confirm('Pastikan biaya pendaftaran sudah dibayar. Lanjutkan?')">
        <input type="hidden" name="aksi" value="daftar_member">
        <div><label>Nama</label><input type="text" name="nama" required></div>
        <div><label>No. HP</label><input type="text" name="no_hp"></div>
        <button class="btn btn-start">Daftar (Rp <?= number_format(BIAYA_DAFTAR, 0, ',', '.') ?>)</button>
      </form>
    </div>

    <div class="tabel-wrap">
    <table>
      <tr><th>No</th><th>Nama</th><th>No. HP</th><th>Terdaftar</th><th>Sesi selesai</th><th></th></tr>
      <?php $no = 1; while ($r = $data->fetch_assoc()): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($r['nama']) ?></td>
        <td><?= htmlspecialchars($r['no_hp']) ?></td>
        <td><?= $r['tgl_daftar'] ?></td>
        <td><?= $r['jml_sesi'] ?></td>
        <td class="sel-aksi">
          <?php if (is_admin()): ?>
            <form action="aksi.php" method="post" onsubmit="return confirm('Hapus member ini? Riwayat transaksinya tetap tersimpan.')">
              <input type="hidden" name="aksi" value="hapus_member"><input type="hidden" name="member_id" value="<?= $r['id'] ?>">
              <button class="btn-kecil">Hapus</button>
            </form>
          <?php elseif (in_array($r['id'], $pending_hapus)): ?>
            <span class="menunggu">Menunggu admin</span>
          <?php else: ?>
            <form action="aksi.php" method="post" onsubmit="return confirm('Ajukan penghapusan member ini ke admin?')">
              <input type="hidden" name="aksi" value="minta_hapus_member"><input type="hidden" name="member_id" value="<?= $r['id'] ?>">
              <button class="btn-kecil">Ajukan hapus</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
    </div>
  </main>
</body>
</html>