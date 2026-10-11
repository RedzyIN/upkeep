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