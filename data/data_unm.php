<?php
// Daftar fakultas dan program studi Universitas Negeri Makassar (UNM).
// Dipakai oleh register.php (dropdown Fakultas -> Jurusan) dan boleh dipakai lagi
// untuk validasi di server: jurusan yang dikirim harus ada di dalam fakultasnya.
//
// Sumber: daftar program studi di situs PMBM resmi UNM
//   https://daftar-pmbm.unm.ac.id/listprodi.php
//   https://daftar-mandiri.unm.ac.id/listprodi.php
// Dicek 11 Okt 2026. Kelas khusus (ICP Bilingual, Kelas Pare-Pare, Kampus Bone)
// digabung ke prodi induknya. Program non-S1 diberi tanda (D3) atau (D4).
// Nama prodi bisa berubah; cocokkan dengan bagian akademik kalau ada yang kurang.
//
// Cara menambah: tulis satu baris baru di dalam daftar fakultasnya.
// Belum ada: Fakultas Kedokteran (prodinya belum bisa saya pastikan dari sumber resmi).

return [

  "Fakultas Matematika dan Ilmu Pengetahuan Alam" => [
    "Pendidikan Matematika", "Matematika",
    "Pendidikan Fisika", "Fisika",
    "Pendidikan Kimia", "Kimia",
    "Pendidikan Biologi", "Biologi",
    "Pendidikan Geografi", "Geografi",
    "Pendidikan IPA", "Statistika", "Sains Data",
  ],

  "Fakultas Teknik" => [
    "Pendidikan Teknik Bangunan",
    "Pendidikan Teknik Mesin",
    "Pendidikan Teknik Otomotif",
    "Pendidikan Teknik Elektro",
    "Pendidikan Teknik Elektronika",
    "Pendidikan Teknik Informatika dan Komputer",
    "Teknik Komputer",
    "Pendidikan Vokasional Mekatronika",
    "Pendidikan Vokasional Seni Kuliner",
    "Pendidikan Teknologi Pertanian",
    "Pendidikan Kesejahteraan Keluarga",
    "Sains Perikanan",
    "Teknik Sipil dan Bangunan (D3)",
    "Teknik Mesin (D3)",
    "Teknik Otomotif (D3)",
    "Teknik Elektro (D3)",
    "Teknik Elektronika (D3)",
    "Tata Busana (D3)",
    "Tata Boga (D3)",
    "Tata Boga (D4)",
    "Teknik Elektro (D4)",
    "Teknik Elektronika (D4)",
  ],

  "Fakultas Ilmu Keolahragaan dan Kesehatan" => [
    "Pendidikan Jasmani Kesehatan dan Rekreasi",
    "Pendidikan Jasmani Kesehatan dan Rekreasi Ke-SD-an",
    "Pendidikan Kepelatihan Olahraga",
    "Ilmu Keolahragaan",
    "Promosi Kesehatan (D4)",
  ],

  "Fakultas Ilmu Pendidikan" => [
    "Teknologi Pendidikan",
    "Pendidikan Luar Sekolah",
    "Pendidikan Masyarakat",
    "Administrasi Pendidikan",
    "Bimbingan dan Konseling",
    "Pendidikan Luar Biasa",
    "Pendidikan Guru Sekolah Dasar",
    "Pendidikan Guru PAUD",
  ],

  "Fakultas Bahasa dan Sastra" => [
    "Pendidikan Bahasa dan Sastra Indonesia", "Sastra Indonesia",
    "Pendidikan Bahasa Inggris", "Sastra Inggris",
    "Pendidikan Bahasa Jerman",
    "Pendidikan Bahasa Arab",
    "Pendidikan Bahasa Mandarin",
    "Pendidikan Bahasa dan Sastra Daerah",
    "Pendidikan Bahasa Bugis",
    "Pendidikan Bahasa Makassar",
    "Bahasa Inggris (D3)",
    "Bahasa Inggris (D4)",
    "Hubungan Masyarakat dan Komunikasi Digital (D4)",
  ],

  "Fakultas Ilmu Sosial dan Hukum" => [
    "Pendidikan Pancasila dan Kewarganegaraan",
    "Pendidikan Sejarah",
    "Pendidikan Sosiologi", "Sosiologi",
    "Pendidikan IPS",
    "Pendidikan Antropologi",
    "Ilmu Administrasi Negara",
    "Ilmu Administrasi Bisnis",
    "Pendidikan Administrasi Perkantoran",
  ],

  "Fakultas Psikologi" => [
    "Psikologi",
  ],

  "Fakultas Seni dan Desain" => [
    "Pendidikan Seni Rupa",
    "Pendidikan Seni Drama Tari dan Musik",
    "Seni Tari",
    "Desain Komunikasi Visual",
    "Etnomusikologi",
    "Animasi (D4)",
  ],

  "Fakultas Ekonomi dan Bisnis" => [
    "Pendidikan Ekonomi",
    "Pendidikan Akuntansi", "Akuntansi",
    "Manajemen",
    "Ekonomi Pembangunan",
    "Akuntansi (D3)",
  ],

];