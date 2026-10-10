<?php
// Endpoint JSON publik untuk bagian "Status Layanan" di index.php.
// Hanya mengirim angka agregat dan KATEGORI + status (tanpa lokasi, nama pelapor, atau isi laporan).
//
// ASUMSI skema (sesuaikan kalau berbeda):
//   tabel  laporan  : id, kategori_id, status, created_at
//   tabel  kategori : id, nama
//   status          : 'diproses' = sedang ditangani, 'selesai' = tuntas
//   koneksi PDO     : variabel $pdo dari init.php
require "init.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

$hasil = [
  "sistem_ok"      => false,
  "hari_ini"       => 0,
  "diproses"       => 0,
  "total"          => 0,
  "selesai"        => 0,
  "persen_selesai" => 0,
  "aktivitas"      => [],
];

try {
  // Kalau query ini berhasil, koneksi database dianggap sehat
  $pdo->query("SELECT 1");
  $hasil["sistem_ok"] = true;

  $hasil["hari_ini"] = (int) $pdo->query(
    "SELECT COUNT(*) FROM laporan WHERE DATE(created_at) = CURDATE()"
  )->fetchColumn();

  $hasil["diproses"] = (int) $pdo->query(
    "SELECT COUNT(*) FROM laporan WHERE status = 'diproses'"
  )->fetchColumn();

  $hasil["total"] = (int) $pdo->query("SELECT COUNT(*) FROM laporan")->fetchColumn();

  $hasil["selesai"] = (int) $pdo->query(
    "SELECT COUNT(*) FROM laporan WHERE status = 'selesai'"
  )->fetchColumn();

  $hasil["persen_selesai"] = $hasil["total"] > 0
    ? (int) round($hasil["selesai"] / $hasil["total"] * 100)
    : 0;

  // 5 aktivitas terbaru, tanpa batas waktu
  $baris = $pdo->query(
    "SELECT k.nama AS kategori, l.status,
            TIMESTAMPDIFF(SECOND, l.created_at, NOW()) AS detik_lalu
       FROM laporan l
       JOIN kategori k ON k.id = l.kategori_id
   ORDER BY l.created_at DESC
      LIMIT 5"
  )->fetchAll(PDO::FETCH_ASSOC);

  foreach ($baris as $b) {
    $hasil["aktivitas"][] = [
      "kategori"   => $b["kategori"],
      "status"     => $b["status"],
      "detik_lalu" => max(0, (int) $b["detik_lalu"]),
    ];
  }
} catch (Throwable $e) {
  // sistem_ok tetap false; detail error tidak dibocorkan ke publik
}

echo json_encode($hasil);