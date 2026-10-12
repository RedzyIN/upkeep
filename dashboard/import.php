
<?php
require "../init.php";
require "../data/dummy.php";

previewLogin();

// Mengirim respons JSON dan menghentikan proses.
function balasJson(array $data, int $kode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($kode);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

// Endpoint hanya diproses untuk POST dengan body JSON.
$isJson = stripos(
    $_SERVER["CONTENT_TYPE"] ?? "",
    "application/json"
) !== false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && $isJson) {

    // 1. Periksa login dan hak akses.
    if (!userLogin()) {
        balasJson(["error" => "Silakan login terlebih dahulu."], 401);
    }

    if (!isAdmin()) {
        balasJson(["error" => "Hanya admin yang boleh melakukan import."], 403);
    }

    // 2. Periksa token CSRF dari header fetch.
    $tokenSesi = $_SESSION["csrf"] ?? "";
    $tokenKirim = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

    if (
        !is_string($tokenSesi) ||
        $tokenSesi === "" ||
        !is_string($tokenKirim) ||
        $tokenKirim === "" ||
        !hash_equals($tokenSesi, $tokenKirim)
    ) {
        balasJson([
            "error" => "Token CSRF tidak valid. Muat ulang halaman."
        ], 419);
    }

    // 3. Baca dan validasi JSON.
    $isi = json_decode(file_get_contents("php://input"), true);

    if (!is_array($isi) || json_last_error() !== JSON_ERROR_NONE) {
        balasJson(["error" => "Body JSON tidak valid."], 400);
    }

    $jenis = $isi["jenis"] ?? "";
    $baris = $isi["baris"] ?? null;

    // Daftar putih: nama tabel tidak berasal langsung dari input pengguna.
    $tabelBoleh = [
        "kategori" => [
            "tabel" => "kategori",
            "maks" => 50
        ],
        "lokasi" => [
            "tabel" => "lokasi",
            "maks" => 100
        ],
    ];

    if (!is_string($jenis) || !isset($tabelBoleh[$jenis])) {
        balasJson(["error" => "Jenis import tidak dikenali."], 400);
    }

    if (!is_array($baris) || count($baris) === 0) {
        balasJson(["error" => "Tidak ada data untuk diimpor."], 400);
    }

    if (count($baris) > 1000) {
        balasJson(["error" => "Maksimal 1000 baris per import."], 400);
    }

    $tabel = $tabelBoleh[$jenis]["tabel"];
    $maks = $tabelBoleh[$jenis]["maks"];

    $berhasil = 0;
    $dilewati = 0;
    $sudahAda = [];

    try {
        $pdo->beginTransaction();

        // Aman karena nama tabel berasal dari daftar putih di atas.
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO `$tabel` (`nama`) VALUES (?)"
        );

        foreach ($baris as $item) {
            // Mendukung teks langsung atau objek {"nama": "..."}.
            $nama = is_array($item)
                ? ($item["nama"] ?? "")
                : $item;

            // Nama harus berupa teks atau nilai skalar.
            if (!is_scalar($nama)) {
                $dilewati++;
                continue;
            }

            $nama = trim((string) $nama);

            $panjang = function_exists("mb_strlen")
                ? mb_strlen($nama, "UTF-8")
                : strlen($nama);

            $kunci = function_exists("mb_strtolower")
                ? mb_strtolower($nama, "UTF-8")
                : strtolower($nama);

            // Lewati nama kosong, terlalu panjang, atau duplikat dalam file.
            if (
                $nama === "" ||
                $panjang > $maks ||
                isset($sudahAda[$kunci])
            ) {
                $dilewati++;
                continue;
            }

            $sudahAda[$kunci] = true;

            $stmt->execute([$nama]);

            // INSERT IGNORE: rowCount 1 = masuk, 0 = sudah ada/diabaikan.
            if ($stmt->rowCount() > 0) {
                $berhasil++;
            } else {
                $dilewati++;
            }
        }

        $pdo->commit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Import Upkeep gagal: " . $e->getMessage());

        balasJson([
            "error" => "Gagal menyimpan data ke database."
        ], 500);
    }

    balasJson([
        "berhasil" => $berhasil,
        "dilewati" => $dilewati
    ]);
}

// Mulai bagian halaman HTML biasa.
wajibLogin();

$admin = isAdmin();

if (!$admin) {
    header("Location: laporan.php");
    exit;
}

$judul = "Import Data Excel";
$jsHalaman = ["assets/js/import.js"];

require "../header.php";
?>

<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    <!-- Token CSRF, (input name="csrf") dibaca import.js lalu dikirim lewat header -->
    <?= csrfField() ?>

    <!-- Header halaman -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Import Data Excel</h1>

        <button type="button" id="unduhTemplate" class="inline-flex items-center justify-center gap-2 rounded-md border border-red-600 bg-white px-4 py-3 font-semibold text-red-700 transition hover:bg-red-50">
            <i data-lucide="download" class="h-4 w-4"></i>
            Download Template
        </button>
    </div>

    <!-- Informasi format -->
    <div class="flex gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0 text-blue-700"></i>

        <div class="text-sm text-blue-900">
            <p class="font-semibold">Petunjuk Import</p>
            <p class="mt-1">
                Gunakan template yang disediakan. Isi kolom Judul, Lokasi,
                Kategori, Prioritas, dan Deskripsi. Pastikan data sudah benar
                sebelum melanjutkan.
            </p>
        </div>
    </div>

    <div>
    <label for="jenisImport"
        class="mb-2 block text-sm font-semibold text-gray-700">
        Jenis data
    </label>

        <select id="jenisImport" class="w-full rounded-md border border-gray-300 bg-white px-3 py-3 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-100">
            <option value="kategori">Kategori</option>
            <option value="lokasi">Lokasi</option>
        </select>
    </div>

    <!-- Area upload -->
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="mb-5 text-lg font-bold text-gray-900">Unggah File</h2>

        <label for="fileExcel" class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-5 py-10 text-center transition hover:border-red-400 hover:bg-red-50/30">
            <span class="mb-3 rounded-full bg-green-100 p-3">
                <i data-lucide="file-spreadsheet" class="h-8 w-8 text-green-700"></i>
            </span>

            <span class="font-semibold text-gray-800">
                Pilih file Excel
            </span>

            <span class="mt-1 text-sm text-gray-500">
                Klik area ini untuk memilih file .xlsx
            </span>

            <span id="namaFile" class="mt-3 break-all text-sm font-medium text-gray-700">Belum ada file dipilih</span>
        </label>

        <input type="file" id="fileExcel" class="hidden" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">

        <p id="pesanUpload" class="mt-3 text-sm" role="status" aria-live="polite"></p>
    </section>

    <!-- Preview tabel -->
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">
                    Preview Data
                </h2>
            </div>

            <span id="jumlahData" class="text-sm font-medium text-gray-600">0 data</span>
        </div>

        <div id="pesanPreview" class="p-6 text-center text-sm text-gray-500">
            Belum ada data. Unggah file Excel untuk menampilkan preview.
        </div>

        <div id="wadahTabel" class="hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-700">No.</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-700">Judul</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-700">Lokasi</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-700">Kategori</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-700">Prioritas</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-700">Deskripsi</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-700">Validasi</th>
                    </tr>
                </thead>

                <tbody id="isiTabel" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </section>

    <!-- Tombol aksi -->
    <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
        <button
            type="button"
            id="resetImport"
            class="rounded-md border border-gray-300 bg-white px-5 py-3
                   font-semibold text-gray-700 transition hover:bg-gray-50"
        >
            Reset
        </button>

        <button
            type="button"
            id="konfirmasiImport"
            disabled
            class="inline-flex items-center justify-center gap-2 rounded-md
                   bg-red-600 px-5 py-3 font-semibold text-white
                   transition hover:bg-red-700
                   disabled:cursor-not-allowed disabled:opacity-50"
        >
            <i data-lucide="check-circle" class="h-4 w-4"></i>
            Konfirmasi Import
        </button>
    </div>

    <p class="text-sm text-gray-500">
        Preview berjalan di browser. Data belum tersimpan ke database.
    </p>

</div>

<?php require "../footer.php"; ?>