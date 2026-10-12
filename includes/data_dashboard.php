<?php
function jalankan(PDO $pdo, string $sql, array $param = []): PDOStatement {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($param);
  return $stmt;
}

function ambilDashboard(PDO $pdo, bool $admin, int $userId): array {
  $where = $admin ? "1" : "l.user_id = ?";
  $param = $admin ? [] : [$userId];

  // ---------- 1. Kartu ringkasan + donut status ----------
  $k = jalankan($pdo,
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(l.status = 'dilaporkan'), 0) AS dilaporkan,
            COALESCE(SUM(l.status = 'diproses'), 0)   AS diproses,
            COALESCE(SUM(l.status = 'selesai'), 0)    AS selesai
     FROM laporan l WHERE $where", $param)->fetch();
  $k = array_map("intval", $k);

  $data = [
    "kartu"  => $k,
    "status" => [
      "labels" => ["Dilaporkan", "Diproses", "Selesai"],
      "data"   => [$k["dilaporkan"], $k["diproses"], $k["selesai"]],
    ],
  ];

  // ---------- 2. Laporan per bulan (6 bulan terakhir) ----------
  $namaBulan = ["", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  $mulai     = date("Y-m-01", strtotime("first day of -5 months"));

  $perBulan = jalankan($pdo,
    "SELECT DATE_FORMAT(l.created_at, '%Y-%m') AS bulan, COUNT(*) AS jumlah
     FROM laporan l
     WHERE $where AND l.created_at >= ?
     GROUP BY bulan",
    array_merge($param, [$mulai]))->fetchAll(PDO::FETCH_KEY_PAIR);   // ["2026-10" => 3, ...]

  $labels = [];
  $jumlah = [];
  for ($i = 5; $i >= 0; $i--) {                     // bulan tanpa laporan tetap tampil sebagai 0
    $waktu    = strtotime("first day of -$i months");
    $labels[] = $namaBulan[(int) date("n", $waktu)];
    $jumlah[] = (int) ($perBulan[date("Y-m", $waktu)] ?? 0);
  }
  $data["bulanan"] = ["labels" => $labels, "data" => $jumlah];

  // ---------- 3a. Khusus USER ----------
  if (!$admin) {
    $data["terbaru"] = jalankan($pdo,
      "SELECT l.id, l.judul, lo.nama AS lokasi, l.status, DATE(l.created_at) AS tanggal
       FROM laporan l
       JOIN lokasi lo ON lo.id = l.lokasi_id
       WHERE l.user_id = ?
       ORDER BY l.created_at DESC
       LIMIT 5", [$userId])->fetchAll();

    return $data;
  }

  // ---------- 3b. Khusus ADMIN ----------
  // Laporan per kategori (kategori tanpa laporan tetap tampil sebagai 0)
  $baris = jalankan($pdo,
    "SELECT k.nama, COUNT(l.id) AS jumlah
     FROM kategori k
     LEFT JOIN laporan l ON l.kategori_id = k.id
     GROUP BY k.id, k.nama
     ORDER BY jumlah DESC")->fetchAll(PDO::FETCH_NUM);
  $data["kategori"] = [
    "labels" => array_column($baris, 0),
    "data"   => array_map("intval", array_column($baris, 1)),
  ];

  // 5 lokasi paling sering dilaporkan -> [[nama, jumlah], ...]
  $baris = jalankan($pdo,
    "SELECT lo.nama, COUNT(*) AS jumlah
     FROM laporan l
     JOIN lokasi lo ON lo.id = l.lokasi_id
     GROUP BY lo.id, lo.nama
     ORDER BY jumlah DESC
     LIMIT 5")->fetchAll(PDO::FETCH_NUM);
  $data["top_lokasi"] = array_map(fn($r) => [$r[0], (int) $r[1]], $baris);

  // Kinerja penanganan
  $batasTerlambat = 3;     // laporan belum selesai lebih dari 3 hari = terlambat

  $rataHari = jalankan($pdo,
    "SELECT AVG(DATEDIFF(r.created_at, l.created_at))
     FROM riwayat_status r
     JOIN laporan l ON l.id = r.laporan_id
     WHERE r.status = 'selesai'")->fetchColumn();

  $terlambat = jalankan($pdo,
    "SELECT COUNT(*) FROM laporan
     WHERE status <> 'selesai' AND DATEDIFF(NOW(), created_at) > ?", [$batasTerlambat])->fetchColumn();

  $data["kinerja"] = [
    "rata_hari" => round((float) $rataHari, 1),
    "terlambat" => (int) $terlambat,
  ];

  // Perlu perhatian: yang belum selesai, prioritas tinggi dan paling lama di atas
  $data["perhatian"] = jalankan($pdo,
    "SELECT l.id, l.judul, lo.nama AS lokasi, l.prioritas,
            DATEDIFF(NOW(), l.created_at) AS umur
     FROM laporan l
     JOIN lokasi lo ON lo.id = l.lokasi_id
     WHERE l.status <> 'selesai'
     ORDER BY FIELD(l.prioritas, 'tinggi', 'sedang', 'rendah'), umur DESC
     LIMIT 5")->fetchAll();

  return $data;
}