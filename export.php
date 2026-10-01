<?php
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
wajib_admin();

$dari = $_GET['dari'] ?? date('Y-m-d');
$sampai = $_GET['sampai'] ?? $dari;
foreach ([$dari, $sampai] as $t) {
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) { die('Format tanggal salah'); }
}
if ($sampai < $dari) { [$dari, $sampai] = [$sampai, $dari]; }

$baris = [];

// 1) Sesi yang sudah selesai
$q = $db->prepare("SELECT s.waktu_selesai AS waktu, u.nama AS unit, u.jenis, m.nama AS member,
                   s.paket_menit, s.durasi_menit, s.diskon_persen, s.total_biaya
                   FROM sesi s JOIN unit u ON u.id = s.unit_id LEFT JOIN member m ON m.id = s.member_id
                   WHERE s.waktu_selesai IS NOT NULL AND DATE(s.waktu_selesai) BETWEEN ? AND ?");
$q->bind_param('ss', $dari, $sampai);
$q->execute();
foreach ($q->get_result() as $r) {
  $baris[] = [$r['waktu'], 'Sesi', $r['unit'] . ' (' . $r['jenis'] . ')', $r['member'] ?: 'Umum',
              $r['paket_menit'] > 0 ? $r['paket_menit'] . ' menit' : 'Bebas', $r['durasi_menit'],
              $r['diskon_persen'], (int)$r['total_biaya']];
}

// 2) Pendaftaran member
$q = $db->prepare("SELECT tgl_daftar, nama, biaya_daftar FROM member WHERE DATE(tgl_daftar) BETWEEN ? AND ?");
$q->bind_param('ss', $dari, $sampai);
$q->execute();
foreach ($q->get_result() as $r) {
  $baris[] = [$r['tgl_daftar'], 'Pendaftaran member', '-', $r['nama'], '-', '', '', (int)$r['biaya_daftar']];
}

usort($baris, fn($a, $b) => strcmp($a[0], $b[0]));

// Cegah teks yang diawali = + - @ dibaca sebagai rumus oleh Excel
function aman($v) { return (is_string($v) && preg_match('/^[=+\-@]/', $v)) ? "'" . $v : $v; }

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="pendapatan_' . $dari . '_sd_' . $sampai . '.csv"');
echo "\xEF\xBB\xBF";   // supaya Excel membaca UTF-8 dengan benar
$out = fopen('php://output', 'w');
$kolom = ['Waktu', 'Jenis', 'Unit', 'Pelanggan', 'Paket', 'Durasi (menit)', 'Diskon (%)', 'Total (Rp)'];
fputcsv($out, $kolom, ';', '"', '');

$total = 0;
foreach ($baris as $b) {
  $total += $b[7];
  fputcsv($out, array_map('aman', $b), ';', '"', '');
}
fputcsv($out, ['', '', '', '', '', '', 'TOTAL', $total], ';', '"', '');
fclose($out);