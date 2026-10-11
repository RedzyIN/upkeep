
<?php
require "../init.php";
require "../data/dummy.php";

previewLogin();
wajibLogin();

$u = userLogin();
$admin = isAdmin();

$judul = "Upkeep";
$jsHalaman = [];
require "../header.php";

// Data dummy lokasi
$lokasiList = [
    ["id" => 1, "nama" => "Lab Komputer 1", "gedung" => "Gedung A", "lantai" => "1"],
    ["id" => 2, "nama" => "Ruang Dosen", "gedung" => "Gedung A", "lantai" => "2"],
    ["id" => 3, "nama" => "Ruang Administrasi", "gedung" => "Gedung B", "lantai" => "1"],
    ["id" => 4, "nama" => "Perpustakaan", "gedung" => "Gedung B", "lantai" => "2"],
    ["id" => 5, "nama" => "Aula", "gedung" => "Gedung C", "lantai" => "1"],
];
?>

<main class="p-4 md:p-6 space-y-6">
    <!-- Judul halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center
                sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Manajemen Lokasi
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Kelola lokasi fasilitas yang digunakan pada laporan.
            </p>
        </div>

        <button type="button" id="tambahData"
            class="inline-flex items-center justify-center gap-2
                   rounded-md bg-red-600 px-4 py-3
                   font-semibold text-white transition hover:bg-red-700">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Tambah Lokasi
        </button>
    </div>

    <!-- Tabel lokasi -->
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-5 py-4 font-semibold">No.</th>
                        <th class="px-5 py-4 font-semibold">Nama Lokasi</th>
                        <th class="px-5 py-4 font-semibold">Gedung</th>
                        <th class="px-5 py-4 font-semibold">Lantai</th>
                        <th class="px-5 py-4 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody id="tabelData" class="divide-y divide-gray-100">
                    <?php foreach ($lokasiList as $i => $lokasi): ?>
                        <tr
                            data-id="<?= (int) $lokasi['id'] ?>"
                            data-nama="<?= e($lokasi['nama']) ?>"
                            data-gedung="<?= e($lokasi['gedung']) ?>"
                            data-lantai="<?= e($lokasi['lantai']) ?>"
                            class="hover:bg-gray-50">

                            <td class="px-5 py-4 text-gray-500 nomor">
                                <?= $i + 1 ?>
                            </td>

                            <td class="px-5 py-4 font-semibold text-gray-800 nama">
                                <?= e($lokasi['nama']) ?>
                            </td>

                            <td class="px-5 py-4 text-gray-600 gedung">
                                <?= e($lokasi['gedung']) ?>
                            </td>

                            <td class="px-5 py-4 text-gray-600 lantai">
                                <?= e($lokasi['lantai']) ?>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex justify-center gap-2">
                                    <button type="button" data-aksi="edit"
                                        class="inline-flex items-center gap-1 rounded-md
                                               border border-blue-600 px-3 py-2
                                               font-medium text-blue-700
                                               transition hover:bg-blue-600
                                               hover:text-white">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Edit
                                    </button>

                                    <button type="button" data-aksi="hapus"
                                        class="inline-flex items-center gap-1 rounded-md
                                               border border-red-600 px-3 py-2
                                               font-medium text-red-700
                                               transition hover:bg-red-600
                                               hover:text-white">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="dataKosong" class="hidden px-5 py-10 text-center text-gray-500">
            Belum ada data lokasi.
        </div>
    </section>
</main>

<!-- Modal tambah/edit -->
<div id="modalData"
    class="fixed inset-0 z-50 hidden items-center justify-center
           bg-black/50 p-4">

    <section role="dialog" aria-modal="true"
        aria-labelledby="modalJudul"
        class="w-full max-w-lg rounded-xl bg-white shadow-xl">

        <div class="flex items-start justify-between border-b border-gray-200 p-5">
            <div>
                <h2 id="modalJudul" class="text-xl font-bold text-gray-800">
                    Tambah Lokasi
                </h2>
                <p id="modalSubjudul" class="mt-1 text-sm text-gray-500">
                    Masukkan informasi lokasi baru.
                </p>
            </div>

            <button type="button" data-tutup
                aria-label="Tutup modal"
                class="rounded-md p-2 text-gray-500 hover:bg-gray-100">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form id="formData" class="space-y-5 p-5">
            <input type="hidden" id="dataId">

            <div>
                <label for="namaLokasi"
                    class="mb-2 block text-sm font-semibold text-gray-700">
                    Nama lokasi <span class="text-red-600">*</span>
                </label>
                <input type="text" id="namaLokasi" required maxlength="100"
                    placeholder="Contoh: Lab Komputer 1"
                    class="w-full rounded-md border border-gray-300 px-3 py-3
                           outline-none focus:border-red-600 focus:ring-2
                           focus:ring-red-100">
            </div>

            <div>
                <label for="gedungLokasi"
                    class="mb-2 block text-sm font-semibold text-gray-700">
                    Gedung <span class="text-red-600">*</span>
                </label>
                <input type="text" id="gedungLokasi" required maxlength="100"
                    placeholder="Contoh: Gedung A"
                    class="w-full rounded-md border border-gray-300 px-3 py-3
                           outline-none focus:border-red-600 focus:ring-2
                           focus:ring-red-100">
            </div>

            <div>
                <label for="lantaiLokasi"
                    class="mb-2 block text-sm font-semibold text-gray-700">
                    Lantai <span class="text-red-600">*</span>
                </label>
                <input type="text" id="lantaiLokasi" required maxlength="30"
                    placeholder="Contoh: 1"
                    class="w-full rounded-md border border-gray-300 px-3 py-3
                           outline-none focus:border-red-600 focus:ring-2
                           focus:ring-red-100">
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" data-tutup
                    class="rounded-md border border-gray-300 px-4 py-2
                           font-semibold text-gray-700 hover:bg-gray-50">
                    Batal
                </button>

                <button type="submit" id="tombolSimpan"
                    class="rounded-md bg-red-600 px-4 py-2
                           font-semibold text-white hover:bg-red-700">
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
    const tabel = document.getElementById("tabelData");
    const dataKosong = document.getElementById("dataKosong");

    const inputId = document.getElementById("dataId");
    const inputNama = document.getElementById("namaLokasi");
    const inputGedung = document.getElementById("gedungLokasi");
    const inputLantai = document.getElementById("lantaiLokasi");
    const judul = document.getElementById("modalJudul");
    const subjudul = document.getElementById("modalSubjudul");
    const tombolSimpan = document.getElementById("tombolSimpan");

    let idBerikutnya = Math.max(
        0,
        ...Array.from(tabel.querySelectorAll("tr"), tr =>
            Number(tr.dataset.id) || 0
        )
    ) + 1;

    function perbaruiTabel() {
        const baris = Array.from(tabel.querySelectorAll("tr"));
        dataKosong.classList.toggle("hidden", baris.length > 0);

        baris.forEach((tr, index) => {
            tr.querySelector(".nomor").textContent = index + 1;
        });
    }

    function bukaModal(mode, tr = null) {
        form.reset();
        inputId.value = tr ? tr.dataset.id : "";

        if (mode === "edit" && tr) {
            judul.textContent = "Edit Lokasi";
            subjudul.textContent = "Ubah informasi lokasi.";
            tombolSimpan.textContent = "Simpan Perubahan";

            inputNama.value = tr.dataset.nama;
            inputGedung.value = tr.dataset.gedung;
            inputLantai.value = tr.dataset.lantai;
        } else {
            judul.textContent = "Tambah Lokasi";
            subjudul.textContent = "Masukkan informasi lokasi baru.";
            tombolSimpan.textContent = "Simpan";
        }

        modal.classList.remove("hidden");
        modal.classList.add("flex");
        inputNama.focus();
    }

    function tutupModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        form.reset();
    }

    document.getElementById("tambahData").addEventListener("click", function () {
        bukaModal("tambah");
    });

    modal.querySelectorAll("[data-tutup]").forEach(tombol => {
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

    tabel.addEventListener("click", function (event) {
        const tombol = event.target.closest("button[data-aksi]");
        if (!tombol) return;

        const tr = tombol.closest("tr");
        if (!tr) return;

        if (tombol.dataset.aksi === "edit") {
            bukaModal("edit", tr);
        }

        if (tombol.dataset.aksi === "hapus") {
            if (!confirm("Yakin ingin menghapus lokasi ini?")) return;

            tr.remove();
            perbaruiTabel();
        }
    });

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        const nama = inputNama.value.trim();
        const gedung = inputGedung.value.trim();
        const lantai = inputLantai.value.trim();

        if (!nama || !gedung || !lantai) {
            alert("Nama lokasi, gedung, dan lantai wajib diisi.");
            return;
        }

        const id = inputId.value;
        let tr;

        if (id) {
            tr = Array.from(tabel.querySelectorAll("tr"))
                .find(row => row.dataset.id === id);

            if (!tr) return;

            tr.dataset.nama = nama;
            tr.dataset.gedung = gedung;
            tr.dataset.lantai = lantai;

            tr.querySelector(".nama").textContent = nama;
            tr.querySelector(".gedung").textContent = gedung;
            tr.querySelector(".lantai").textContent = lantai;
        } else {
            tr = document.createElement("tr");
            tr.dataset.id = String(idBerikutnya++);
            tr.dataset.nama = nama;
            tr.dataset.gedung = gedung;
            tr.dataset.lantai = lantai;
            tr.className = "hover:bg-gray-50";

            tr.innerHTML = `
                <td class="px-5 py-4 text-gray-500 nomor"></td>
                <td class="px-5 py-4 font-semibold text-gray-800 nama"></td>
                <td class="px-5 py-4 text-gray-600 gedung"></td>
                <td class="px-5 py-4 text-gray-600 lantai"></td>
                <td class="px-5 py-4">
                    <div class="flex justify-center gap-2">
                        <button type="button" data-aksi="edit"
                            class="inline-flex items-center gap-1 rounded-md
                                   border border-blue-600 px-3 py-2
                                   font-medium text-blue-700 hover:bg-blue-600
                                   hover:text-white">
                            Edit
                        </button>
                        <button type="button" data-aksi="hapus"
                            class="inline-flex items-center gap-1 rounded-md
                                   border border-red-600 px-3 py-2
                                   font-medium text-red-700 hover:bg-red-600
                                   hover:text-white">
                            Hapus
                        </button>
                    </div>
                </td>`;

            tr.querySelector(".nama").textContent = nama;
            tr.querySelector(".gedung").textContent = gedung;
            tr.querySelector(".lantai").textContent = lantai;

            tabel.appendChild(tr);
        }

        perbaruiTabel();
        tutupModal();

        alert("Demo berhasil. Perubahan belum tersimpan ke database.");
    });

    perbaruiTabel();
});
</script>

<?php require "../footer.php"; ?>
