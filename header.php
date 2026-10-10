<?php
  // Variabel opsional yang bisa diisi halaman SEBELUM require "header.php":
  //   $judul       -> judul halaman
  //   $pakaiChart  -> true kalau halaman butuh Chart.js (dashboard, rekap)
  //   $tanpaNavbar -> true kalau halaman tamu punya navbar sendiri (contoh: index.php)
  //   $paksaTamu   -> true kalau halaman selalu tampil sebagai tamu walau sudah login (landing)
  $judul       = $judul ?? "Upkeep";
  $pakaiChart  = $pakaiChart ?? false;
  $tanpaNavbar = $tanpaNavbar ?? false;
  $paksaTamu   = $paksaTamu ?? false;
  $u           = $paksaTamu ? null : userLogin();   // null = tampilan tamu (tanpa sidebar)
  $halaman     = basename($_SERVER["PHP_SELF"]);

  // halaman turunan ikut menandai menu induknya
  $induk = ["detail.php" => "laporan.php", "edit.php" => "laporan.php"];
  $aktif = $induk[$halaman] ?? $halaman;

  $menu = [];
  if ($u && $u["role"] === "admin") {
    $menu = [
      ["dashboard.php", "Dashboard",      "layout-dashboard"],
      ["laporan.php",   "Semua Laporan",  "clipboard-list"],
      ["rekap.php",     "Rekap & Export", "file-down"],
      ["kategori.php",  "Kategori",       "tags"],
      ["lokasi.php",    "Lokasi",         "map-pin"],
      ["import.php",    "Import CSV",     "file-up"],
    ];
  } elseif ($u) {
    $menu = [
      ["dashboard.php", "Dashboard",    "layout-dashboard"],
      ["laporan.php",   "Laporan Saya", "clipboard-list"],
      ["tambah.php",    "Buat Laporan", "plus-circle"],
    ];
  }
?>
<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judul) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind via CDN (tanpa tailwind.config.js) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Instrument Sans"', 'system-ui', 'sans-serif'] } } } };
  </script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <?php if ($pakaiChart): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
  <?php endif; ?>
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased">

<?php if ($u): ?>
<div class="flex min-h-full">

  <!-- Overlay untuk sidebar di HP -->
  <div id="overlay" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

  <div id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0">
    <a href="dashboard.php" class="flex h-14 items-center gap-2.5 border-b border-white/10 px-5 text-white">
      <div class="flex h-8 w-8 items-center justify-center rounded-md bg-red-700 text-white"><i data-lucide="wrench" class="h-4 w-4"></i></div>
      <div class="text-base font-bold tracking-tight">Upkeep</div>
    </a>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
      <?php foreach ($menu as [$file, $label, $ikon]): ?>
        <a href="<?= $file ?>"
           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                  <?= $aktif === $file ? 'bg-red-700/20 text-red-300' : 'hover:bg-white/5 hover:text-white' ?>">
          <i data-lucide="<?= $ikon ?>" class="h-4 w-4"></i><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="border-t border-white/10 p-4">
      <p class="truncate text-sm font-semibold text-white"><?= e($u["nama"]) ?></p>
      <p class="mb-3 text-xs text-slate-400"><?= $u["role"] === "admin" ? "Admin Sarpras" : "Pelapor" ?></p>
      <a href="logout.php" class="flex items-center gap-2 text-sm text-slate-400 hover:text-white">
        <i data-lucide="log-out" class="h-4 w-4"></i> Keluar
      </a>
    </div>
  </div>

  <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
    <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-8">
      <button id="btnMenu" type="button" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka menu">
        <i data-lucide="menu" class="h-5 w-5"></i>
      </button>
      <h1 class="text-base font-semibold"><?= e($judul) ?></h1>
    </header>

    <div class="flex-1 p-4 lg:p-8">

<?php else: ?>
<!-- Tampilan tamu (landing, login, register) -->
<?php if (!$tanpaNavbar): ?>
<nav class="border-b border-slate-200 bg-white">
  <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4">
    <a href="index.php" class="flex items-center gap-2.5 font-bold tracking-tight">
      <div class="flex h-8 w-8 items-center justify-center rounded-md bg-red-700 text-white"><i data-lucide="wrench" class="h-4 w-4"></i></div>
      Upkeep
    </a>
    <div class="flex items-center gap-2 text-sm font-semibold">
      <a href="login.php" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Masuk</a>
      <a href="register.php" class="rounded-md bg-red-700 px-3 py-1.5 text-white hover:bg-red-800">Daftar</a>
    </div>
  </div>
</nav>
<?php endif; ?>

  <div>
<?php endif; ?>