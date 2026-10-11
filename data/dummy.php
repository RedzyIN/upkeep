<?php
// =====================================================================
//  DATA DUMMY - khusus fase front-end.
//  Hapus file ini setelah dashboard dihubungkan ke database.
//  Cari semua titik dummy dengan:   grep -rn "DUMMY" .
// =====================================================================

const MODE_PREVIEW = true;   // DUMMY: ubah ke false (atau hapus file ini) saat login sungguhan sudah jadi

// Login palsu supaya dashboard bisa dilihat tanpa halaman login.
//   dashboard.php?as=user   -> tampil sebagai user
//   dashboard.php?as=admin  -> tampil sebagai admin
function previewLogin() {                                    // DUMMY
  if (!MODE_PREVIEW) return;
  if (!isset($_GET["as"]) && userLogin()) return;            // sudah "login", biarkan
  $_SESSION["user"] = (($_GET["as"] ?? "user") === "admin")
    ? ["id" => 1, "nama" => "Administrator",  "role" => "admin"]
    : ["id" => 2, "nama" => "Faatih Alfaredzy", "role" => "user"];
}

// ---------- Dashboard USER ----------
// Nanti diganti: query ke tabel laporan WHERE user_id = ?  (nama fungsi & bentuk array dipertahankan)
function dataDashboardUser(): array {                        // DUMMY
  return [
    "kartu"   => ["total" => 8, "dilaporkan" => 2, "diproses" => 2, "selesai" => 4],
    "status"  => ["labels" => ["Dilaporkan", "Diproses", "Selesai"], "data" => [2, 2, 4]],
    "bulanan" => ["labels" => ["Mei", "Jun", "Jul", "Agu", "Sep", "Okt"], "data" => [1, 2, 0, 3, 1, 1]],
    "perlu_rating" => 2,
    "terbaru" => [
      ["id" => 8, "judul" => "Tempat sampah penuh",     "lokasi" => "Kantin",                                   "status" => "dilaporkan", "tanggal" => "2026-10-10"],
      ["id" => 7, "judul" => "AC bocor ke meja",        "lokasi" => "AE 109",                                   "status" => "dilaporkan", "tanggal" => "2026-10-09"],
      ["id" => 5, "judul" => "Kursi patah",             "lokasi" => "Laboratorium Sistem Cerdas - Gedung AE",   "status" => "diproses",   "tanggal" => "2026-10-02"],
      ["id" => 4, "judul" => "Kabel LAN putus",         "lokasi" => "Laboratorium Embedded - Gedung AE",        "status" => "diproses",   "tanggal" => "2026-09-27"],
      ["id" => 3, "judul" => "Keran wastafel bocor",    "lokasi" => "Toilet Lantai 1 Gedung A",                 "status" => "selesai",    "tanggal" => "2026-09-16"],
    ],
  ];
}

// ---------- Dashboard ADMIN ----------
// Nanti diganti: query COUNT/GROUP BY ke seluruh tabel laporan
function dataDashboardAdmin(): array {                       // DUMMY
  return [
    "kartu"   => ["total" => 128, "dilaporkan" => 14, "diproses" => 22, "selesai" => 92],
    "status"  => ["labels" => ["Dilaporkan", "Diproses", "Selesai"], "data" => [14, 22, 92]],
    "bulanan" => ["labels" => ["Mei", "Jun", "Jul", "Agu", "Sep", "Okt"], "data" => [14, 19, 16, 24, 21, 34]],
    "kategori" => [
      "labels" => ["Listrik", "AC", "Jaringan & WiFi", "Furnitur", "Air & Sanitasi", "Kebersihan"],
      "data"   => [31, 28, 22, 19, 17, 11],
    ],
    "top_lokasi" => [
      ["Gedung B - Lab Komputer", 18], ["Gedung A - Ruang 201", 15], ["Perpustakaan", 12],
      ["Toilet Lantai 1 Gedung A", 9], ["Kantin", 7],
    ],
    "kinerja" => ["rata_hari" => 3.2, "rating" => 4.3, "terlambat" => 5],
    "perhatian" => [
      ["id" => 7,  "judul" => "AC bocor ke meja",        "lokasi" => "Gedung B - Lab Komputer", "prioritas" => "tinggi", "umur" => 2],
      ["id" => 4,  "judul" => "WiFi perpustakaan putus", "lokasi" => "Perpustakaan",            "prioritas" => "tinggi", "umur" => 14],
      ["id" => 12, "judul" => "Stop kontak mati",        "lokasi" => "Gedung A - Ruang 201",    "prioritas" => "tinggi", "umur" => 6],
      ["id" => 15, "judul" => "Lampu koridor padam",     "lokasi" => "Gedung A - Lantai 2",     "prioritas" => "sedang", "umur" => 9],
    ],
  ];
}

// ---------- Daftar LAPORAN (laporan.php) ----------
// Urutan id sama dengan database.sql: 1 Listrik, 2 Air & Sanitasi, 3 AC, 4 Furnitur, 5 Jaringan & WiFi, 6 Kebersihan
function dataKategoriList(): array {                         // DUMMY: nanti SELECT id, nama FROM kategori
  return [1 => "Listrik", 2 => "Air & Sanitasi", 3 => "AC", 4 => "Furnitur", 5 => "Jaringan & WiFi", 6 => "Kebersihan"];
}
 
// Filter + halaman dikerjakan di sini. Nanti diganti query:  WHERE ... LIMIT ? OFFSET ?
// Bentuk nilai kembalian ("rows", "total", "hitung") dipertahankan supaya laporan.php tidak perlu diubah.
function dataLaporanList(int $userId, bool $admin, array $f, int $hal, int $per): array {   // DUMMY
  $kat = dataKategoriList();
  //        id, judul,                     lokasi,                    kategori, prioritas, status,       tanggal,      user, pelapor
  $mentah = [
    [8,  "Tempat sampah penuh",     "Kantin",                   6, "rendah", "dilaporkan", "2026-10-10", 2, "Mahasiswa Demo"],
    [7,  "AC bocor ke meja",        "Gedung B - Lab Komputer",  3, "tinggi", "dilaporkan", "2026-10-09", 2, "Mahasiswa Demo"],
    [9,  "Proyektor tidak menyala", "Gedung A - Ruang 101",     1, "sedang", "diproses",   "2026-10-06", 3, "Rina Putri"],
    [12, "Stop kontak mati",        "Gedung A - Ruang 201",     1, "tinggi", "dilaporkan", "2026-10-05", 4, "Budi Santoso"],
    [5,  "Kursi patah",             "Gedung A - Ruang 101",     4, "sedang", "diproses",   "2026-10-02", 2, "Mahasiswa Demo"],
    [15, "Lampu koridor padam",     "Gedung A - Lantai 2",      1, "sedang", "dilaporkan", "2026-10-02", 3, "Rina Putri"],
    [10, "Pintu toilet rusak",      "Toilet Lantai 1 Gedung A", 2, "rendah", "selesai",    "2026-09-30", 4, "Budi Santoso"],
    [4,  "WiFi perpustakaan putus", "Perpustakaan",             5, "tinggi", "diproses",   "2026-09-27", 2, "Mahasiswa Demo"],
    [11, "Mic masjid berdengung",   "Masjid Kampus",            1, "sedang", "selesai",    "2026-09-22", 3, "Rina Putri"],
    [3,  "Keran wastafel bocor",    "Toilet Lantai 1 Gedung A", 2, "rendah", "selesai",    "2026-09-16", 2, "Mahasiswa Demo"],
    [13, "Meja lab goyang",         "Gedung B - Lab Komputer",  4, "rendah", "selesai",    "2026-09-12", 4, "Budi Santoso"],
    [6,  "Lampu ruangan berkedip",  "Gedung A - Ruang 201",     1, "sedang", "selesai",    "2026-09-08", 2, "Mahasiswa Demo"],
    [14, "AC perpustakaan mati",    "Perpustakaan",             3, "tinggi", "selesai",    "2026-09-02", 3, "Rina Putri"],
    [2,  "Stop kontak lab mati",    "Gedung B - Lab Komputer",  1, "tinggi", "selesai",    "2026-08-20", 2, "Mahasiswa Demo"],
    [1,  "AC tidak dingin",         "Gedung A - Ruang 201",     3, "sedang", "selesai",    "2026-08-02", 2, "Mahasiswa Demo"],
  ];
 
  $semua = [];
  foreach ($mentah as $r) {
    if (!$admin && $r[7] !== $userId) continue;              // user hanya melihat laporannya sendiri
    $semua[] = [
      "id" => $r[0], "judul" => $r[1], "lokasi" => $r[2], "kategori_id" => $r[3], "kategori" => $kat[$r[3]],
      "prioritas" => $r[4], "status" => $r[5], "tanggal" => $r[6], "user_id" => $r[7], "pelapor" => $r[8],
    ];
  }
  usort($semua, fn($a, $b) => [$b["tanggal"], $b["id"]] <=> [$a["tanggal"], $a["id"]]);   // terbaru di atas
 
  // jumlah per status (untuk badge angka di tab)
  $hitung = ["semua" => count($semua), "dilaporkan" => 0, "diproses" => 0, "selesai" => 0];
  foreach ($semua as $r) $hitung[$r["status"]]++;
 
  $hasil = array_values(array_filter($semua, function ($r) use ($f) {
    return ($f["status"] === ""    || $r["status"] === $f["status"])
        && ($f["prioritas"] === "" || $r["prioritas"] === $f["prioritas"])
        && ($f["kategori"] === 0   || $r["kategori_id"] === $f["kategori"]);
  }));
 
  return ["rows" => array_slice($hasil, ($hal - 1) * $per, $per), "total" => count($hasil), "hitung" => $hitung];
}
// Data dummy untuk peringatan laporan serupa.
// Data ini hanya untuk tahap frontend. Nanti ganti dengan query PDO ke tabel laporan.

function dataLaporanSerupa(int $kategoriId, int $lokasiId): array {
  $dataDummy = [
    [
      "kategori_id" => 3,
      "lokasi_id" => 2,
      "judul" => "AC tidak dingin",
      "status" => "diproses",
      "tanggal" => "8 Okt 2026",
      "url" => "detail.php?id=4",
    ],
    [
      "kategori_id" => 3,
      "lokasi_id" => 2,
      "judul" => "AC mengeluarkan suara berisik",
      "status" => "dilaporkan",
      "tanggal" => "10 Okt 2026",
      "url" => "detail.php?id=8",
    ],
    [
      "kategori_id" => 1,
      "lokasi_id" => 1,
      "judul" => "Lampu ruangan berkedip",
      "status" => "dilaporkan",
      "tanggal" => "9 Okt 2026",
      "url" => "detail.php?id=7",
    ],
  ];

  $hasil = [];

  foreach ($dataDummy as $laporan) {
    // Hanya cocokkan kategori dan lokasi yang sama.
    // Status selesai tidak dimasukkan ke peringatan.
    $statusAktif = in_array($laporan["status"], ["dilaporkan", "diproses"], true);
    $kombinasiSama = $laporan["kategori_id"] === $kategoriId
      && $laporan["lokasi_id"] === $lokasiId;

    if ($statusAktif && $kombinasiSama) {
      $hasil[] = [
        "judul" => $laporan["judul"],
        "status" => ucfirst($laporan["status"]),
        "tanggal" => $laporan["tanggal"],
        "url" => $laporan["url"],
      ];
    }
  }

  return array_slice($hasil, 0, 3);
}