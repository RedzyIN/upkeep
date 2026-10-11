<?php
  require "init.php";
  $judul       = "Upkeep - Lapor Fasilitas Kampus";
  $tanpaNavbar = true;   // navbar tamu bawaan header.php dimatikan, landing punya navbar sendiri
  $paksaTamu   = true;   // landing selalu tampil sebagai halaman tamu (tanpa sidebar)
  $sudahLogin  = userLogin() !== null;   // dipakai untuk mengganti tombol Masuk/Daftar
  require "header.php";
?>

<!-- ================= 1. NAVBAR ================= -->
<nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
  <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between px-4 sm:px-6 lg:px-8">

    <a href="#atas" class="flex items-center font-bold tracking-tight">Upkeep</a>

    <div class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex">
      <a href="#cara-kerja" data-nav="cara-kerja" class="hover:text-red-700">Cara Kerja</a>
      <a href="#fitur"      data-nav="fitur"      class="hover:text-red-700">Fitur</a>
      <a href="#status"     data-nav="status"     class="hover:text-red-700">Status</a>
      <a href="#faq"        data-nav="faq"        class="hover:text-red-700">FAQ</a>
    </div>

    <div class="flex items-center gap-2 text-sm font-semibold">
      <?php if ($sudahLogin): ?>
        <a href="dashboard.php" class="rounded-md bg-red-700 px-3 py-2 text-white hover:bg-red-800">Buka Dashboard</a>
      <?php else: ?>
        <a href="auth/login.php" class="rounded-md px-3 py-2 text-slate-700 hover:bg-slate-100">Masuk</a>
        <a href="auth/register.php" class="rounded-md bg-red-700 px-3 py-2 text-white hover:bg-red-800">Daftar</a>
      <?php endif; ?>

    </div>
  </div>
</nav>


<!-- ================= 2. HERO ================= -->
<section id="atas" class="border-b border-slate-200 bg-white">
  <div class="mx-auto grid max-w-[1440px] items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-16">

    <!-- Kiri: teks -->
    <div>
      <h1 class="mt-4 text-4xl font-bold tracking-tight lg:text-5xl">Lapor fasilitas rusak, <span class="text-red-600">pantau sampai tuntas.</span></h1>
      <p class="mt-4 max-w-xl text-lg text-slate-600">Wadah terpadu mahasiswa, dosen, dan tenaga kependidikan untuk melaporkan WiFi, plafon, AC bocor, proyektor, hingga sarana laboratorium. Tidak ada laporan yang hilang di grup chat.</p>

      <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <a href="<?= $sudahLogin ? 'auth/tambah.php' : 'auth/register.php' ?>"
           class="inline-flex items-center justify-center gap-2 rounded-md bg-red-700 px-5 py-3 font-semibold text-white hover:bg-red-800">
          Mulai Lapor <i data-lucide="arrow-right" class="h-4 w-4"></i>
        </a>
        <a href="#cara-kerja"
           class="inline-flex items-center justify-center gap-2 rounded-md border border-slate-300 px-5 py-3 font-semibold text-slate-800 hover:bg-slate-100">
          Lihat Cara Kerja <i data-lucide="chevron-down" class="h-4 w-4"></i>
        </a>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-lg">
      <div class="flex flex-wrap items-center justify-start gap-2">
          <div class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold uppercase tracking-wider">#SRP-2024-082</div>
          <div class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
            <i data-lucide="circle-check" class="h-3.5 w-3.5"></i> Selesai
          </div>
      </div>
 
      <h3 class="mt-4 text-lg font-semibold">Pipa conduit & Access Point rusak</h3>
      <p class="mt-1 flex items-center gap-1 text-sm text-slate-500">
        <i data-lucide="map-pin" class="h-4 w-4 text-red-600"></i>
        Ruang Kelas C3P1, Lantai 2 - UNM Gunung Sari
      </p>
 
      <!-- Foto sebelum / sesudah (ganti kotak abu-abu dengan <img> sungguhan nanti) -->
      <div class="mt-4 grid grid-cols-2 gap-3">
        <div class="relative flex h-32 items-center justify-center rounded-lg bg-slate-200 text-slate-400">
          <i data-lucide="image" class="h-8 w-8"></i>
          <span class="absolute left-2 top-2 rounded bg-slate-900 px-2 py-0.5 text-xs font-semibold text-white">Sebelum</span>
        </div>
        <div class="relative flex h-32 items-center justify-center rounded-lg bg-slate-200 text-slate-400">
          <i data-lucide="image" class="h-8 w-8"></i>
          <span class="absolute left-2 top-2 rounded bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Sesudah</span>
        </div>
      </div>
 
      <div class="mt-4 flex items-center justify-between rounded-lg bg-slate-50 p-3">
        <div class="flex items-center gap-3">
          <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white">BA</span>
          <div>
            <p class="text-sm font-semibold">Bambang Agus (Teknisi Jaringan)</p>
            <p class="text-xs text-slate-500">ICT - Divisi Listrik &amp; TI</p>
          </div>
        </div>
        <div class="flex items-center gap-1 text-sm font-semibold text-red-600">
          <i data-lucide="badge-check" class="h-4 w-4"></i> Valid
        </div>
      </div>
 
      <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
        <div class="flex items-center gap-1"><i data-lucide="clock" class="h-4 w-4"></i> Selesai 14:15 WIB</div>
        <div class="flex items-center gap-1 font-semibold text-amber-600"><i data-lucide="star" class="h-4 w-4"></i> Rating pelapor 5.0</div>
      </div>
    </div>
  </div>
 
  <!-- Angka ringkas -->
  <div class="mx-auto max-w-[1440px] px-4 pb-16">
    <div class="grid grid-cols-2 gap-6 rounded-xl border border-slate-200 bg-slate-50 p-6 lg:grid-cols-4">
      <div>
        <p class="text-3xl font-bold text-red-600">28 <span class="text-lg">menit</span></p>
        <p class="text-sm font-semibold">Rata-rata respon pertama</p>
        <p class="text-xs text-slate-500">Teknisi siaga menerima SPK digital</p>
      </div>
      <div>
        <p class="text-3xl font-bold text-green-600">100%</p>
        <p class="text-sm font-semibold">Transparansi visual</p>
        <p class="text-xs text-slate-500">Foto sebelum &amp; sesudah perbaikan</p>
      </div>
      <div>
        <p class="text-3xl font-bold text-yellow-600">14 <span class="text-lg">regu</span></p>
        <p class="text-sm font-semibold">Siaga di seluruh zona</p>
        <p class="text-xs text-slate-500">Gedung kuliah, lab &amp; asrama</p>
      </div>
      <div>
        <p class="text-3xl font-bold text-green-500">4.8 <span class="text-lg text-slate-500">/ 5</span></p>
        <p class="text-sm font-semibold">Indeks kepuasan</p>
        <p class="text-xs text-slate-500">Berdasarkan 3.420+ penilaian</p>
      </div>
    </div>
  </div>
</section>


<!-- ================= 3. CARA KERJA ================= -->
<section id="cara-kerja" class="scroll-mt-20 bg-slate-50">
  <div class="mx-auto max-w-[1440px] px-4 py-16">
    <h2 class="text-2xl font-bold uppercase tracking-wider text-center">Tata Cara Penggunaan</h2>
 
    <div class="mt-10 grid gap-6 md:grid-cols-3">
 
      <div class="rounded-xl border border-slate-200 bg-white p-10 transition-all duration-300 shadow-sm hover:shadow-xl">
        <div class="flex items-center justify-between">
          <span class="text-4xl font-bold text-red-700">01</span>
          <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 text-red-600"><i data-lucide="camera" class="h-6 w-6"></i></span>
        </div>
        <h3 class="mt-4 text-lg font-semibold">Foto &amp; tentukan lokasi</h3>
        <p class="mt-2 text-sm text-slate-600">
          Pilih gedung, lantai, dan ruangan. Unggah foto kendala, misalnya kabel terkelupas, kursi patah, atau lampu padam.
        </p>
      </div>
 
      <div class="rounded-xl border border-slate-200 bg-white p-10 transition-all duration-300 shadow-sm hover:shadow-xl">
        <div class="flex items-center justify-between">
          <span class="text-4xl font-bold text-red-700">02</span>
          <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 text-red-600"><i data-lucide="clipboard-check" class="h-6 w-6"></i></span>
        </div>
        <h3 class="mt-4 text-lg font-semibold">Admin menindaklanjuti</h3>
        <p class="mt-2 text-sm text-slate-600">
          Biro Sarpras memvalidasi laporan, mengubah status, dan menugaskan teknisi di zona terkait.
        </p>
      </div>
 
      <div class="rounded-xl border border-slate-200 bg-white p-10 transition-all duration-300 shadow-sm hover:shadow-xl">
        <div class="flex items-center justify-between">
          <span class="text-4xl font-bold text-red-700">03</span>
          <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 text-red-600"><i data-lucide="circle-check" class="h-6 w-6"></i></span>
        </div>
        <h3 class="mt-4 text-lg font-semibold">Pantau sampai tuntas</h3>
        <p class="mt-2 text-sm text-slate-600">
          Lihat timeline status, cek foto setelah diperbaiki, lalu beri rating untuk kinerja teknisi.
        </p>
      </div>
    </div>
  </div>
</section>


<!-- ================= 4. FITUR ================= -->
<section id="fitur" class="scroll-mt-20 bg-white border border-slate-200">
  <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8">
    <h2 class="text-2xl font-bold uppercase tracking-wider text-center">Apa saja Fiturnya?</h2>

    <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">

      <div class="rounded-xl border border-slate-200 p-6 transition-all duration-300 shadow-sm hover:shadow-xl">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-700"><i data-lucide="image-plus" class="h-5 w-5"></i></span>
        <h3 class="mt-4 font-semibold">Laporan dengan foto</h3>
        <p class="mt-2 text-sm text-slate-600">Unggah foto kerusakan agar teknisi langsung paham masalahnya.</p>
      </div>

      <div class="rounded-xl border border-slate-200 p-6 transition-all duration-300 shadow-sm hover:shadow-xl">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-700"><i data-lucide="list-checks" class="h-5 w-5"></i></span>
        <h3 class="mt-4 font-semibold">Timeline status</h3>
        <p class="mt-2 text-sm text-slate-600">Setiap perubahan status dan catatan penanganan tercatat berurutan.</p>
      </div>

      <div class="rounded-xl border border-slate-200 p-6 transition-all duration-300 shadow-sm hover:shadow-xl">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-700"><i data-lucide="shield-check" class="h-5 w-5"></i></span>
        <h3 class="mt-4 font-semibold">Anti-spam</h3>
        <p class="mt-2 text-sm text-slate-600">Batas laporan per hari, peringatan laporan serupa, dan perlindungan token CSRF.</p>
      </div>

      <div class="rounded-xl border border-slate-200 p-6 transition-all duration-300 shadow-sm hover:shadow-xl">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-700"><i data-lucide="layout-dashboard" class="h-5 w-5"></i></span>
        <h3 class="mt-4 font-semibold">Dashboard</h3>
        <p class="mt-2 text-sm text-slate-600">Ringkasan laporan dan grafik, berbeda untuk pelapor dan admin.</p>
      </div>

      <div class="rounded-xl border border-slate-200 p-6 transition-all duration-300 shadow-sm hover:shadow-xl">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-700"><i data-lucide="file-down" class="h-5 w-5"></i></span>
        <h3 class="mt-4 font-semibold">Export Excel/PDF</h3>
        <p class="mt-2 text-sm text-slate-600">Admin bisa mengunduh rekap laporan untuk arsip dan pelaporan.</p>
      </div>

      <div class="rounded-xl border border-slate-200 p-6 transition-all duration-300 shadow-sm hover:shadow-xl">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-700"><i data-lucide="star" class="h-5 w-5"></i></span>
        <h3 class="mt-4 font-semibold">Rating</h3>
        <p class="mt-2 text-sm text-slate-600">Pelapor menilai hasil perbaikan setelah laporan selesai.</p>
      </div>
    </div>
  </div>
</section>


<!-- ================= 5. STATUS LAYANAN ================= -->
<section id="status" class="scroll-mt-20 bg-slate-50">
  <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8">
    <h2 class="text-2xl font-bold uppercase tracking-wider text-center">Status layanan</h2>

    <div class="flex flex-col justify-between gap-2 md:flex-row md:items-end">
      <h2 class="mt-1 text-3xl font-bold tracking-tight">Kondisi sistem saat ini</h2>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">

      <!-- Tiga angka ringkas -->
      <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-sm text-slate-500">Sistem</p>
          <p class="mt-1 flex items-center gap-2 text-lg font-bold">
            <span id="sistemTitik" class="h-3 w-3 rounded-full bg-slate-300"></span>
            <span id="sistemTeks">Memeriksa...</span>
          </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-sm text-slate-500">Laporan masuk hari ini</p>
          <p id="angkaHariIni" class="mt-1 text-3xl font-bold text-red-700">-</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-sm text-slate-500">Sedang diproses</p>
          <p id="angkaProses" class="mt-1 text-3xl font-bold text-amber-600">-</p>
        </div>
      </div>

      <!-- Aktivitas terbaru: hanya kategori + status, tanpa lokasi/nama pelapor -->
      <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
        <h3 class="font-semibold">Aktivitas terbaru</h3>
        <ul id="daftarAktivitas" class="mt-3 divide-y divide-slate-100 text-sm">
          <li class="py-3 text-slate-400">Memuat data...</li>
        </ul>
      </div>
    </div>
    <div class="flex flex-col justify-end mt-6 md:flex-row md:items-end">
      <p class="text-xs text-slate-500">Diperbarui otomatis setiap 5 menit</p>
    </div>
  </div>
</section>


<!-- ================= 6. DAMPAK DALAM ANGKA ================= -->
<section id="dampak" class="bg-white">
  <div class="mx-auto max-w-screen px-auto py-12 border border-slate-200 sm:px-6 lg:px-8">
    <h3 class="text-2xl font-bold uppercase tracking-wider mx-auto max-w-2xl text-center">Laporan Yang Ditindaklanjuti</h3>

    <div class="mt-8 grid grid-cols-2 gap-6 lg:grid-cols-4">
      <div class="rounded-xl bg-slate-900 p-6 text-center text-white">
        <p id="dampakTotal" class="text-4xl font-bold">0</p>
        <p class="mt-1 text-sm text-slate-300">Total laporan</p>
      </div>
      <div class="rounded-xl bg-slate-900 p-6 text-center text-white">
        <p id="dampakSelesai" class="text-4xl font-bold">0</p>
        <p class="mt-1 text-sm text-slate-300">Laporan selesai</p>
      </div>
      <div class="rounded-xl bg-slate-900 p-6 text-center text-white">
        <p id="dampakProses" class="text-4xl font-bold">0</p>
        <p class="mt-1 text-sm text-slate-300">Sedang diproses</p>
      </div>
      <div class="rounded-xl bg-red-700 p-6 text-center text-white">
        <p><span id="dampakPersen" class="text-4xl font-bold">0</span><span class="text-2xl font-bold">%</span></p>
        <p class="mt-1 text-sm text-red-100">Tingkat penyelesaian</p>
      </div>
    </div>
  </div>
</section>


<!-- ================= 7. FAQ (accordion) ================= -->
<section id="faq" class="scroll-mt-20 bg-slate-50">
  <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h3 class="text-2xl font-bold uppercase tracking-wider text-center">FAQ</h3>

    <!-- Setiap item: tombol [data-faq] + jawaban (hidden). Diatur oleh assets/js/landing.js -->
    <div class="mt-8 space-y-3">

      <div class="rounded-lg border border-slate-200 bg-white">
        <button type="button" data-faq class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" aria-expanded="false">
          Siapa yang boleh membuat laporan?
          <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 transition-transform"></i>
        </button>
        <div class="hidden px-5 pb-4 text-sm text-slate-600">
          Semua civitas kampus yang sudah punya akun, yaitu mahasiswa, dosen, dan tenaga kependidikan. Daftar dulu, lalu kamu bisa langsung melapor.
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white">
        <button type="button" data-faq class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" aria-expanded="false">
          Berapa lama laporan ditangani?
          <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 transition-transform"></i>
        </button>
        <div class="hidden px-5 pb-4 text-sm text-slate-600">
          Tergantung antrean dan tingkat kerusakan. Kamu tidak perlu menebak: setiap perubahan status tampil di timeline laporanmu.
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white">
        <button type="button" data-faq class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" aria-expanded="false">
          Apakah foto wajib dilampirkan?
          <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 transition-transform"></i>
        </button>
        <div class="hidden px-5 pb-4 text-sm text-slate-600">
          Sangat dianjurkan. Foto membantu admin menilai tingkat kerusakan dan menyiapkan alat yang tepat sebelum teknisi datang.
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white">
        <button type="button" data-faq class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" aria-expanded="false">
          Bagaimana kalau laporan yang sama sudah dibuat orang lain?
          <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 transition-transform"></i>
        </button>
        <div class="hidden px-5 pb-4 text-sm text-slate-600">
          Sistem memberi peringatan jika ada laporan serupa di lokasi yang sama, jadi kamu tidak perlu membuat laporan ganda.
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white">
        <button type="button" data-faq class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" aria-expanded="false">
          Apakah data saya aman?
          <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 transition-transform"></i>
        </button>
        <div class="hidden px-5 pb-4 text-sm text-slate-600">
          Halaman publik hanya menampilkan kategori dan status laporan. Lokasi detail dan identitas pelapor hanya terlihat oleh pelapor itu sendiri dan admin.
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white">
        <button type="button" data-faq class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" aria-expanded="false">
          Bagaimana cara mengetahui laporan saya sudah selesai?
          <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 transition-transform"></i>
        </button>
        <div class="hidden px-5 pb-4 text-sm text-slate-600">
          Buka menu Laporan Saya setelah masuk. Statusnya berubah menjadi Selesai, dan kamu bisa memberi rating atas hasil perbaikannya.
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ================= 8. AJAKAN PENUTUP ================= -->
<section class="bg-white">
  <div class="mx-auto max-w-[1440px] px-4 py-16">
    <div class="rounded-2xl bg-slate-900 p-8 text-white lg:p-12">
      <h2 class="mt-4 max-w-2xl text-3xl font-bold tracking-tight lg:text-4xl">Menemukan sarana kampus yang kurang layak hari ini?</h2>
      <p class="mt-3 max-w-2xl text-slate-300">
        Bantu ciptakan lingkungan akademik yang aman dan nyaman. Daftar atau masuk, lalu laporkan dalam hitungan detik.
      </p>
 
      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <a href="auth/register.php" class="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-5 py-3 font-semibold hover:bg-red-700">
          <i data-lucide="user-plus" class="h-5 w-5"></i> Daftar akun
        </a>
        <a href="auth/login.php" class="inline-flex items-center justify-center gap-2 rounded-md border border-white/30 px-5 py-3 font-semibold hover:bg-white/10">
          <i data-lucide="log-in" class="h-5 w-5"></i> Masuk ke portal
        </a>
      </div>
 
      <p class="mt-5 flex items-center gap-2 text-sm text-slate-300">
        <i data-lucide="search" class="h-4 w-4"></i> Sudah punya tiket? Cek statusnya setelah masuk.
      </p>
    </div>
  </div>
</section>


<!-- ================= 9. FOOTER ================= -->
<footer class="border-t border-slate-200 bg-white">
  <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid gap-8 md:grid-cols-4">

      <div>
        <div class="flex items-center gap-2 font-bold">Upkeep</div>
        <p class="mt-3 text-sm text-slate-600">Sistem pelaporan dan pemantauan perbaikan fasilitas kampus.</p>
      </div>

      <!-- Data kontak di bawah adalah CONTOH, ganti dengan data resmi -->
      <div>
        <p class="text-sm font-semibold">Kontak layanan </p>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
          <li class="flex items-center gap-2"><i data-lucide="clock" class="h-4 w-4"></i> Senin - Jumat, 07.30 - 16.30</li>
          <li class="flex items-center gap-2"><i data-lucide="message-circle" class="h-4 w-4"></i> WhatsApp: 0811-2345-6789</li>
          <li class="flex items-center gap-2"><i data-lucide="mail" class="h-4 w-4"></i> sarpras@kampus.ac.id</li>
        </ul>
      </div>

      <div>
        <p class="text-sm font-semibold">Tautan cepat</p>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
          <li><a href="#cara-kerja" class="hover:text-red-700">Cara Kerja</a></li>
          <li><a href="#faq" class="hover:text-red-700">FAQ</a></li>
          <li><a href="auth/login.php" class="hover:text-red-700">Masuk</a></li>
        </ul>
      </div>
      <div>
        <p class="text-sm font-semibold">Developer Social Media</p>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
          <li><a href="https://github.com/RedzyIN/upkeep" target="_blank" rel="noopener" class="inline-flex items-center gap-2 hover:text-red-700">GitHub</a></li>
        </ul>
      </div>
    </div>

    <p class="mt-8 border-t border-slate-200 pt-6 text-center text-xs text-slate-500">
      &copy; <?= date("Y") ?> Upkeep. Proyek portofolio.
    </p>
  </div>
</footer>

<script src="assets/js/landing.js"></script>
<?php require "footer.php"; ?>