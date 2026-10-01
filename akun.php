<?php
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
wajib_admin();

$kasir = $db->query("SELECT username FROM admin WHERE peran = 'kasir' LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Akun - D1vzz PlayStation</title><link rel="stylesheet" href="style.css"><script src="tema.js"></script></head>
<body>
  <header>
    <h1><svg class="logo-ps" viewBox="0 0 74 16" width="56" height="12" fill="none" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"><path class="s1" d="M8 2 L14 13 L2 13 Z"/><circle class="s2" cx="28" cy="8" r="5.5"/><path class="s3" d="M40 3 L51 13 M51 3 L40 13"/><rect class="s4" x="61" y="3" width="10" height="10" rx="1"/></svg>D1vzz PlayStation</h1>
    <nav>
      <a href="index.php">Dashboard</a>
      <a href="member.php">Member</a>
      <a href="riwayat.php">Riwayat</a>
      <?= menu_tambahan($db, 'akun') ?>
      <a href="logout.php">Keluar</a>
      <button id="tema" class="tema" type="button">Mode gelap</button>
    </nav>
  </header>
  <main>
    <?php pesan(); ?>

    <div class="kotak">
      <h2>Akun kasir</h2>
      <p>Hanya boleh ada satu akun kasir. Kasir dapat memulai dan menyelesaikan sesi, menambah waktu, dan mendaftarkan member. Reset pendapatan dan penghapusan member harus lewat persetujuan admin.</p>
      <?php if ($kasir): ?>
        <p>Akun kasir saat ini: <strong><?= htmlspecialchars($kasir['username']) ?></strong></p>
        <form action="aksi.php" method="post" onsubmit="return confirm('Hapus akun kasir ini? Kasir akan langsung keluar dari aplikasi.')">
          <input type="hidden" name="aksi" value="hapus_kasir">
          <button class="btn btn-stop btn-inline">Hapus akun kasir</button>
        </form>
      <?php else: ?>
        <form action="aksi.php" method="post" class="form-baris" autocomplete="off">
          <input type="hidden" name="aksi" value="tambah_kasir">
          <div><label>Username kasir</label><input type="text" name="username" maxlength="30" required></div>
          <div><label>Password (minimal 6 karakter)</label><input type="password" name="password" minlength="6" required></div>
          <button class="btn btn-start">Buat akun kasir</button>
        </form>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>