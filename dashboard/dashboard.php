<?php
  require "../init.php";
  require "../data/dummy.php";
  previewLogin();                 // DUMMY: hapus baris ini saat login sungguhan sudah jadi
  wajibLogin();

  $u     = userLogin();
  $admin = isAdmin();
  $data  = $admin ? dataDashboardAdmin() : dataDashboardUser();   // DUMMY: nanti diganti query database

  $judul      = "Dashboard";
  $pakaiChart = true;
  $jsHalaman  = ["assets/js/dashboard.js"];
  require "../header.php";

  $k     = $data["kartu"];
  $kartu = [
    [$admin ? "Total Laporan" : "Laporan Saya", $k["total"],      "clipboard-list", "bg-slate-100 text-slate-600"],
    ["Menunggu",                                $k["dilaporkan"], "clock",          "bg-amber-100 text-amber-700"],
    ["Diproses",                                $k["diproses"],   "loader",         "bg-sky-100 text-sky-700"],
    ["Selesai",                                 $k["selesai"],    "circle-check",   "bg-emerald-100 text-emerald-700"],
  ];
  $namaDepan = explode(" ", $u["nama"])[0];
?>
 
<!-- ===== Sapaan ===== -->
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h2 class="text-2xl font-bold tracking-tight">Halo, <?= e($namaDepan) ?></h2>
    <p class="text-sm text-slate-500">
      <?= $admin ? "Berikut kondisi laporan fasilitas kampus saat ini." : "Pantau perkembangan laporan kerusakanmu di sini." ?>
    </p>
  </div>
  <?php if ($admin): ?>
    <a href="laporan.php" class="inline-flex w-full justify-center items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white sm:w-auto hover:bg-red-700">
      <i data-lucide="clipboard-list" class="h-4 w-4"></i> Lihat Semua Laporan
    </a>
  <?php else: ?>
    <a href="tambah_laporan.php" class="inline-flex w-full justify-center items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white sm:w-auto hover:bg-red-700">
      <i data-lucide="circle-plus" class="h-4 w-4"></i> Buat Laporan
    </a>
  <?php endif; ?>
</div>
 
<?php if (!$admin && $data["perlu_rating"] > 0): ?>
<div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
  <i data-lucide="star" class="h-5 w-5 shrink-0"></i>
  <p><?= (int) $data["perlu_rating"] ?> laporanmu sudah selesai dan belum dinilai. <a href="laporan.php?status=selesai" class="font-semibold underline">Beri rating</a></p>
</div>
<?php endif; ?>
 
<!-- ===== Kartu ringkasan ===== -->
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ($kartu as [$label, $nilai, $ikon, $warna]): ?>
  <div class="rounded-xl border border-black/10 bg-white p-5">
    <div class="flex items-center justify-between">
      <p class="text-sm font-medium text-slate-500"><?= e($label) ?></p>
      <span class="flex h-9 w-9 items-center justify-center rounded-lg <?= $warna ?>"><i data-lucide="<?= $ikon ?>" class="h-5 w-5"></i></span>
    </div>
    <p class="mt-3 text-3xl font-bold tabular-nums" data-count="<?= (int) $nilai ?>">0</p>
  </div>
  <?php endforeach; ?>
</div>
 
<?php if ($admin): ?>
<!-- ==================== ADMIN ==================== -->
<div class="mb-6 grid gap-4 lg:grid-cols-3">
  <section class="rounded-xl border border-black/10 bg-white p-5 lg:col-span-2">
    <h3 class="font-semibold">Laporan per Bulan</h3>
    <p class="mb-4 text-sm text-slate-500">6 bulan terakhir</p>
    <div id="chartBulanan"></div>
  </section>
  <section class="rounded-xl border border-black/10 bg-white p-5">
    <h3 class="font-semibold">Status Laporan</h3>
    <p class="mb-4 text-sm text-slate-500">Seluruh laporan</p>
    <div id="chartStatus"></div>
  </section>
</div>
 
<div class="mb-6 grid gap-4 lg:grid-cols-3">
  <section class="rounded-xl border border-black/10 bg-white p-5 lg:col-span-2">
    <h3 class="font-semibold">Laporan per Kategori</h3>
    <p class="mb-4 text-sm text-slate-500">Kerusakan apa yang paling sering terjadi</p>
    <div id="chartKategori"></div>
  </section>
  <section class="rounded-xl border border-black/10 bg-white p-5">
    <h3 class="font-semibold">5 Lokasi Paling Sering Dilaporkan</h3>
    <?php $maks = max(array_column($data["top_lokasi"], 1)); ?>
    <ul class="mt-4 space-y-3">
      <?php foreach ($data["top_lokasi"] as [$lokasi, $jumlah]): ?>
      <li>
        <div class="mb-1 flex justify-between text-sm"><span class="truncate pr-2"><?= e($lokasi) ?></span><span class="font-semibold tabular-nums"><?= (int) $jumlah ?></span></div>
        <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-red-600" style="width: <?= round($jumlah / $maks * 100) ?>%"></div></div>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
 
<div class="grid gap-4 lg:grid-cols-3">
  <section class="rounded-xl border border-black/10 bg-white lg:col-span-2">
    <div class="flex items-center justify-between border-b border-black/5 px-5 py-4">
      <h3 class="font-semibold">Perlu Perhatian</h3>
      <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700"><?= (int) $data["kinerja"]["terlambat"] ?> terlambat</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
          <tr><th class="px-5 py-3">Laporan</th><th class="px-5 py-3">Prioritas</th><th class="px-5 py-3">Umur</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($data["perhatian"] as $r): ?>
          <tr>
            <td class="px-5 py-3"><p class="font-medium"><?= e($r["judul"]) ?></p><p class="text-xs text-slate-500"><?= e($r["lokasi"]) ?></p></td>
            <td class="px-5 py-3"><?= badgePrioritas($r["prioritas"]) ?></td>
            <td class="px-5 py-3 tabular-nums <?= $r["umur"] > 3 ? 'font-semibold text-rose-600' : '' ?>"><?= (int) $r["umur"] ?> hari</td>
            <td class="px-5 py-3 text-right"><a href="detail.php?id=<?= (int) $r["id"] ?>" class="font-semibold text-red-700 hover:underline">Tangani</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
 
  <section class="rounded-xl border border-black/10 bg-white p-5">
    <h3 class="mb-4 font-semibold">Kinerja Penanganan</h3>
    <dl class="space-y-4 text-sm">
      <div class="flex items-center justify-between"><dt class="text-slate-500">Rata-rata waktu selesai</dt><dd class="text-lg font-bold"><?= str_replace(".", ",", $data["kinerja"]["rata_hari"]) ?> hari</dd></div>
      <div class="flex items-center justify-between"><dt class="text-slate-500">Rata-rata rating</dt><dd class="text-lg font-bold"><?= str_replace(".", ",", $data["kinerja"]["rating"]) ?> / 5</dd></div>
      <div class="flex items-center justify-between"><dt class="text-slate-500">Laporan terlambat</dt><dd class="text-lg font-bold text-rose-600"><?= (int) $data["kinerja"]["terlambat"] ?></dd></div>
    </dl>
  </section>
</div>
 
<?php else: ?>
<!-- ==================== USER ==================== -->
<div class="mb-6 grid gap-4 lg:grid-cols-3">
  <section class="rounded-xl border border-black/10 bg-white p-5 lg:col-span-2">
    <h3 class="font-semibold">Laporan Saya per Bulan</h3>
    <p class="mb-4 text-sm text-slate-500">6 bulan terakhir</p>
    <div id="chartBulanan"></div>
  </section>
  <section class="rounded-xl border border-black/10 bg-white p-5">
    <h3 class="font-semibold">Status Laporan</h3>
    <p class="mb-4 text-sm text-slate-500">Semua laporan</p>
    <div id="chartStatus"></div>
  </section>
</div>
 
<section class="rounded-xl border border-black/10 bg-white">
  <div class="flex items-center justify-between border-b border-black/5 px-5 py-4">
    <h3 class="font-semibold">Laporan Terbaru</h3>
    <a href="laporan.php" class="text-sm font-semibold text-red-700 hover:underline">Lihat semua</a>
  </div>
  <?php if (empty($data["terbaru"])): ?>
    <div class="px-5 py-12 text-center">
      <p class="font-medium">Belum ada laporan</p>
      <p class="mb-4 text-sm text-slate-500">Lihat sesuatu yang rusak di kampus? Laporkan sekarang.</p>
      <a href="tambah_laporan.php" class="inline-flex w-full justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white sm:w-auto hover:bg-red-700">Buat Laporan</a>
    </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-500">
        <tr><th class="px-5 py-3">Laporan</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($data["terbaru"] as $r): ?>
        <tr>
          <td class="px-5 py-3"><p class="font-medium"><?= e($r["judul"]) ?></p><p class="text-xs text-slate-500"><?= e($r["lokasi"]) ?></p></td>
          <td class="whitespace-nowrap px-5 py-3 text-slate-600"><?= tglIndo($r["tanggal"]) ?></td>
          <td class="px-5 py-3"><?= badgeStatus($r["status"]) ?></td>
          <td class="px-5 py-3 text-right"><a href="detail.php?id=<?= (int) $r["id"] ?>" class="font-semibold text-red-700 hover:underline">Detail</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>
 
<!-- Data dari PHP dikirim ke JavaScript, lalu digambar oleh dashboard.js -->
<script>window.DASHBOARD = <?= json_encode($data + ["role" => $u["role"]], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>

<?php require "../footer.php"; ?>