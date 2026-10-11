<?php
  // Variabel opsional yang diisi halaman SEBELUM require "header.php":
  //   $judul      -> judul halaman
  //   $jejak      -> posisi halaman di topbar, mis. [["Laporan Saya","laporan.php"], ["Detail",null]]
  //   $pakaiChart -> true kalau halaman butuh ApexCharts
  $judul      = $judul ?? "Upkeep";
  $jejak      = $jejak ?? [[$judul, null]];
  $pakaiChart = $pakaiChart ?? false;
  $u          = ($paksaTamu ?? false) ? null : userLogin();                       // null kalau belum login
  $halaman    = basename($_SERVER["PHP_SELF"]);
  $tanpaNavbar = $tanpaNavbar ?? false;
 
  // halaman turunan ikut menandai menu induknya
  $induk = ["detail.php" => "laporan.php", "edit.php" => "laporan.php"];
  $aktif = $induk[$halaman] ?? $halaman;
 
  // Isi sidebar: "Judul grup" => [[file, label, ikon], ...]
  $menu = [];
  if ($u && $u["role"] === "admin") {
    $menu = [
      "Utama"       => [["dashboard.php", "Dashboard", "layout-dashboard"],
                        ["laporan.php",   "Semua Laporan", "clipboard-list"]],
      "Master Data" => [["kategori.php",  "Kategori", "tags"],
                        ["lokasi.php",    "Lokasi", "map-pin"],
                        ["import.php",    "Import Excel", "file-up"]],
      "Laporan"     => [["rekap.php",     "Rekap & Export", "file-down"]],
      "Akun"        => [["profil.php",    "Profil Saya", "user-round"]],
      // Opsional: ["pengguna.php", "Pengguna", "users"]  (kelola akun user)
    ];
  } elseif ($u) {
    $menu = [
      "Utama" => [["dashboard.php", "Dashboard", "layout-dashboard"],
                  ["tambah_laporan.php",    "Buat Laporan", "circle-plus"],
                  ["laporan.php",   "Laporan Saya", "clipboard-list"]],
      "Akun"  => [["profil.php",    "Profil Saya", "user-round"],
                  ["bantuan.php",   "Bantuan", "life-buoy"]],
    ];
  }
  $inisial = $u ? strtoupper(mb_substr($u["nama"], 0, 1)) : "";
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judul) ?> | Upkeep</title>
 
  <!-- Ingat posisi sidebar (tutup/buka) sebelum halaman tampil, supaya tidak berkedip -->
  <script>try { if (localStorage.getItem("sidebarTutup") === "1") document.documentElement.classList.add("sidebar-tutup"); } catch (e) {}</script>
 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
 
  <!-- Tailwind via CDN (tanpa tailwind.config.js) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Instrument Sans"', 'system-ui', 'sans-serif'] } } } };
  </script>
  <style>
    :focus-visible { outline: 2px solid #dc2626; outline-offset: 2px; }
    #sidebar :focus-visible { outline-color: #fff; }
    #sidebar, #konten { transition: transform .2s ease, padding .2s ease; }
    @media (min-width: 1024px) {
      html.sidebar-tutup #sidebar { transform: translateX(-100%); }
      html.sidebar-tutup #konten  { padding-left: 0; }
    }
  </style>
 
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <?php if ($pakaiChart): ?>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1"></script>
  <?php endif; ?>
</head>
<body class="h-full bg-slate-200 font-sans text-black antialiased">
 
<?php if ($u): ?>
<div class="flex min-h-full">
 
  <!-- Overlay sidebar di HP -->
  <div id="overlay" class="fixed inset-0 z-30 hidden bg-black/60 lg:hidden"></div>
 
  <aside id="sidebar" class="fixed inset-y-0 max-w-[85vw] left-0 z-40 flex w-64 -translate-x-full flex-col bg-black text-slate-200 lg:translate-x-0">
    <a href="dashboard.php" class="flex h-14 shrink-0 items-center gap-2.5 border-b border-white/10 px-5 text-white">
      <div class="text-base font-bold tracking-tight">Upkeep</div>
    </a>
 
    <nav class="flex-1 overflow-y-auto px-3 py-4">
      <?php foreach ($menu as $grup => $items): ?>
        <p class="mb-1 mt-4 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 first:mt-0"><?= e($grup) ?></p>
        <?php foreach ($items as [$file, $label, $ikon]): ?>
          <a href="<?= $file ?>"
             class="mb-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                    <?= $aktif === $file ? 'bg-red-600 text-white' : 'text-slate-200/80 hover:bg-white/10 hover:text-white' ?>">
            <i data-lucide="<?= $ikon ?>" class="h-4 w-4"></i><?= e($label) ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
  </aside>
 
  <div id="konten" class="flex min-w-0 flex-1 flex-col lg:pl-64">
 
    <!-- TOPBAR: (1) tombol sidebar  (2) posisi halaman  (3) profil -->
    <header class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-black/10 bg-white/90 px-4 backdrop-blur lg:px-6">
      <div class="flex min-w-0 items-center gap-3">
        <button id="btnMenu" type="button" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100" aria-label="Buka atau tutup sidebar">
          <i data-lucide="panel-left" class="h-5 w-5"></i>
        </button>
        <nav aria-label="Posisi halaman" class="flex min-w-0 items-center gap-1.5 text-sm text-slate-500">
          <i data-lucide="house" class="h-4 w-4 shrink-0"></i>
          <?php foreach ($jejak as [$label, $url]): ?>
            <i data-lucide="chevron-right" class="h-3.5 w-3.5 shrink-0 text-slate-300"></i>
            <?php if ($url): ?>
              <a href="<?= e($url) ?>" class="truncate hover:text-red-700"><?= e($label) ?></a>
            <?php else: ?>
              <span class="truncate font-semibold text-slate-800"><?= e($label) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
      </div>
 
      <div class="relative shrink-0">
        <button id="btnProfil" type="button" aria-haspopup="menu" aria-expanded="false" class="flex items-center gap-2 py-1 pl-1 pr-2.5 hover:bg-slate-100">
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white"><?= e($inisial) ?></div>
          <div class="hidden text-left leading-tight sm:block">
            <div class="block text-sm font-semibold"><?= e($u["nama"]) ?></div>
            <div class="block text-xs text-slate-500"><?= $u["role"] === "admin" ? "Admin" : "Pelapor" ?></div>
          </div>
          <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400"></i>
        </button>
        <div id="menuProfil" role="menu" class="absolute right-0 mt-2 hidden w-48 border border-black/10 bg-white p-1 shadow-lg">
          <a href="profil.php" role="menuitem" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-slate-100">
            <i data-lucide="user-round" class="h-4 w-4"></i> Profil Saya
          </a>
          <a href="logout.php" role="menuitem" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">
            <i data-lucide="log-out" class="h-4 w-4"></i> Keluar
          </a>
        </div>
      </div>
    </header>
 
    <main id="isi" class="flex-1 p-4 lg:p-8">

<?php else: ?>
<!-- Tampilan tamu (landing, login, register) -->
  <?php if (!$tanpaNavbar): ?>
  <?php endif; ?>
  <main id="isi">
<?php endif; ?>