
<?php
require "../init.php";

// Pastikan pengguna sudah login.
if (!userLogin()) {
    setFlash("warning", "Silakan login terlebih dahulu.");
    redirect("../auth/login.php");
}

// Admin tidak diperbolehkan mengedit laporan melalui halaman ini.
if (isAdmin()) {
    setFlash("error", "Halaman edit hanya untuk pelapor.");
    redirect("laporan.php");
}

$u = userLogin();

// Validasi ID laporan dari POST atau GET.
$idInput = $_POST["id"] ?? $_GET["id"] ?? null;

$id = filter_var(
    is_string($idInput) || is_int($idInput) ? $idInput : null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if ($id === false || $id === null) {
    setFlash("error", "ID laporan tidak valid.");
    redirect("laporan.php");
}

// Mengambil satu laporan berdasarkan ID.
function ambilLaporanEdit(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        "SELECT * FROM laporan WHERE id = :id LIMIT 1"
    );

    $stmt->execute(["id" => $id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

$laporan = ambilLaporanEdit($pdo, $id);

// Periksa keberadaan laporan, kepemilikan, dan status.
if (
    !$laporan ||
    (int) $laporan["user_id"] !== (int) $u["id"] ||
    $laporan["status"] !== "dilaporkan"
) {
    setFlash(
        "error",
        "Laporan tidak ditemukan, bukan milikmu, atau sudah diproses."
    );

    redirect("laporan.php");
}

// Ambil pilihan kategori dan lokasi.
$kategoriList = $pdo->query(
    "SELECT id, nama FROM kategori ORDER BY nama"
)->fetchAll(PDO::FETCH_ASSOC);

$lokasiList = $pdo->query(
    "SELECT id, nama FROM lokasi ORDER BY nama"
)->fetchAll(PDO::FETCH_ASSOC);

// Nilai awal form.
$error = "";
$judulInput = $laporan["judul"];
$kategoriInput = (int) $laporan["kategori_id"];
$lokasiInput = (int) $laporan["lokasi_id"];
$prioritasInput = $laporan["prioritas"];
$deskripsiInput = $laporan["deskripsi"];

// Proses ketika form dikirim.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrfCek();

    // Ambil input teks dengan aman.
    $judulPost = $_POST["judul"] ?? "";
    $deskripsiPost = $_POST["deskripsi"] ?? "";
    $prioritasPost = $_POST["prioritas"] ?? "";

    $judulInput = is_string($judulPost)
        ? trim($judulPost)
        : "";

    $deskripsiInput = is_string($deskripsiPost)
        ? trim($deskripsiPost)
        : "";

    $prioritasInput = is_string($prioritasPost)
        ? $prioritasPost
        : "";

    // Validasi ID kategori.
    $kategoriPost = $_POST["kategori_id"] ?? null;

    $kategoriInput = filter_var(
        is_string($kategoriPost) || is_int($kategoriPost)
            ? $kategoriPost
            : null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    // Validasi ID lokasi.
    $lokasiPost = $_POST["lokasi_id"] ?? null;

    $lokasiInput = filter_var(
        is_string($lokasiPost) || is_int($lokasiPost)
            ? $lokasiPost
            : null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    // Validasi judul.
    if (
        $judulInput === "" ||
        mb_strlen($judulInput) > 150
    ) {
        $error = "Judul wajib diisi dan maksimal 150 karakter.";
    }

    // Validasi kategori dan lokasi.
    if (
        $error === "" &&
        (
            $kategoriInput === false ||
            $kategoriInput === null ||
            $lokasiInput === false ||
            $lokasiInput === null
        )
    ) {
        $error = "Kategori atau lokasi tidak valid.";
    }

    // Validasi prioritas.
    $prioritasBoleh = ["rendah", "sedang", "tinggi"];

    if (
        $error === "" &&
        !in_array($prioritasInput, $prioritasBoleh, true)
    ) {
        $error = "Prioritas tidak valid.";
    }

    // Validasi deskripsi.
    if (
        $error === "" &&
        (
            mb_strlen($deskripsiInput) < 20 ||
            mb_strlen($deskripsiInput) > 2000
        )
    ) {
        $error = "Deskripsi harus terdiri dari 20–2000 karakter.";
    }

    // Pastikan kategori dan lokasi benar-benar ada di database.
    if ($error === "") {
        $cekKategori = $pdo->prepare(
            "SELECT COUNT(*) FROM kategori WHERE id = :id"
        );

        $cekKategori->execute([
            "id" => $kategoriInput,
        ]);

        $cekLokasi = $pdo->prepare(
            "SELECT COUNT(*) FROM lokasi WHERE id = :id"
        );

        $cekLokasi->execute([
            "id" => $lokasiInput,
        ]);

        if (
            !(int) $cekKategori->fetchColumn() ||
            !(int) $cekLokasi->fetchColumn()
        ) {
            $error = "Kategori atau lokasi yang dipilih tidak valid.";
        }
    }

    // Foto lama tetap digunakan jika pengguna tidak menggantinya.
    $fotoLama = $laporan["foto"];
    $fotoBaru = $fotoLama;
    $fileFotoBaru = null;

    // Proses upload foto hanya jika validasi input sebelumnya berhasil.
    if (
        $error === "" &&
        isset($_FILES["foto"]) &&
        is_array($_FILES["foto"])
    ) {
        $file = $_FILES["foto"];
        $uploadError = $file["error"] ?? UPLOAD_ERR_NO_FILE;

        if (!is_int($uploadError)) {
            $error = "Data unggahan foto tidak valid.";
        } elseif ($uploadError !== UPLOAD_ERR_NO_FILE) {
            if ($uploadError !== UPLOAD_ERR_OK) {
                $error = "Foto gagal diunggah.";
            } elseif (
                !isset(
                    $file["tmp_name"],
                    $file["size"],
                    $file["name"]
                ) ||
                !is_string($file["tmp_name"]) ||
                !is_int($file["size"]) ||
                $file["size"] <= 0
            ) {
                $error = "Data foto yang diunggah tidak valid.";
            } elseif ($file["size"] > 2 * 1024 * 1024) {
                $error = "Ukuran foto maksimal 2 MB.";
            } elseif (!is_uploaded_file($file["tmp_name"])) {
                $error = "File foto tidak valid.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file["tmp_name"]);

                $tipeFoto = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp",
                ];

                if (!isset($tipeFoto[$mime])) {
                    $error = "Foto harus berformat JPG, PNG, atau WebP.";
                } else {
                    $namaFile = bin2hex(random_bytes(16))
                        . "." . $tipeFoto[$mime];

                    $folderUpload = __DIR__ . "/../uploads/";

                    if (!is_dir($folderUpload)) {
                        $error = "Folder uploads tidak ditemukan.";
                    } elseif (!is_writable($folderUpload)) {
                        $error = "Folder uploads tidak dapat ditulisi.";
                    } else {
                        $pathFotoBaru = $folderUpload . $namaFile;

                        if (
                            !move_uploaded_file(
                                $file["tmp_name"],
                                $pathFotoBaru
                            )
                        ) {
                            $error = "Foto gagal disimpan.";
                        } else {
                            $fotoBaru = $namaFile;
                            $fileFotoBaru = $pathFotoBaru;
                        }
                    }
                }
            }
        }
    }

    // Simpan perubahan hanya jika seluruh validasi berhasil.
    if ($error === "") {
        $updateBerhasil = false;

        try {
            // Pemilik dan status dicek kembali ketika UPDATE.
            $stmt = $pdo->prepare(
                "UPDATE laporan
                    SET judul = :judul,
                        kategori_id = :kategori_id,
                        lokasi_id = :lokasi_id,
                        prioritas = :prioritas,
                        deskripsi = :deskripsi,
                        foto = :foto
                  WHERE id = :id
                    AND user_id = :user_id
                    AND status = 'dilaporkan'"
            );

            $stmt->execute([
                "judul" => $judulInput,
                "kategori_id" => $kategoriInput,
                "lokasi_id" => $lokasiInput,
                "prioritas" => $prioritasInput,
                "deskripsi" => $deskripsiInput,
                "foto" => $fotoBaru,
                "id" => $id,
                "user_id" => (int) $u["id"],
            ]);

            // rowCount() bisa nol jika tidak ada nilai yang berubah.
            if ($stmt->rowCount() === 0) {
                $cek = $pdo->prepare(
                    "SELECT id
                       FROM laporan
                      WHERE id = :id
                        AND user_id = :user_id
                        AND status = 'dilaporkan'
                      LIMIT 1"
                );

                $cek->execute([
                    "id" => $id,
                    "user_id" => (int) $u["id"],
                ]);

                if (!$cek->fetchColumn()) {
                    // Laporan tidak lagi dapat diedit.
                    if ($fileFotoBaru !== null) {
                        hapusFoto(basename($fileFotoBaru));
                    }

                    setFlash(
                        "error",
                        "Laporan sudah tidak dapat diedit."
                    );

                    redirect("laporan.php");
                }
            }

            $updateBerhasil = true;
        } catch (Throwable $e) {
            // Foto baru tidak boleh tertinggal jika UPDATE gagal.
            if (!$updateBerhasil && $fileFotoBaru !== null) {
                hapusFoto(basename($fileFotoBaru));
            }

            error_log("Edit laporan gagal: " . $e->getMessage());

            $error = "Laporan gagal diperbarui. Silakan coba lagi.";
        }

        if ($updateBerhasil) {
            // Hapus foto lama setelah database berhasil diperbarui.
            if (
                $fotoBaru !== $fotoLama &&
                $fotoLama !== null &&
                $fotoLama !== ""
            ) {
                hapusFoto($fotoLama);
            }

            setFlash("success", "Laporan berhasil diperbarui.");
            redirect("laporan.php");
        }
    }
}

$judul = "Edit Laporan";
$jsHalaman = [];

require "../header.php";

$inp = "w-full rounded-md border border-black/20 bg-white px-3 py-2.5 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30";
?>

<div class="mb-6">
    <nav aria-label="Breadcrumb" class="mb-3 text-sm text-slate-500">
        <a href="dashboard.php" class="hover:text-red-700">Rumah</a>
        <span class="mx-2">›</span>
        <a href="laporan.php" class="hover:text-red-700">Laporan</a>
        <span class="mx-2">›</span>
        <span class="font-semibold text-slate-800">Edit Laporan</span>
    </nav>

    <h2 class="text-2xl font-bold tracking-tight">Edit Laporan</h2>

    <p class="mt-1 text-sm text-slate-600">
        Laporan hanya dapat diedit selama statusnya masih dilaporkan.
    </p>
</div>

<?php if ($error !== ""): ?>
    <div
        role="alert"
        class="mb-4 rounded-md border border-red-600/30 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
        <?= e($error) ?>
    </div>
<?php endif; ?>

<section class="max-w-3xl rounded-xl border border-black/10 bg-white p-5 sm:p-6">
    <form method="post" enctype="multipart/form-data" class="space-y-5">
        <?= csrfField() ?>

        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div>
            <label for="judul" class="mb-1.5 block text-sm font-semibold">
                Judul laporan
            </label>

            <input
                id="judul"
                name="judul"
                type="text"
                maxlength="150"
                required
                value="<?= e($judulInput) ?>"
                class="<?= $inp ?>"
            >
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="kategori_id" class="mb-1.5 block text-sm font-semibold">
                    Kategori
                </label>

                <select
                    id="kategori_id"
                    name="kategori_id"
                    required
                    class="<?= $inp ?>"
                >
                    <?php foreach ($kategoriList as $kategori): ?>
                        <option
                            value="<?= (int) $kategori["id"] ?>"
                            <?= (int) $kategoriInput === (int) $kategori["id"] ? "selected" : "" ?>
                        >
                            <?= e($kategori["nama"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="lokasi_id" class="mb-1.5 block text-sm font-semibold">
                    Lokasi
                </label>

                <select
                    id="lokasi_id"
                    name="lokasi_id"
                    required
                    class="<?= $inp ?>"
                >
                    <?php foreach ($lokasiList as $lokasi): ?>
                        <option
                            value="<?= (int) $lokasi["id"] ?>"
                            <?= (int) $lokasiInput === (int) $lokasi["id"] ? "selected" : "" ?>
                        >
                            <?= e($lokasi["nama"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label for="prioritas" class="mb-1.5 block text-sm font-semibold">
                Prioritas
            </label>

            <select
                id="prioritas"
                name="prioritas"
                required
                class="<?= $inp ?>"
            >
                <?php foreach (["rendah", "sedang", "tinggi"] as $p): ?>
                    <option
                        value="<?= e($p) ?>"
                        <?= $prioritasInput === $p ? "selected" : "" ?>
                    >
                        <?= ucfirst($p) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="deskripsi" class="mb-1.5 block text-sm font-semibold">
                Deskripsi
            </label>

            <textarea
                id="deskripsi"
                name="deskripsi"
                rows="5"
                minlength="20"
                maxlength="2000"
                required
                class="<?= $inp ?>"
            ><?= e($deskripsiInput) ?></textarea>
        </div>

        <?php if (!empty($laporan["foto"])): ?>
            <div>
                <p class="mb-2 text-sm font-semibold">Foto saat ini</p>

                <img
                    src="<?= e(BASE_URL . "uploads/" . rawurlencode($laporan["foto"])) ?>"
                    alt="Foto laporan saat ini"
                    class="max-h-64 rounded-lg border border-black/10 object-contain"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Pilih foto baru di bawah jika ingin menggantinya.
                </p>
            </div>
        <?php endif; ?>

        <div>
            <label for="foto" class="mb-1.5 block text-sm font-semibold">
                Ganti foto
                <span class="font-normal text-slate-500">
                    (opsional, maksimal 2 MB)
                </span>
            </label>

            <input
                id="foto"
                name="foto"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm"
            >
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-black/5 pt-5 sm:flex-row sm:justify-end">
            <a
                href="laporan.php"
                class="inline-flex items-center justify-center rounded-md border border-black/20 px-4 py-2.5 text-sm font-semibold hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
            >
                Simpan perubahan
            </button>
        </div>
    </form>
</section>

<?php require "../footer.php"; ?>