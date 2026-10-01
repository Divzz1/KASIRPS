<?php
// Menyambungkan PHP ke database MySQL/MariaDB
date_default_timezone_set('Asia/Jakarta');

// Pengaturan member (ubah angkanya di sini)
define('BIAYA_DAFTAR', 20000);   // biaya daftar member (sekali bayar)
define('DISKON_MEMBER', 20);     // diskon member dalam persen

$db = new mysqli('localhost', 'root', '', 'rental_ps');
if ($db->connect_error) { die('Koneksi gagal: ' . $db->connect_error); }
$db->query("SET time_zone = '+07:00'");
session_start();

// Batas awal "pendapatan hari ini": 00:00 hari ini atau waktu reset terakhir (yang lebih baru)
function batas_kas($db) {
  $r = $db->query("SELECT GREATEST(TIMESTAMP(CURDATE()), COALESCE(MAX(waktu), TIMESTAMP(CURDATE()))) AS b FROM tutup_kas")->fetch_assoc();
  return $r['b'];
}

// Total pendapatan (sesi selesai + pendaftaran member) sejak waktu $batas
function pendapatan_sejak($db, $batas) {
  $r = $db->query("SELECT
    (SELECT COALESCE(SUM(total_biaya),0) FROM sesi WHERE waktu_selesai >= '$batas') AS sesi_total,
    (SELECT COUNT(*) FROM sesi WHERE waktu_selesai >= '$batas') AS sesi_jml,
    (SELECT COALESCE(SUM(biaya_daftar),0) FROM member WHERE tgl_daftar >= '$batas') AS member_total,
    (SELECT COUNT(*) FROM member WHERE tgl_daftar >= '$batas') AS member_jml")->fetch_assoc();
  $r['total'] = $r['sesi_total'] + $r['member_total'];
  return $r;
}

// ---------- Hak akses ----------
// Sesi lama (tanpa peran) dipaksa login ulang
if (isset($_SESSION['admin']) && !isset($_SESSION['peran'])) {
  $_SESSION = []; session_destroy(); header('Location: login.php'); exit;
}
function is_admin() { return ($_SESSION['peran'] ?? '') === 'admin'; }
function wajib_admin() {
  if (!is_admin()) { $_SESSION['pesan'] = 'Halaman ini hanya untuk admin.'; header('Location: index.php'); exit; }
}
// Kasir yang akunnya sudah dihapus admin otomatis dikeluarkan
if (($_SESSION['peran'] ?? '') === 'kasir') {
  $cek = $db->prepare("SELECT id FROM admin WHERE username=? AND peran='kasir'");
  $cek->bind_param('s', $_SESSION['admin']);
  $cek->execute();
  if ($cek->get_result()->num_rows == 0) { session_destroy(); header('Location: login.php'); exit; }
}

function pesan() {
  if (!empty($_SESSION['pesan'])) {
    echo '<div class="pesan">' . htmlspecialchars($_SESSION['pesan']) . '</div>';
    unset($_SESSION['pesan']);
  }
}
function jml_menunggu($db) {
  return (int)$db->query("SELECT COUNT(*) AS c FROM permintaan WHERE status='menunggu'")->fetch_assoc()['c'];
}
function ada_pending($db, $jenis, $member_id = 0) {
  $stmt = $db->prepare("SELECT COUNT(*) AS c FROM permintaan WHERE jenis=? AND status='menunggu' AND COALESCE(member_id,0)=?");
  $stmt->bind_param('si', $jenis, $member_id);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc()['c'] > 0;
}
// Menu tambahan di header: link khusus admin + nama & peran pengguna
function menu_tambahan($db, $aktif = '') {
  $h = '';
  if (is_admin()) {
    $n = jml_menunggu($db);
    $h .= '<a href="persetujuan.php"' . ($aktif == 'persetujuan' ? ' class="aktif"' : '') . '>Persetujuan'
        . ($n > 0 ? ' <span class="lencana">' . $n . '</span>' : '') . '</a>';
    $h .= '<a href="akun.php"' . ($aktif == 'akun' ? ' class="aktif"' : '') . '>Akun</a>';
  }
  $h .= '<span class="peran">' . htmlspecialchars($_SESSION['admin'] ?? '') . ' &middot; ' . (is_admin() ? 'Admin' : 'Kasir') . '</span>';
  return $h;
}