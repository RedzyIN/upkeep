
<?php
require "../init.php";
require "../data/dummy.php";

previewLogin();
wajibAdmin();

$judul = "Manajemen Lokasi";
$jejak = [["Lokasi", null]];
$jsHalaman = [];

$urlHalaman = BASE_URL . "dashboard/lokasi.php";

// Proses CRUD
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrfCek();

    $aksi = $_POST["aksi"] ?? "";
    $id = filter_var($_POST["id"] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $nama = trim($_POST["nama"] ?? "");

    try {
        if ($aksi === "tambah" || $aksi === "edit") {
            if ($nama === "" || mb_strlen($nama) > 100) {
                setFlash("error", "Nama lokasi wajib diisi (maksimal 100 karakter).");
            } elseif ($aksi === "tambah") {
                $stmt = $pdo->prepare(
                    "INSERT INTO lokasi (nama) VALUES (:nama)"
                );
                $stmt->execute(["nama" => $nama]);

                setFlash("success", "Lokasi berhasil ditambahkan.");
            } else {
                if ($id < 1) {
                    setFlash("error", "ID lokasi tidak valid.");
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE lokasi SET nama = :nama WHERE id = :id"
                    );
                    $stmt->execute([
                        "nama" => $nama,
                        "id" => $id
                    ]);

                    setFlash("success", "Lokasi berhasil diperbarui.");
                }
            }
        } elseif ($aksi === "hapus") {
            if ($id < 1) {
                setFlash("error", "ID lokasi tidak valid.");
            } else {
                $stmt = $pdo->prepare(
                    "DELETE FROM lokasi WHERE id = :id"
                );
                $stmt->execute(["id" => $id]);

                if ($stmt->rowCount() > 0) {
                    setFlash("success", "Lokasi berhasil dihapus.");
                } else {
                    setFlash("error", "Lokasi tidak ditemukan.");
                }
            }
        } else {
            setFlash("error", "Aksi tidak dikenal.");
        }
    } catch (PDOException $e) {
        $kodeMySQL = (int) ($e->errorInfo[1] ?? 0);

        if ($kodeMySQL === 1062) {
            setFlash("error", "Nama lokasi sudah digunakan.");
        } elseif ($kodeMySQL === 1451) {
            setFlash(
                "error",
                "Lokasi tidak dapat dihapus karena masih digunakan oleh laporan."
            );
        } else {
            error_log("CRUD lokasi gagal: " . $e->getMessage());
            setFlash("error", "Terjadi kesalahan saat memproses lokasi.");
        }
    }

    redirect($urlHalaman);
}

// Baca data dari database
$stmt = $pdo->query(
    "SELECT id, nama FROM lokasi ORDER BY nama ASC"
);
$lokasiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

require "../header.php";
?>

<div class="space-y-6 p-4 md:p-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Manajemen Lokasi
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Kelola lokasi fasilitas yang digunakan pada laporan.
            </p>
        </div>

        <button type="button" id="tambahData"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-4 py-3 font-semibold text-white transition hover:bg-red-700">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Tambah Lokasi
        </button>
    </div>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-5 py-4 font-semibold">No.</th>
                        <th class="px-5 py-4 font-semibold">Nama Lokasi</th>
                        <th class="px-5 py-4 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <?php if (!$lokasiList): ?>
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-gray-500">
                                Belum ada data lokasi.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lokasiList as $i => $lokasi): ?>
                            <tr>
                                <td class="px-5 py-4 text-gray-500">
                                    <?= $i + 1 ?>
                                </td>

                                <td class="px-5 py-4 font-semibold text-gray-800">
                                    <?= e($lokasi["nama"]) ?>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                            data-mode="edit"
                                            data-id="<?= (int) $lokasi["id"] ?>"
                                            data-nama="<?= e($lokasi["nama"]) ?>"
                                            class="inline-flex items-center gap-1 rounded-md border border-blue-600 px-3 py-2 font-medium text-blue-700 transition hover:bg-blue-600 hover:text-white">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                            Edit
                                        </button>

                                        <button type="button"
                                            data-mode="hapus"
                                            data-id="<?= (int) $lokasi["id"] ?>"
                                            data-nama="<?= e($lokasi["nama"]) ?>"
                                            class="inline-flex items-center gap-1 rounded-md border border-red-600 px-3 py-2 font-medium text-red-700 transition hover:bg-red-600 hover:text-white">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Modal tambah, edit, dan konfirmasi hapus -->
<div id="modalData"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">

    <section role="dialog" aria-modal="true"
        aria-labelledby="modalJudul"
        class="w-full max-w-lg rounded-xl bg-white shadow-xl">

        <div class="flex items-start justify-between border-b border-gray-200 p-5">
            <div>
                <h2 id="modalJudul" class="text-xl font-bold text-gray-800">
                    Tambah Lokasi
                </h2>
                <p id="modalSubjudul" class="mt-1 text-sm text-gray-500">
                    Masukkan nama lokasi baru.
                </p>
            </div>

            <button type="button" data-tutup aria-label="Tutup modal"
                class="rounded-md p-2 text-gray-500 hover:bg-gray-100">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form method="POST" id="formData" class="space-y-5 p-5">
            <?= csrfField() ?>

            <input type="hidden" name="aksi" id="aksi">
            <input type="hidden" name="id" id="dataId">

            <div id="areaNama">
                <label for="namaLokasi"
                    class="mb-2 block text-sm font-semibold text-gray-700">
                    Nama lokasi <span class="text-red-600">*</span>
                </label>
                <input type="text" name="nama" id="namaLokasi"
                    maxlength="100" required
                    placeholder="Contoh: Gedung A - Ruang 201"
                    class="w-full rounded-md border border-gray-300 px-3 py-3 outline-none focus:border-red-600 focus:ring-2 focus:ring-red-100">
            </div>

            <p id="pesanHapus" class="hidden text-sm text-gray-700">
                Apakah kamu yakin ingin menghapus lokasi ini?
            </p>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" data-tutup
                    class="rounded-md border border-gray-300 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50">
                    Batal
                </button>

                <button type="submit" id="tombolSimpan"
                    class="rounded-md bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">
                    Simpan
                </button>
            </div>
        </form>
    </section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalData");
    const form = document.getElementById("formData");
    const inputAksi = document.getElementById("aksi");
    const inputId = document.getElementById("dataId");
    const inputNama = document.getElementById("namaLokasi");
    const areaNama = document.getElementById("areaNama");
    const pesanHapus = document.getElementById("pesanHapus");
    const judul = document.getElementById("modalJudul");
    const subjudul = document.getElementById("modalSubjudul");
    const tombolSimpan = document.getElementById("tombolSimpan");

    function bukaModal(mode, data = {}) {
        form.reset();

        inputAksi.value = mode;
        inputId.value = data.id || "";
        inputNama.value = data.nama || "";

        areaNama.classList.toggle("hidden", mode === "hapus");
        inputNama.required = mode !== "hapus";
        pesanHapus.classList.toggle("hidden", mode !== "hapus");

        if (mode === "tambah") {
            judul.textContent = "Tambah Lokasi";
            subjudul.textContent = "Masukkan nama lokasi baru.";
            tombolSimpan.textContent = "Simpan";
            tombolSimpan.className =
                "rounded-md bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700";
        } else if (mode === "edit") {
            judul.textContent = "Edit Lokasi";
            subjudul.textContent = "Ubah nama lokasi.";
            tombolSimpan.textContent = "Simpan Perubahan";
            tombolSimpan.className =
                "rounded-md bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700";
        } else {
            judul.textContent = "Hapus Lokasi";
            subjudul.textContent = "Periksa kembali sebelum menghapus.";
            pesanHapus.textContent =
                `Yakin ingin menghapus lokasi "${data.nama}"? Jika masih digunakan oleh laporan, penghapusan akan ditolak.`;
            tombolSimpan.textContent = "Ya, Hapus";
            tombolSimpan.className =
                "rounded-md bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700";
        }

        modal.classList.remove("hidden");
        modal.classList.add("flex");

        if (mode !== "hapus") inputNama.focus();
    }

    function tutupModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }

    document.getElementById("tambahData").addEventListener("click", function () {
        bukaModal("tambah");
    });

    document.querySelectorAll("[data-mode]").forEach(function (tombol) {
        tombol.addEventListener("click", function () {
            bukaModal(tombol.dataset.mode, {
                id: tombol.dataset.id,
                nama: tombol.dataset.nama
            });
        });
    });

    modal.querySelectorAll("[data-tutup]").forEach(function (tombol) {
        tombol.addEventListener("click", tutupModal);
    });

    modal.addEventListener("click", function (event) {
        if (event.target === modal) tutupModal();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !modal.classList.contains("hidden")) {
            tutupModal();
        }
    });
});
</script>

<?php require "../footer.php"; ?>