<?php
function ambilRekap(PDO $pdo, bool $admin, int $userId, string $mulai, string $akhir): array {
  $sql = "SELECT l.id, l.judul, lo.nama AS lokasi, k.nama AS kategori,
                 l.prioritas, l.status, DATE(l.created_at) AS tanggal, u.nama AS pelapor
          FROM laporan l
          JOIN lokasi   lo ON lo.id = l.lokasi_id
          JOIN kategori k  ON k.id  = l.kategori_id
          JOIN users    u  ON u.id  = l.user_id
          WHERE DATE(l.created_at) BETWEEN ? AND ?";
  $param = [$mulai, $akhir];
 
  if (!$admin) {                       // user hanya boleh melihat laporannya sendiri
    $sql    .= " AND l.user_id = ?";
    $param[] = $userId;
  }
 
  $stmt = $pdo->prepare($sql . " ORDER BY l.created_at DESC");
  $stmt->execute($param);
  return $stmt->fetchAll();
}