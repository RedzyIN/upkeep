<?php

require "../init.php";

// Halaman ini hanya untuk user/pelapor.
if (!userLogin()) {
    setFlash("warning", "Silakan login terlebih dahulu.");
    redirect("../auth/login.php");
}

if (isAdmin()) {
    setFlash("warning", "Halaman Buat Laporan hanya untuk pelapor.");
    redirect("laporan.php");
}

// Periksa CSRF sebelum memproses data POST.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrfCek();
}

$judul = "Buat Laporan";
$jsHalaman = ["assets/js/tambah.js"];

$errorUmum = "";
$old = [
    "judul" => "",
    "kategori_id" => "",
    "lokasi_id" => "",
    "prioritas" => "sedang",
    "deskripsi" => ""
];

// Ambil kategori dan lokasi dari database.
try {
    $kategoriList = $pdo->query(
        "SELECT id, nama FROM kategori ORDER BY nama"
    )->fetchAll(PDO::FETCH_ASSOC);

    $lokasiList = $pdo->query(
        "SELECT id, nama FROM lokasi ORDER BY nama"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $kategoriList = [];
    $lokasiList = [];
    $errorUmum = "Kategori atau lokasi gagal dimuat. Periksa koneksi database.";
}

// Proses ketika form dikirim.
if ($_SERVER["REQUEST_METHOD"] === "POST" && $errorUmum === "") {

    // Ambil kembali input agar tidak hilang ketika validasi gagal.
    $old["judul"] = trim($_POST["judul"] ?? "");
    $old["kategori_id"] = trim($_POST["kategori_id"] ?? "");
    $old["lokasi_id"] = trim($_POST["lokasi_id"] ?? "");
    $old["prioritas"] = $_POST["prioritas"] ?? "sedang";
    $old["deskripsi"] = trim($_POST["deskripsi"] ?? "");

    $judulLaporan = $old["judul"];
    $kategoriId = filter_var(
        $old["kategori_id"],
        FILTER_VALIDATE_INT
    );
    $lokasiId = filter_var(
        $old["lokasi_id"],
        FILTER_VALIDATE_INT
    );
    $prioritas = $old["prioritas"];
    $deskripsi = $old["deskripsi"];

    // Validasi judul.
    if (
        mb_strlen($judulLaporan) < 5 ||
        mb_strlen($judulLaporan) > 150
    ) {
        $errorUmum = "Judul harus berisi 5–150 karakter.";
    }

    // Validasi kategori harus benar-benar ada di database.
    $kategoriValid = array_map(
        "intval",
        array_column($kategoriList, "id")
    );

    if (
        $errorUmum === "" &&
        (
            $kategoriId === false ||
            !in_array($kategoriId, $kategoriValid, true)
        )
    ) {
        $errorUmum = "Pilih kategori yang valid.";
    }

    // Validasi lokasi harus benar-benar ada di database.
    $lokasiValid = array_map(
        "intval",
        array_column($lokasiList, "id")
    );

    if (
        $errorUmum === "" &&
        (
            $lokasiId === false ||
            !in_array($lokasiId, $lokasiValid, true)
        )
    ) {
        $errorUmum = "Pilih lokasi yang valid.";
    }

    // Validasi prioritas berdasarkan ENUM di database.
    $prioritasBoleh = ["rendah", "sedang", "tinggi"];

    if (
        $errorUmum === "" &&
        !in_array($prioritas, $prioritasBoleh, true)
    ) {
        $errorUmum = "Prioritas laporan tidak valid.";
    }

    // Validasi deskripsi.
    if (
        $errorUmum === "" &&
        (
            mb_strlen($deskripsi) < 20 ||
            mb_strlen($deskripsi) > 2000
        )
    ) {
        $errorUmum = "Deskripsi harus berisi 20–2000 karakter.";
    }

    // Upload foto hanya jika semua input teks valid.
    $namaFoto = null;

    if ($errorUmum === "") {
        $fileFoto = $_FILES["foto"] ?? [
            "error" => UPLOAD_ERR_NO_FILE
        ];

        [$uploadBerhasil, $hasilUpload] = uploadFoto(
            $fileFoto,
            "laporan"
        );

        if (!$uploadBerhasil) {
            $errorUmum = $hasilUpload;
        } else {
            $namaFoto = $hasilUpload;
        }
    }

    // Simpan laporan dan status awal dalam satu transaksi.
    if ($errorUmum === "") {
        try {
            $pdo->beginTransaction();

            $sqlLaporan = "
                INSERT INTO laporan
                    (
                        user_id,
                        kategori_id,
                        lokasi_id,
                        judul,
                        deskripsi,
                        foto,
                        prioritas,
                        status
                    )
                VALUES
                    (
                        :user_id,
                        :kategori_id,
                        :lokasi_id,
                        :judul,
                        :deskripsi,
                        :foto,
                        :prioritas,
                        'dilaporkan'
                    )
            ";

            $stmt = $pdo->prepare($sqlLaporan);
            $stmt->execute([
                ":user_id" => (int) userLogin()["id"],
                ":kategori_id" => $kategoriId,
                ":lokasi_id" => $lokasiId,
                ":judul" => $judulLaporan,
                ":deskripsi" => $deskripsi,
                ":foto" => $namaFoto,
                ":prioritas" => $prioritas
            ]);

            $idLaporan = (int) $pdo->lastInsertId();

            // Catat status awal untuk timeline laporan.
            $stmtRiwayat = $pdo->prepare("
                INSERT INTO riwayat_status
                    (laporan_id, status, catatan)
                VALUES
                    (:laporan_id, 'dilaporkan', :catatan)
            ");

            $stmtRiwayat->execute([
                ":laporan_id" => $idLaporan,
                ":catatan" => "Laporan diterima"
            ]);

            $pdo->commit();

            setFlash(
                "success",
                "Laporan berhasil dibuat."
            );

            redirect("laporan.php");

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Hapus foto jika penyimpanan database gagal.
            if ($namaFoto !== null) {
                hapusFoto($namaFoto);
            }

            // Detail error disimpan di log server, bukan ditampilkan ke user.
            error_log("Gagal membuat laporan: " . $e->getMessage());

            $errorUmum = "Laporan gagal disimpan. Silakan coba lagi.";
        }
    }
}

$inp = "w-full rounded-md border border-black/20 bg-white px-3 py-2.5 text-sm text-black placeholder:text-black/40 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30";

require "../header.php";
?>

<!-- Judul halaman -->
<div class="mb-6">
  <nav aria-label="Breadcrumb" class="mb-3 text-sm text-slate-500">
    <a href="dashboard.php" class="hover:text-red-700">Rumah</a>
    <span class="mx-2">›</span>
    <span class="font-semibold text-slate-800">Buat Laporan</span>
  </nav>

  <h2 class="text-2xl font-bold tracking-tight">Buat Laporan Baru</h2>
</div>

<?php if ($errorUmum): ?>
  <div role="alert" class="mb-4 rounded-md border border-red-600/30 bg-red-600/10 px-4 py-3 text-sm text-red-700">
    <?= e($errorUmum) ?>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">

  <!-- Kolom kiri: form laporan -->
  <section class="rounded-xl border border-black/10 bg-white p-5 sm:p-6">
    <form id="formLaporan" action="tambah_laporan.php" method="post" enctype="multipart/form-data" novalidate>
      <?= csrfField() ?>

      <!-- Judul laporan -->
      <div class="mb-5">
        <label for="judul" class="mb-1.5 block text-sm font-semibold">Judul laporan <span class="text-red-600">*</span></label>
        <input
          type="text" id="judul" name="judul" maxlength="150" required
          value="<?= e($old["judul"]) ?>"
          placeholder="Contoh: AC tidak dingin"
          class="<?= $inp ?>"
          aria-describedby="judulError">
        <p id="judulError" class="mt-1 hidden text-xs text-red-600"></p>
      </div>

      <!-- Kategori dan lokasi -->
      <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="kategori_id" class="mb-1.5 block text-sm font-semibold">Kategori <span class="text-red-600">*</span></label>
          <select id="kategori_id" name="kategori_id" required class="<?= $inp ?>" aria-describedby="kategoriError">
            <option value="">Pilih kategori</option>
            <?php foreach ($kategoriList as $kategori): ?>
              <option value="<?= (int) $kategori["id"] ?>" <?= $old["kategori_id"] == $kategori["id"] ? "selected" : "" ?>>
                <?= e($kategori["nama"]) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p id="kategoriError" class="mt-1 hidden text-xs text-red-600"></p>
        </div>

        <div>
          <label for="lokasi_id" class="mb-1.5 block text-sm font-semibold">Lokasi <span class="text-red-600">*</span></label>
          <select id="lokasi_id" name="lokasi_id" required class="<?= $inp ?>" aria-describedby="lokasiError">
            <option value="">Pilih lokasi</option>
            <?php foreach ($lokasiList as $lokasi): ?>
              <option value="<?= (int) $lokasi["id"] ?>" <?= $old["lokasi_id"] == $lokasi["id"] ? "selected" : "" ?>>
                <?= e($lokasi["nama"]) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p id="lokasiError" class="mt-1 hidden text-xs text-red-600"></p>
        </div>
      </div>

      <!-- Peringatan laporan serupa akan diisi oleh tambah.js -->
      <div id="peringatanSerupa" class="mb-5 hidden rounded-lg border border-amber-300 bg-amber-50 p-4" aria-live="polite">
        <div class="flex items-start gap-3">
          <i data-lucide="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"></i>
          <div class="min-w-0 flex-1">
            <h3 class="font-semibold text-amber-900">Sudah ada laporan serupa</h3>
            <p class="mt-1 text-sm text-amber-800">Ada laporan aktif untuk kategori dan lokasi ini. Periksa daftar berikut sebelum melanjutkan.</p>
            <div id="daftarLaporanSerupa" class="mt-3 space-y-2"></div>
            <div class="mt-4 flex flex-wrap gap-2">
              <button id="lihatLaporanSerupa" type="button" class="rounded-md border border-amber-700 px-3 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                Lihat laporan itu
              </button>
              <button id="tetapBuatLaporan" type="button" class="rounded-md bg-amber-700 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-800">
                Tetap buat laporan baru
              </button>
            </div>
            <p id="pilihanSerupa" class="mt-2 hidden text-xs text-amber-900"></p>
          </div>
        </div>
      </div>

      <!-- Prioritas -->
      <div class="mb-5">
        <legend class="mb-2 block text-sm font-semibold">Prioritas <span class="text-red-600">*</span></legend>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <label class="cursor-pointer rounded-lg border border-black/15 p-3 hover:border-red-600 has-[:checked]:border-red-600 has-[:checked]:bg-red-50">
            <span class="flex items-center gap-2">
              <input type="radio" name="prioritas" value="rendah" <?= $old["prioritas"] === "rendah" ? "checked" : "" ?> class="accent-red-600">
              <span class="text-sm font-semibold">Rendah</span>
            </span>
            <span class="mt-2 block text-xs text-slate-500">Gangguan kecil, masih bisa digunakan.</span>
          </label>

          <label class="cursor-pointer rounded-lg border border-black/15 p-3 hover:border-red-600 has-[:checked]:border-red-600 has-[:checked]:bg-red-50">
            <span class="flex items-center gap-2">
              <input type="radio" name="prioritas" value="sedang" <?= $old["prioritas"] === "sedang" ? "checked" : "" ?> class="accent-red-600">
              <span class="text-sm font-semibold">Sedang</span>
            </span>
            <span class="mt-2 block text-xs text-slate-500">Mengganggu kegiatan, tetapi masih bisa diatasi.</span>
          </label>

          <label class="cursor-pointer rounded-lg border border-black/15 p-3 hover:border-red-600 has-[:checked]:border-red-600 has-[:checked]:bg-red-50">
            <span class="flex items-center gap-2">
              <input type="radio" name="prioritas" value="tinggi" <?= $old["prioritas"] === "tinggi" ? "checked" : "" ?> class="accent-red-600">
              <span class="text-sm font-semibold">Tinggi</span>
            </span>
            <span class="mt-2 block text-xs text-slate-500">Menghambat kegiatan atau berpotensi membahayakan.</span>
          </label>
        </div>
      </div>

      <!-- Deskripsi -->
      <div class="mb-5">
        <div class="mb-1.5 flex items-center justify-between gap-3">
          <label for="deskripsi" class="text-sm font-semibold">Deskripsi <span class="text-red-600">*</span></label>
          <span id="hitungKarakter" class="text-xs text-slate-500">0 karakter</span>
        </div>
        <textarea id="deskripsi" name="deskripsi" rows="5" required minlength="20" maxlength="2000" placeholder="Jelaskan kerusakan, lokasi detail, dan sejak kapan terjadi..." class="<?= $inp ?> resize-y" aria-describedby="deskripsiError hitungKarakter"><?= e($old["deskripsi"]) ?></textarea>
        <p class="mt-1 text-xs text-slate-500">Minimal 20 karakter. Jangan lupa jelaskan sejak kapan kerusakan terjadi.</p>
        <p id="deskripsiError" class="mt-1 hidden text-xs text-red-600"></p>
      </div>

      <!-- Foto -->
      <div class="mb-6" data-area-foto>
        <label class="mb-1.5 block text-sm font-semibold" for="foto">Foto kerusakan <span class="font-normal text-slate-500">(opsional)</span></label>

        <label for="foto" id="areaFoto" class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-black/20 bg-slate-50 px-4 py-8 text-center transition hover:border-red-600 hover:bg-red-50/30">
          <i data-lucide="upload-cloud" class="mb-2 h-8 w-8 text-slate-400"></i>
          <span class="text-sm font-semibold">Klik untuk memilih foto atau seret file ke sini</span>
          <span class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP. Maksimal 2 MB.</span>
        </label>

        <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
        <p id="fotoError" class="mt-2 hidden text-xs text-red-600" role="alert"></p>

        <div id="previewFoto" class="mt-3 hidden">
          <div class="relative inline-block max-w-full overflow-hidden rounded-lg border border-black/10">
            <img id="gambarPreview" src="" alt="Preview foto kerusakan" class="max-h-64 max-w-full object-contain">
            <button id="hapusFoto" type="button" class="absolute right-2 top-2 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-red-700 shadow hover:bg-red-50">
              Hapus foto
            </button>
          </div>
          <p id="namaFoto" class="mt-1 truncate text-xs text-slate-500"></p>
        </div>
      </div>

      <div class="flex flex-col-reverse gap-3 border-t border-black/5 pt-5 sm:flex-row sm:justify-end">
        <a href="laporan.php" class="inline-flex items-center justify-center rounded-md border border-black/20 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-100">
          Batal
        </a>
        <button id="btnKirim" type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
          <i data-lucide="send" class="h-4 w-4"></i>
          <span id="teksKirim">Kirim Laporan</span>
        </button>
      </div>

      <p id="pesanForm" class="mt-3 hidden rounded-md px-3 py-2 text-sm" role="status" aria-live="polite"></p>
    </form>
  </section>

  <!-- Kolom kanan: tips statis -->
  <aside class="rounded-xl border border-black/10 bg-white p-5">
    <div class="flex items-center gap-2">
      <span class="flex h-9 w-9 items-center justify-center rounded-md bg-red-600/10 text-red-700">
        <i data-lucide="lightbulb" class="h-5 w-5"></i>
      </span>
      <h3 class="font-bold">Laporan yang baik</h3>
    </div>
    <ul class="mt-4 space-y-4 text-sm text-slate-600">
      <li class="flex items-start gap-3">
        <i data-lucide="image" class="mt-0.5 h-4 w-4 shrink-0 text-red-600"></i>
        <span><strong class="text-slate-800">Sertakan foto yang jelas.</strong> Ambil gambar dari jarak yang cukup agar kerusakan terlihat.</span>
      </li>
      <li class="flex items-start gap-3">
        <i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-red-600"></i>
        <span><strong class="text-slate-800">Sebutkan lokasi spesifik.</strong> Tuliskan ruang, lantai, atau sisi ruangan pada deskripsi.</span>
      </li>
      <li class="flex items-start gap-3">
        <i data-lucide="clock" class="mt-0.5 h-4 w-4 shrink-0 text-red-600"></i>
        <span><strong class="text-slate-800">Jelaskan sejak kapan rusak.</strong> Informasi waktu membantu petugas memahami masalah.</span>
      </li>
    </ul>
  </aside>
</div>

<?php require "../footer.php"; ?>
