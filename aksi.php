<?php
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

$aksi = $_POST['aksi'] ?? '';
$pengguna = $_SESSION['admin'];

function kembali($url, $pesan = '') {
  if ($pesan != '') { $_SESSION['pesan'] = $pesan; }
  header("Location: $url"); exit;
}
function lakukan_reset($db) {
  $kas = pendapatan_sejak($db, batas_kas($db));
  $total = (int)$kas['total'];
  $db->query("INSERT INTO tutup_kas (waktu, total) VALUES (NOW(), $total)");
}
function lakukan_hapus_member($db, $id) {
  $id = (int)$id;
  $db->query("UPDATE member SET aktif = 0 WHERE id = $id");   // riwayat tetap utuh
}

// Aksi yang hanya boleh dilakukan admin (dicek di sisi server)
if (in_array($aksi, ['reset_kas', 'hapus_member', 'putuskan', 'tambah_kasir', 'hapus_kasir']) && !is_admin()) {
  kembali('index.php', 'Hanya admin yang boleh melakukan ini.');
}

// ---------- Aksi kasir & admin ----------
if ($aksi == 'mulai') {
  $unit_id = (int)$_POST['unit_id'];
  $paket = (int)($_POST['paket'] ?? 0);
  $member_id = (int)($_POST['member_id'] ?? 0);
  $diskon = $member_id > 0 ? DISKON_MEMBER : 0;
  $db->query("INSERT INTO sesi (unit_id, waktu_mulai, paket_menit, member_id, diskon_persen)
              VALUES ($unit_id, NOW(), $paket, NULLIF($member_id,0), $diskon)");
  $db->query("UPDATE unit SET status='dipakai' WHERE id=$unit_id");
  kembali('index.php');
}

if ($aksi == 'tambah_waktu') {
  $sesi_id = (int)$_POST['sesi_id'];
  $tambah = (int)$_POST['tambah'];
  if (in_array($tambah, [30, 60, 120])) {
    $db->query("UPDATE sesi SET paket_menit = paket_menit + $tambah
                WHERE id = $sesi_id AND waktu_selesai IS NULL AND paket_menit > 0");
  }
  kembali('index.php');
}

if ($aksi == 'selesai') {
  $sesi_id = (int)$_POST['sesi_id'];
  $s = $db->query("SELECT s.unit_id, s.paket_menit, s.diskon_persen, u.tarif_per_jam,
                   TIMESTAMPDIFF(SECOND, s.waktu_mulai, NOW()) AS detik
                   FROM sesi s JOIN unit u ON u.id = s.unit_id
                   WHERE s.id = $sesi_id AND s.waktu_selesai IS NULL")->fetch_assoc();
  if ($s) {
    $menit = max(1, (int)ceil($s['detik'] / 60));
    $tarif = $s['tarif_per_jam'];
    $paket = (int)$s['paket_menit'];
    if ($paket > 0) {
      $lebih = max(0, $menit - $paket);
      $biaya = (int)ceil($paket * $tarif / 60) + (int)ceil($lebih * $tarif / 60);
    } else {
      $biaya = (int)ceil($menit * $tarif / 60);
    }
    $biaya = (int)ceil($biaya * (100 - (int)$s['diskon_persen']) / 100);
    $db->query("UPDATE sesi SET waktu_selesai=NOW(), durasi_menit=$menit, total_biaya=$biaya WHERE id=$sesi_id");
    $db->query("UPDATE unit SET status='kosong' WHERE id={$s['unit_id']}");
  }
  kembali('index.php');
}

if ($aksi == 'daftar_member') {
  $nama = trim($_POST['nama'] ?? '');
  $hp = trim($_POST['no_hp'] ?? '');
  if ($nama != '') {
    $biaya = BIAYA_DAFTAR;
    $stmt = $db->prepare("INSERT INTO member (nama, no_hp, tgl_daftar, biaya_daftar) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param('ssi', $nama, $hp, $biaya);
    $stmt->execute();
    kembali('member.php', 'Member berhasil didaftarkan.');
  }
  kembali('member.php');
}

// ---------- Kasir mengajukan permintaan ke admin ----------
if ($aksi == 'minta_reset' || $aksi == 'minta_hapus_member') {
  $jenis = $aksi == 'minta_reset' ? 'reset_kas' : 'hapus_member';
  $mid = $jenis == 'hapus_member' ? (int)($_POST['member_id'] ?? 0) : 0;
  $tujuan = $jenis == 'reset_kas' ? 'riwayat.php' : 'member.php';
  if (ada_pending($db, $jenis, $mid)) { kembali($tujuan, 'Permintaan yang sama masih menunggu persetujuan admin.'); }
  $stmt = $db->prepare("INSERT INTO permintaan (jenis, member_id, diminta_oleh, waktu_minta) VALUES (?, NULLIF(?,0), ?, NOW())");
  $stmt->bind_param('sis', $jenis, $mid, $pengguna);
  $stmt->execute();
  kembali($tujuan, 'Permintaan dikirim ke admin. Menunggu persetujuan.');
}

// ---------- Khusus admin ----------
if ($aksi == 'reset_kas') {
  lakukan_reset($db);
  kembali('riwayat.php', 'Pendapatan hari ini sudah direset.');
}

if ($aksi == 'hapus_member') {
  lakukan_hapus_member($db, $_POST['member_id'] ?? 0);
  kembali('member.php', 'Member dihapus.');
}

if ($aksi == 'putuskan') {
  $id = (int)$_POST['id'];
  $setuju = ($_POST['keputusan'] ?? '') == 'setuju';
  $r = $db->query("SELECT * FROM permintaan WHERE id = $id AND status = 'menunggu'")->fetch_assoc();
  if ($r) {
    if ($setuju) {
      if ($r['jenis'] == 'reset_kas') { lakukan_reset($db); }
      else { lakukan_hapus_member($db, $r['member_id']); }
    }
    $status = $setuju ? 'disetujui' : 'ditolak';
    $stmt = $db->prepare("UPDATE permintaan SET status = ?, waktu_putus = NOW() WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
  }
  kembali('persetujuan.php', $setuju ? 'Permintaan disetujui dan sudah dijalankan.' : 'Permintaan ditolak.');
}

if ($aksi == 'tambah_kasir') {
  $u = trim($_POST['username'] ?? '');
  $p = $_POST['password'] ?? '';
  $ada = (int)$db->query("SELECT COUNT(*) AS c FROM admin WHERE peran='kasir'")->fetch_assoc()['c'];
  if ($ada > 0) { kembali('akun.php', 'Akun kasir sudah ada. Hapus dulu jika ingin menggantinya.'); }
  if ($u === '' || strlen($u) > 30 || strlen($p) < 6) { kembali('akun.php', 'Username wajib diisi dan password minimal 6 karakter.'); }
  $cek = $db->prepare("SELECT id FROM admin WHERE username = ?");
  $cek->bind_param('s', $u);
  $cek->execute();
  if ($cek->get_result()->num_rows > 0) { kembali('akun.php', 'Username sudah dipakai.'); }
  $hash = password_hash($p, PASSWORD_DEFAULT);
  $stmt = $db->prepare("INSERT INTO admin (username, password, peran) VALUES (?, ?, 'kasir')");
  $stmt->bind_param('ss', $u, $hash);
  $stmt->execute();
  kembali('akun.php', 'Akun kasir berhasil dibuat.');
}

if ($aksi == 'hapus_kasir') {
  $db->query("DELETE FROM admin WHERE peran = 'kasir'");
  kembali('akun.php', 'Akun kasir dihapus.');
}

kembali('index.php');