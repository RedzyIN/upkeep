<?php
require "../init.php";
require "../data/dummy.php";

previewLogin();
wajibLogin();

$u = userLogin();
$admin = isAdmin();

// Periode default: tanggal 1 sampai hari ini pada bulan berjalan.
$hariIni = date("Y-m-d");
$awalBulan = date("Y-m-01");

$tanggalMulai = $_GET["mulai"] ?? $awalBulan;
$tanggalAkhir = $_GET["akhir"] ?? $hariIni;

// Validasi format tanggal.
$validMulai = DateTime::createFromFormat("!Y-m-d", $tanggalMulai);
$validAkhir = DateTime::createFromFormat("!Y-m-d", $tanggalAkhir);

if (
    !$validMulai ||
    $validMulai->format("Y-m-d") !== $tanggalMulai ||
    !$validAkhir ||
    $validAkhir->format("Y-m-d") !== $tanggalAkhir
) {
    $tanggalMulai = $awalBulan;
    $tanggalAkhir = $hariIni;
    $pesanError = "Format tanggal tidak valid.";
} elseif ($tanggalMulai > $tanggalAkhir) {
    $tanggalMulai = $awalBulan;
    $tanggalAkhir = $hariIni;
    $pesanError = "Tanggal mulai tidak boleh melebihi tanggal akhir.";
} else {
    $pesanError = "";
}

// Ambil data laporan. Filter status, kategori, dan prioritas dikosongkan
// agar rekap memuat semua status laporan yang boleh dilihat pengguna.
$daftar = ambilRekap($pdo, $admin, $u["id"], $tanggalMulai, $tanggalAkhir);

// Angka 10000 digunakan agar seluruh data dummy dapat dimuat sekaligus.

// Mendukung format tanggal ISO (Y-m-d), d-m-Y, dan d/m/Y.
function tanggalUntukRekap($tanggal): ?string
{
    foreach (["!Y-m-d", "!d-m-Y", "!d/m/Y"] as $format) {
        $objekTanggal = DateTime::createFromFormat($format, (string) $tanggal);

        if (
            $objekTanggal &&
            $objekTanggal->format(substr($format, 1)) === $tanggal
        ) {
            return $objekTanggal->format("Y-m-d");
        }
    }

    $timestamp = strtotime((string) $tanggal);

    return $timestamp !== false
        ? date("Y-m-d", $timestamp)
        : null;
}

// Saring berdasarkan periode yang dipilih.

// Hitung statistik.
$totalLaporan = count($daftar);
$totalDilaporkan = 0;
$totalDiproses = 0;
$totalSelesai = 0;

foreach ($daftar as $laporan) {
    $status = strtolower($laporan["status"] ?? "");

    if ($status === "dilaporkan") {
        $totalDilaporkan++;
    } elseif ($status === "diproses") {
        $totalDiproses++;
    } elseif ($status === "selesai") {
        $totalSelesai++;
    }
}

$judul = "Rekap Laporan";
$jsHalaman = ["assets/js/export.js"];

require "../header.php";
?>

<main class="space-y-6 p-4 md:p-6 lg:px-8 py-8">

    <!-- Judul halaman -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            Rekap Laporan
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            Ringkasan laporan kerusakan berdasarkan periode yang dipilih.
        </p>
    </div>

    <!-- Filter periode -->
    <section class="bg-white border border-gray-200 rounded-xl p-5 mb-6">
        <form method="GET" class="flex flex-col md:flex-row md:items-end gap-4">

            <div class="flex-1">
                <label for="tanggalMulai"
                       class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal mulai
                </label>
                <input
                    type="date"
                    id="tanggalMulai"
                    name="mulai"
                    value="<?= e($tanggalMulai) ?>"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500"
                >
            </div>

            <div class="flex-1">
                <label for="tanggalAkhir"
                       class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal akhir
                </label>
                <input
                    type="date"
                    id="tanggalAkhir"
                    name="akhir"
                    value="<?= e($tanggalAkhir) ?>"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500"
                >
            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition"
            >
                <i data-lucide="filter" class="h-4 w-4"></i>
                Tampilkan Rekap
            </button>
        </form>

        <?php if ($pesanError !== ""): ?>
            <p class="mt-3 text-sm text-red-600">
                <?= e($pesanError) ?>
            </p>
        <?php endif; ?>
    </section>

    <!-- Kartu statistik -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Total Laporan</p>
                <i data-lucide="clipboard-list" class="h-5 w-5 text-red-600"></i>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-900">
                <?= $totalLaporan ?>
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Dilaporkan</p>
                <i data-lucide="file-text" class="h-5 w-5 text-yellow-600"></i>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-900">
                <?= $totalDilaporkan ?>
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Diproses</p>
                <i data-lucide="clock-3" class="h-5 w-5 text-blue-600"></i>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-900">
                <?= $totalDiproses ?>
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Selesai</p>
                <i data-lucide="check-circle-2" class="h-5 w-5 text-green-600"></i>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-900">
                <?= $totalSelesai ?>
            </p>
        </div>

    </section>

    <!-- Tabel rekap -->
    <section class="bg-white border border-gray-200 rounded-xl overflow-hidden">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-gray-200">
            <div>
                <h2 class="font-semibold text-gray-900">
                    Data Laporan
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Periode
                    <?= e(date("d-m-Y", strtotime($tanggalMulai))) ?>
                    sampai
                    <?= e(date("d-m-Y", strtotime($tanggalAkhir))) ?>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    id="btnExportExcel"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-800 transition"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                    Export Excel
                </button>

                <button
                    type="button"
                    id="btnExportPDF"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition"
                >
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    Export PDF
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="tabelRekap" class="w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-5 py-3">No.</th>
                        <th class="px-5 py-3">Judul Laporan</th>
                        <th class="px-5 py-3">Lokasi</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3">Prioritas</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Pelapor</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($daftar)): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-gray-500">
                                Tidak ada laporan pada periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar as $index => $r): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <?= $index + 1 ?>
                                </td>

                                <td class="px-5 py-3 font-medium text-gray-900">
                                    <?= e($r["judul"] ?? "-") ?>
                                </td>

                                <td class="px-5 py-3">
                                    <?= e($r["lokasi"] ?? "-") ?>
                                </td>

                                <td class="px-5 py-3">
                                    <?= e($r["kategori"] ?? "-") ?>
                                </td>

                                <td class="px-5 py-3">
                                    <?= e(ucfirst($r["prioritas"] ?? "-")) ?>
                                </td>

                                <td class="px-5 py-3 whitespace-nowrap">
                                    <?php
                                    $tanggalTampil = tanggalUntukRekap($r["tanggal"] ?? "");
                                    echo e(
                                        $tanggalTampil
                                            ? date("d-m-Y", strtotime($tanggalTampil))
                                            : ($r["tanggal"] ?? "-")
                                    );
                                    ?>
                                </td>

                                <td class="px-5 py-3">
                                    <?= e($r["pelapor"] ?? "-") ?>
                                </td>

                                <td class="px-5 py-3 whitespace-nowrap">
                                    <?= function_exists("badgeStatus")
                                        ? badgeStatus($r["status"] ?? "")
                                        : e(ucfirst($r["status"] ?? "-")) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 text-sm text-gray-500">
            Menampilkan <?= $totalLaporan ?> laporan pada periode terpilih.
        </div>

    </section>

<!-- Library ekspor harus dimuat sebelum export.js. -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<?php require "../footer.php"; ?>
