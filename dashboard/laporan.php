
<?php
require "../init.php";

wajibLogin();

$u = userLogin();
$admin = isAdmin();

// --------------------------------------------------
// 1. Baca dan validasi filter dari URL
// --------------------------------------------------

$statusBoleh = ["dilaporkan", "diproses", "selesai"];
$prioritasBoleh = ["rendah", "sedang", "tinggi"];

$status = $_GET["status"] ?? "";
if (!in_array($status, $statusBoleh, true)) {
    $status = "";
}

$prioritas = $_GET["prioritas"] ?? "";
if (!in_array($prioritas, $prioritasBoleh, true)) {
    $prioritas = "";
}

$kategori = max(0, (int) ($_GET["kategori"] ?? 0));
$hal = max(1, (int) ($_GET["hal"] ?? 1));
$per = 10;

// Batasi panjang pencarian.
$q = trim($_GET["q"] ?? "");
$q = mb_substr($q, 0, 100);

$adaFilter = (
    $status !== "" ||
    $prioritas !== "" ||
    $kategori > 0 ||
    $q !== ""
);

// --------------------------------------------------
// 2. Ambil kategori dari database
// --------------------------------------------------

$stmtKategori = $pdo->query(
    "SELECT id, nama FROM kategori ORDER BY nama ASC"
);

$kategoriList = [];

foreach ($stmtKategori->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $kategoriList[(int) $row["id"]] = $row["nama"];
}

// Jika kategori yang diminta tidak tersedia, nonaktifkan filter.
if ($kategori > 0 && !array_key_exists($kategori, $kategoriList)) {
    $kategori = 0;
}

// --------------------------------------------------
// 3. Siapkan kondisi SQL
// --------------------------------------------------

$where = [];
$params = [];

// User hanya dapat melihat laporan miliknya.
// Admin dapat melihat seluruh laporan.
if (!$admin) {
    $where[] = "l.user_id = :user_id";
    $params[":user_id"] = (int) $u["id"];
}

if ($status !== "") {
    $where[] = "l.status = :status";
    $params[":status"] = $status;
}

if ($prioritas !== "") {
    $where[] = "l.prioritas = :prioritas";
    $params[":prioritas"] = $prioritas;
}

if ($kategori > 0) {
    $where[] = "l.kategori_id = :kategori_id";
    $params[":kategori_id"] = $kategori;
}

// Pencarian berdasarkan judul, deskripsi, lokasi,
// kategori, atau nama pelapor.
if ($q !== "") {
    $where[] = "(
        l.judul LIKE :cari_judul
        OR l.deskripsi LIKE :cari_deskripsi
        OR lok.nama LIKE :cari_lokasi
        OR k.nama LIKE :cari_kategori
        OR u.nama LIKE :cari_pelapor
    )";

    $kataCari = "%" . $q . "%";

    $params[":cari_judul"] = $kataCari;
    $params[":cari_deskripsi"] = $kataCari;
    $params[":cari_lokasi"] = $kataCari;
    $params[":cari_kategori"] = $kataCari;
    $params[":cari_pelapor"] = $kataCari;
}

$whereSql = $where
    ? "WHERE " . implode(" AND ", $where)
    : "";

// --------------------------------------------------
// 4. Hitung jumlah laporan sesuai filter
// --------------------------------------------------

$sqlTotal = "
    SELECT COUNT(*)
    FROM laporan l
    JOIN kategori k ON k.id = l.kategori_id
    JOIN lokasi lok ON lok.id = l.lokasi_id
    JOIN users u ON u.id = l.user_id
    $whereSql
";

$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);

$total = (int) $stmtTotal->fetchColumn();

$jumlahHal = max(1, (int) ceil($total / $per));

// Jika nomor halaman melebihi halaman terakhir,
// arahkan kembali ke halaman terakhir yang tersedia.
$hal = min($hal, $jumlahHal);
$offset = ($hal - 1) * $per;

// --------------------------------------------------
// 5. Ambil laporan dari database
// --------------------------------------------------

$sqlLaporan = "
    SELECT
        l.id,
        l.judul,
        l.deskripsi,
        l.prioritas,
        l.status,
        l.created_at AS tanggal,
        l.user_id,
        k.id AS kategori_id,
        k.nama AS kategori,
        lok.nama AS lokasi,
        u.nama AS pelapor
    FROM laporan l
    JOIN kategori k ON k.id = l.kategori_id
    JOIN lokasi lok ON lok.id = l.lokasi_id
    JOIN users u ON u.id = l.user_id
    $whereSql
    ORDER BY l.created_at DESC, l.id DESC
    LIMIT :limit OFFSET :offset
";

$stmtLaporan = $pdo->prepare($sqlLaporan);

// Parameter filter.
foreach ($params as $nama => $nilai) {
    $stmtLaporan->bindValue($nama, $nilai);
}

// LIMIT dan OFFSET harus berupa integer.
$stmtLaporan->bindValue(":limit", $per, PDO::PARAM_INT);
$stmtLaporan->bindValue(":offset", $offset, PDO::PARAM_INT);

$stmtLaporan->execute();

$daftar = $stmtLaporan->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// 6. Hitung angka tab status
// Angka mengikuti hak akses, tetapi tidak terpengaruh
// filter status, kategori, prioritas, atau pencarian.
// --------------------------------------------------

$sqlHitung = "
    SELECT
        COUNT(*) AS semua,
        COALESCE(SUM(status = 'dilaporkan'), 0) AS dilaporkan,
        COALESCE(SUM(status = 'diproses'), 0) AS diproses,
        COALESCE(SUM(status = 'selesai'), 0) AS selesai
    FROM laporan
";

if (!$admin) {
    $sqlHitung .= " WHERE user_id = :user_id";
    $stmtHitung = $pdo->prepare($sqlHitung);
    $stmtHitung->execute([":user_id" => (int) $u["id"]]);
} else {
    $stmtHitung = $pdo->query($sqlHitung);
}

$hitung = $stmtHitung->fetch(PDO::FETCH_ASSOC);

foreach ($hitung as $key => $nilai) {
    $hitung[$key] = (int) $nilai;
}

// --------------------------------------------------
// 7. Informasi pagination
// --------------------------------------------------

$dari = $total > 0 ? $offset + 1 : 0;
$sampai = min($offset + $per, $total);

// --------------------------------------------------
// 8. Tab status dan pembuat link
// --------------------------------------------------

$tabStatus = [
    [
        "kode" => "",
        "label" => "Semua",
        "jumlah" => $hitung["semua"]
    ],
    [
        "kode" => "dilaporkan",
        "label" => "Menunggu",
        "jumlah" => $hitung["dilaporkan"]
    ],
    [
        "kode" => "diproses",
        "label" => "Diproses",
        "jumlah" => $hitung["diproses"]
    ],
    [
        "kode" => "selesai",
        "label" => "Selesai",
        "jumlah" => $hitung["selesai"]
    ],
];

function linkLaporan(
    $status,
    $kategori,
    $prioritas,
    $hal = 0,
    $q = ""
) {
    $param = [];

    if ($status !== "") {
        $param["status"] = $status;
    }

    if ($kategori > 0) {
        $param["kategori"] = $kategori;
    }

    if ($prioritas !== "") {
        $param["prioritas"] = $prioritas;
    }

    if ($q !== "") {
        $param["q"] = $q;
    }

    if ($hal > 0) {
        $param["hal"] = $hal;
    }

    return "laporan.php" .
        ($param ? "?" . http_build_query($param) : "");
}

// --------------------------------------------------
// 9. Persiapan tampilan
// --------------------------------------------------

$judul = "Upkeep";
$jsHalaman = ["assets/js/laporan.js", "assets/js/popup_laporan.js"];

$inp = "rounded-md border border-black/20 bg-white px-3 py-2 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30";

require "../header.php";
?>


<!-- ===== Judul halaman ===== -->
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h2 class="text-2xl font-bold tracking-tight">Semua Laporan</h2>
  </div>

  <?php if ($admin): ?>
    <a href="rekap.php" class="inline-flex items-center gap-2 rounded-md border border-black/20 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-100">
      <i data-lucide="file-down" class="h-4 w-4"></i> Rekap & Export
    </a>
  <?php else: ?>
    <a href="tambah_laporan.php" class="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
      <i data-lucide="circle-plus" class="h-4 w-4"></i> Buat Laporan
    </a>
  <?php endif; ?>
</div>

<section class="rounded-xl border border-black/10 bg-white">

  <!-- ===== Tab status: Semua / Menunggu / Diproses / Selesai ===== -->
  <div class="flex flex-wrap gap-2 border-b border-black/5 p-4">
    <?php foreach ($tabStatus as $tab): ?>
      <?php
        // Tab yang sedang dipilih berwarna merah, yang lain abu-abu
        $tabAktif = ($status === $tab["kode"]);
        if ($tabAktif) {
          $kelasTab   = "bg-red-600 text-white";
          $kelasAngka = "bg-white/25";
        } else {
          $kelasTab   = "bg-slate-100 text-black hover:bg-slate-200";
          $kelasAngka = "bg-black/10";
        }
      ?>
      <a href="<?= e(linkLaporan($tab["kode"], $kategori, $prioritas, 0, $q)) ?>"
         class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors <?= $kelasTab ?>">
        <?= e($tab["label"]) ?>
        <span class="rounded-full px-1.5 text-xs tabular-nums <?= $kelasAngka ?>"><?= (int) $tab["jumlah"] ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ===== Pencarian & filter ===== -->
  <div class="flex flex-wrap items-center gap-3 border-b border-black/5 p-4">

    <!-- Pencarian langsung di tabel (dijalankan app.js lewat atribut data-cari) -->  
    <form method="get" action="laporan.php"
        class="relative min-w-[200px] flex-1">

        <i data-lucide="search"
        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>

        <input
            type="search"
            name="q"
            value="<?= e($q) ?>"
            placeholder="Cari judul, lokasi, kategori..."
            aria-label="Cari laporan"
            class="<?= $inp ?> w-full pl-9 pr-20"
        >

        <?php if ($q !== ""): ?>
            <input type="hidden" name="q" value="<?= e($q) ?>">
        <?php endif; ?>

        <?php if ($status !== ""): ?>
            <input type="hidden" name="status" value="<?= e($status) ?>">
        <?php endif; ?>

        <?php if ($kategori > 0): ?>
            <input type="hidden" name="kategori" value="<?= $kategori ?>">
        <?php endif; ?>

        <?php if ($prioritas !== ""): ?>
            <input type="hidden" name="prioritas" value="<?= e($prioritas) ?>">
        <?php endif; ?>

        <button
            type="submit"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50"
        >
            Cari
        </button>
    </form>


    <!-- Filter kategori & prioritas (terkirim otomatis lewat atribut data-auto-submit) -->
    <form method="get" action="laporan.php" data-auto-submit class="flex flex-wrap items-center gap-3">
      <input type="hidden" name="status" value="<?= e($status) ?>">

      <select name="kategori" class="<?= $inp ?>" aria-label="Filter kategori">
        <option value="0">Semua kategori</option>
        <?php foreach ($kategoriList as $id => $nama): ?>
          <option value="<?= $id ?>" <?= $kategori === $id ? "selected" : "" ?>><?= e($nama) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="prioritas" class="<?= $inp ?>" aria-label="Filter prioritas">
        <option value="">Semua prioritas</option>
        <?php foreach ($prioritasBoleh as $p): ?>
          <option value="<?= $p ?>" <?= $prioritas === $p ? "selected" : "" ?>><?= ucfirst($p) ?></option>
        <?php endforeach; ?>
      </select>

      <?php if ($adaFilter): ?>
        <a href="laporan.php" class="text-sm font-semibold text-red-700 hover:underline">Reset</a>
      <?php endif; ?>
    </form>
  </div>


  <?php if (empty($daftar)): ?>

  <!-- ===== Tampilan kosong (belum ada laporan / filter tidak cocok) ===== -->
  <div class="px-5 py-16 text-center">

    <?php
      // Ikon berbeda: kaca pembesar kalau karena filter, kotak masuk kalau memang kosong
      if ($adaFilter) {
        $ikonKosong = "search-x";
      } else {
        $ikonKosong = "inbox";
      }
    ?>
    <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
      <i data-lucide="<?= $ikonKosong ?>" class="h-6 w-6"></i>
    </span>

    <?php if ($adaFilter): ?>
      <p class="font-semibold">Tidak ada laporan yang cocok</p>
      <p class="mb-4 text-sm text-slate-600">Coba ubah atau hapus filter yang sedang dipakai.</p>
      <a href="laporan.php" class="inline-flex rounded-md border border-black/20 px-4 py-2 text-sm font-semibold hover:bg-slate-100">Reset filter</a>

    <?php elseif ($admin): ?>
      <p class="font-semibold">Belum ada laporan masuk</p>
      <p class="text-sm text-slate-600">Laporan dari civitas kampus akan muncul di sini.</p>

    <?php else: ?>
      <p class="font-semibold">Kamu belum membuat laporan</p>
      <p class="mb-4 text-sm text-slate-600">Lihat sesuatu yang rusak di kampus? Laporkan sekarang.</p>
      <a href="tambah_laporan.php" class="inline-flex rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Buat Laporan</a>
    <?php endif; ?>
  </div>

  <?php else: ?>

  <!-- ===== Tabel laporan ===== -->
  <div class="overflow-x-auto">
    <table id="tabelLaporan" class="w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-500">
        <tr>
          <th class="px-5 py-3">Laporan</th>
          <?php if ($admin): ?><th class="px-5 py-3">Pelapor</th><?php endif; ?>
          <th class="px-5 py-3">Kategori</th>
          <th class="px-5 py-3">Prioritas</th>
          <th class="px-5 py-3">Tanggal</th>
          <th class="px-5 py-3">Status</th>
          <th class="px-5 py-3 text-right">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-black/5">
        <?php foreach ($daftar as $r): ?>
        <?php
          // Siapkan nilai untuk satu baris, supaya HTML di bawah mudah dibaca
          $idLaporan = (int) $r["id"];

          // Tombol pertama: admin "Tangani" kalau belum selesai, selain itu "Detail"
          if ($admin && $r["status"] !== "selesai") {
            $teksLihat = "Tangani";
          } else {
            $teksLihat = "Detail";
          }

          // Pelapor hanya boleh edit saat laporan masih "dilaporkan" (belum diproses)
          $bolehEdit = !$admin && $r["status"] === "dilaporkan";

          // Admin boleh hapus kapan saja. Pelapor hanya boleh membatalkan saat masih "dilaporkan".
          $bolehHapus = $admin || $r["status"] === "dilaporkan";

          if ($admin) {
            $teksHapus  = "Hapus";
            $pesanHapus = "Hapus laporan ini?";
          } else {
            $teksHapus  = "Batalkan";
            $pesanHapus = "Batalkan laporan ini?";
          }
        ?>
        <tr class="hover:bg-slate-50">

          <td class="px-5 py-3">
              <button type="button" data-popup="lihat" data-mode="detail" data-id="<?= $idLaporan ?>" data-judul="<?= e($r['judul']) ?>" data-lokasi="<?= e($r['lokasi']) ?>" data-pelapor="<?= e($r['pelapor'] ?? $u['nama'] ?? 'Pelapor') ?>" data-tanggal="<?= e($r['tanggal']) ?>" data-kategori="<?= e($r['kategori']) ?>" data-prioritas="<?= e($r['prioritas']) ?>" data-status="<?= e($r['status']) ?>" data-deskripsi="<?= e($r['deskripsi'] ?? 'Belum ada deskripsi.') ?>" class="font-semibold hover:text-red-700">
                  <?= e($r["judul"]) ?>
              </button>
            <p class="text-xs text-slate-500"><?= e($r["lokasi"]) ?></p>
          </td>

          <?php if ($admin): ?>
            <td class="px-5 py-3 text-slate-700"><?= e($r["pelapor"]) ?></td>
          <?php endif; ?>

          <td class="whitespace-nowrap px-5 py-3 text-slate-700"><?= e($r["kategori"]) ?></td>
          <td class="px-5 py-3"><?= badgePrioritas($r["prioritas"]) ?></td>
          <td class="whitespace-nowrap px-5 py-3 text-slate-700"><?= tglIndo($r["tanggal"]) ?></td>
          <td class="px-5 py-3"><?= badgeStatus($r["status"]) ?></td>

          <td class="px-5 py-3">
            <div class="flex items-center justify-end gap-3">

              <!-- Lihat detail / tangani -->
            <button type="button" data-popup="lihat" data-mode="<?= $teksLihat === 'Tangani' ? 'tangani' : 'detail' ?>" data-id="<?= $idLaporan ?>" data-judul="<?= e($r['judul']) ?>" data-lokasi="<?= e($r['lokasi']) ?>" data-pelapor="<?= e($r['pelapor'] ?? $u['nama'] ?? 'Pelapor') ?>" data-tanggal="<?= e($r['tanggal']) ?>" data-kategori="<?= e($r['kategori']) ?>" data-prioritas="<?= e($r['prioritas']) ?>" data-status="<?= e($r['status']) ?>" data-deskripsi="<?= e($r['deskripsi'] ?? 'Belum ada deskripsi.') ?>" class="inline-flex items-center border border-red-600 rounded-md p-3 gap-1 bg-white font-semibold text-red-700 hover:text-white hover:bg-red-600 transition duration-300 ease-in-out">
                <i data-lucide="eye" class="h-4 w-4"></i><?= $teksLihat ?>
            </button>


              <!-- Edit (khusus pelapor, laporan belum diproses) -->
              <?php if ($bolehEdit): ?>
                <button type="button" data-popup="lihat" data-mode="edit" data-id="<?= $idLaporan ?>" data-judul="<?= e($r['judul']) ?>" data-lokasi="<?= e($r['lokasi']) ?>" data-kategori="<?= e($r['kategori']) ?>" data-prioritas="<?= e($r['prioritas']) ?>" data-status="<?= e($r['status']) ?>" data-tanggal="<?= e($r['tanggal']) ?>" data-pelapor="<?= e($r['pelapor'] ?? $u['nama'] ?? 'Pelapor') ?>" data-deskripsi="<?= e($r['deskripsi'] ?? '') ?>" class="inline-flex items-center border border-blue-600 rounded-md p-3 gap-1 bg-white font-semibold text-blue-700 hover:text-white hover:bg-blue-600 transition duration-300 ease-in-out">
                  <i data-lucide="pencil" class="h-4 w-4"></i>Edit
                </button>
              <?php endif; ?>

              <!-- Hapus / batalkan (konfirmasi muncul lewat app.js, atribut data-konfirmasi) -->
              <?php if ($bolehHapus): ?>
                <form action="hapus.php" method="post" data-konfirmasi="<?= $pesanHapus ?>">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $idLaporan ?>">
                  <button class="inline-flex items-center gap-1 font-semibold text-slate-500 border border-slate-600 rounded-md p-3 gap-1 bg-white font-semibold hover:text-white hover:bg-slate-600 transition duration-300 ease-in-out">
                    <i data-lucide="trash-2" class="h-4 w-4"></i><?= $teksHapus ?>
                  </button>
                </form>
              <?php endif; ?>

            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Muncul kalau pencarian live tidak menemukan apa pun (diatur laporan.js) -->
  <p id="kosongCari" class="hidden px-5 py-10 text-center text-sm text-slate-600">Tidak ada hasil untuk pencarian ini di halaman ini.</p>

  <!-- ===== Info jumlah & nomor halaman ===== -->
  <div class="flex flex-wrap items-center justify-between gap-3 border-t border-black/5 px-5 py-3 text-sm">
    <p class="text-slate-600">Menampilkan <?= $dari ?>–<?= $sampai ?> dari <?= $total ?> laporan</p>

    <?php if ($jumlahHal > 1): ?>
    <nav class="flex gap-1" aria-label="Halaman">
      <?php for ($i = 1; $i <= $jumlahHal; $i++): ?>
        <?php
          // Halaman yang sedang dibuka berwarna merah
          if ($i === $hal) {
            $kelasHal = "bg-red-600 text-white";
          } else {
            $kelasHal = "hover:bg-slate-200";
          }
        ?>
        <a href="<?= e(linkLaporan($status, $kategori, $prioritas, $i, $q)) ?>"
           class="rounded-md px-3 py-1.5 font-semibold <?= $kelasHal ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</section>

<!-- ===== Pop-up laporan ===== -->
<div id="popupLaporan"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">

    <div class="w-full max-w-xl rounded-xl border border-black/10 bg-white shadow-xl">

        <!-- Header pop-up -->
        <div class="flex items-center justify-between border-b border-black/5 p-5">
            <div>
                <h3 id="popupJudul" class="text-lg font-bold tracking-tight">
                    Detail Laporan
                </h3>
                <p id="popupSubjudul" class="text-sm text-slate-500">
                    Informasi laporan kerusakan
                </p>
            </div>

            <button type="button"
                    id="popupTutup"
                    aria-label="Tutup pop-up"
                    class="rounded-md p-2 text-slate-500 hover:bg-slate-100">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <!-- Konten detail -->
        <div id="popupDetail" class="space-y-4 p-5">
            <div>
                <p class="text-xs text-slate-500">Judul laporan</p>
                <p id="detailJudul" class="font-semibold"></p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-slate-500">Pelapor</p>
                    <p id="detailPelapor" class="text-sm font-medium"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Tanggal</p>
                    <p id="detailTanggal" class="text-sm font-medium"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Kategori</p>
                    <p id="detailKategori" class="text-sm font-medium"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Prioritas</p>
                    <p id="detailPrioritas" class="text-sm font-medium"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Status</p>
                    <p id="detailStatus" class="text-sm font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Lokasi</p>
                    <p id="detailLokasi" class="text-sm font-medium"></p>
                </div>
            </div>

            <div>
                <p class="mb-1 text-xs text-slate-500">Deskripsi</p>
                <p id="detailDeskripsi"
                   class="whitespace-pre-line text-sm leading-relaxed text-slate-700"></p>
            </div>
        </div>

        <!-- Form edit pelapor -->
        <form id="formEdit" class="hidden space-y-4 p-5">
            <input type="hidden" name="id" id="editId">

            <div>
                <label for="editJudul"
                       class="mb-1 block text-sm font-semibold">
                    Judul laporan
                </label>
                <input type="text" name="judul" id="editJudul"
                       required
                       class="w-full rounded-md border border-black/20 bg-white px-3 py-2 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30">
            </div>

            <div>
                <label for="editLokasi"
                       class="mb-1 block text-sm font-semibold">
                    Lokasi
                </label>
                <input type="text" name="lokasi" id="editLokasi"
                       required
                       class="w-full rounded-md border border-black/20 bg-white px-3 py-2 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30">
            </div>

            <div>
                <label for="editKategori"
                       class="mb-1 block text-sm font-semibold">
                    Kategori
                </label>
                <input type="text" name="kategori" id="editKategori"
                       required
                       class="w-full rounded-md border border-black/20 bg-white px-3 py-2 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30">
            </div>

            <div>
                <label for="editPrioritas"
                       class="mb-1 block text-sm font-semibold">
                    Prioritas
                </label>
                <select name="prioritas" id="editPrioritas"
                        class="w-full rounded-md border border-black/20 bg-white px-3 py-2 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30">
                    <option value="rendah">Rendah</option>
                    <option value="sedang">Sedang</option>
                    <option value="tinggi">Tinggi</option>
                </select>
            </div>

            <div>
                <label for="editDeskripsi"
                       class="mb-1 block text-sm font-semibold">
                    Deskripsi
                </label>
                <textarea name="deskripsi" id="editDeskripsi" rows="4"
                          class="w-full rounded-md border border-black/20 bg-white px-3 py-2 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30"></textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-black/5 pt-4">
                <button type="button" data-popup-batal
                        class="rounded-md border border-black/20 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Simpan perubahan
                </button>
            </div>
        </form>

        <!-- Form penanganan admin -->
        <form id="formTangani" class="hidden space-y-4 p-5">
            <input type="hidden" name="id" id="tanganiId">

            <div>
                <p class="text-sm text-slate-500">Laporan yang ditangani</p>
                <p id="tanganiJudul" class="font-semibold"></p>
            </div>

            <div>
                <label for="tanganiStatus"
                       class="mb-1 block text-sm font-semibold">
                    Ubah status laporan
                </label>
                <select name="status" id="tanganiStatus"
                        class="w-full rounded-md border border-black/20 bg-white px-3 py-2 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30">
                    <option value="dilaporkan">Dilaporkan</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 border-t border-black/5 pt-4">
                <button type="button" data-popup-batal
                        class="rounded-md border border-black/20 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Simpan status
                </button>
            </div>
        </form>

        <!-- Footer khusus tampilan detail -->
        <div id="popupFooter"
             class="flex justify-end border-t border-black/5 p-5">
            <button type="button" data-popup-batal
                    class="rounded-md border border-black/20 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-100">
                Tutup
            </button>
        </div>

    </div>
</div>

<?php require "../footer.php"; ?>