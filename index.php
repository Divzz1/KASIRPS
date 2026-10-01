<?php
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

// Semua unit + sesi yang sedang berjalan (waktu_selesai masih kosong)
$sql = "SELECT u.*, s.id AS sesi_id, UNIX_TIMESTAMP(s.waktu_mulai) AS mulai, s.paket_menit, s.diskon_persen, m.nama AS member_nama
        FROM unit u
        LEFT JOIN sesi s ON s.unit_id = u.id AND s.waktu_selesai IS NULL
        LEFT JOIN member m ON m.id = s.member_id
        ORDER BY u.id";
$units = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
$members = $db->query("SELECT id, nama FROM member WHERE aktif = 1 ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

// Ringkasan untuk bagian atas dashboard
$total   = count($units);
$dipakai = count(array_filter($units, fn($u) => $u['status'] == 'dipakai'));
// Pendapatan hari ini = sesi selesai + biaya pendaftaran member hari ini
$kas = pendapatan_sejak($db, batas_kas($db));
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - D1vzz PlayStation</title><link rel="stylesheet" href="style.css"><script src="tema.js"></script></head>
<body>
  <header>
    <h1><svg class="logo-ps" viewBox="0 0 74 16" width="56" height="12" fill="none" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"><path class="s1" d="M8 2 L14 13 L2 13 Z"/><circle class="s2" cx="28" cy="8" r="5.5"/><path class="s3" d="M40 3 L51 13 M51 3 L40 13"/><rect class="s4" x="61" y="3" width="10" height="10" rx="1"/></svg>D1vzz PlayStation</h1>
    <nav>
      <a href="index.php" class="aktif">Dashboard</a>
      <a href="member.php">Member</a>
      <a href="riwayat.php">Riwayat</a>
      <?= menu_tambahan($db) ?>
      <a href="logout.php">Keluar</a>
      <button id="tema" class="tema" type="button">Mode gelap</button>
    </nav>
  </header>

  <main>
    <?php pesan(); ?>
    <section class="ringkasan-atas">
      <div><span>Sedang dipakai</span><strong><?= $dipakai ?> <small>dari <?= $total ?> unit</small></strong></div>
      <div><span>Unit kosong</span><strong><?= $total - $dipakai ?></strong></div>
      <div><span>Pendapatan hari ini</span><strong>Rp <?= number_format($kas['total'], 0, ',', '.') ?></strong></div>
      <div class="kanan"><button id="btn-suara" class="btn-suara">Aktifkan alarm</button></div>
    </section>

    <!-- Waktu server dikirim ke JavaScript supaya timer akurat -->
    <div class="grid" data-sekarang="<?= time() ?>">
      <?php foreach ($units as $u): ?>
        <div class="kartu <?= $u['status'] ?> <?= strtolower($u['jenis']) ?>">
          <div class="baris-atas">
            <h2><?= htmlspecialchars($u['nama']) ?></h2>
            <span class="status"><?= $u['status'] == 'dipakai' ? 'Dipakai' : 'Kosong' ?></span>
          </div>
          <p class="jenis"><span class="chip"><?= $u['jenis'] ?></span>Rp <?= number_format($u['tarif_per_jam'], 0, ',', '.') ?>/jam</p>

          <?php if ($u['status'] == 'dipakai'): ?>
            <p class="paket-info"><?= $u['paket_menit'] > 0 ? 'Paket ' . $u['paket_menit'] . ' menit' : 'Bebas, bayar sesuai durasi' ?></p>
            <?php if ($u['member_nama']): ?><p class="paket-info">Member: <?= htmlspecialchars($u['member_nama']) ?> (diskon <?= (int)$u['diskon_persen'] ?>%)</p><?php endif; ?>
            <div class="timer" data-mulai="<?= $u['mulai'] ?>" data-paket="<?= (int)$u['paket_menit'] ?>" data-tarif="<?= $u['tarif_per_jam'] ?>" data-diskon="<?= (int)$u['diskon_persen'] ?>">00:00:00</div>
            <div class="biaya">Rp 0</div>
            <?php if ($u['paket_menit'] > 0): ?>
            <form action="aksi.php" method="post" class="form-tambah">
              <input type="hidden" name="aksi" value="tambah_waktu">
              <input type="hidden" name="sesi_id" value="<?= $u['sesi_id'] ?>">
              <select name="tambah">
                <option value="30">+ 30 menit</option>
                <option value="60">+ 1 jam</option>
                <option value="120">+ 2 jam</option>
              </select>
              <button class="btn btn-tambah">Tambah waktu</button>
            </form>
            <?php endif; ?>
            <form action="aksi.php" method="post" onsubmit="return confirm('Selesaikan sesi ini?')">
              <input type="hidden" name="aksi" value="selesai">
              <input type="hidden" name="sesi_id" value="<?= $u['sesi_id'] ?>">
              <button class="btn btn-stop">Selesai</button>
            </form>
          <?php else: ?>
            <form action="aksi.php" method="post" class="form-mulai">
              <input type="hidden" name="aksi" value="mulai">
              <input type="hidden" name="unit_id" value="<?= $u['id'] ?>">
              <label>Pelanggan</label>
              <select name="member_id">
                <option value="0">Umum</option>
                <?php foreach ($members as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?> (member)</option>
                <?php endforeach; ?>
              </select>
              <label>Durasi</label>
              <select name="paket">
                <option value="60">1 jam</option>
                <option value="120">2 jam</option>
                <option value="180">3 jam</option>
                <option value="30">30 menit</option>
                <option value="0">Bebas (per menit)</option>
              </select>
              <button class="btn btn-start">Mulai</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
  <script src="script.js"></script>
</body>
</html>