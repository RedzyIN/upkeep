<?php
require_once"../init.php";
wajibTamu();
$judul       = "Upkeep";
$tanpaNavbar = true;                 // halaman ini hanya berisi kartu form
$jsHalaman   = ["assets/js/auth.js"];

// TODO(backend): proses registrasi menyusul. Alur: kirim form -> status 'menunggu' -> admin menyetujui.
//   1. Validasi teks (nama, email & no_induk unik, password_hash).
//   2. Validasi berkas $_FILES["berkas"] di SERVER (cek di JS hanya kenyamanan):
//        - ["error"] === UPLOAD_ERR_OK, ukuran <= 5 MB
//        - tipe asli lewat finfo (application/pdf, image/jpeg, image/png), jangan percaya ekstensi
//        - simpan dengan nama acak (bin2hex(random_bytes(16))), idealnya di luar folder publik
//      Mahasiswa & dosen: cek fakultas ada di $unm dan jurusan ada di $unm[fakultas].
//      Field "unit" TIDAK terkirim untuk mereka (disabled); tendik memakai "unit".
//   3. INSERT role='user', status='menunggu', kolom berkas = nama file tadi.
//   4. setFlash("Pendaftaran terkirim. Tunggu persetujuan admin.") lalu redirect ke login.php.
//   5. Halaman admin: daftar akun 'menunggu' + tombol Setujui/Tolak. Berkas dibuka lewat skrip PHP
//      yang mengecek sesi admin, bukan tautan langsung.
// Catatan: role TIDAK ada di form. Akun baru selalu 'user'.
$error = "";
$old   = ["nama" => "", "email" => "", "tipe" => "", "no_induk" => "", "unit" => "", "fakultas" => "", "jurusan" => ""];   // isi ulang form kalau gagal

// Data fakultas -> daftar jurusan UNM (isi lengkapnya ada di data-unm.php)
$unm = require "../data/data_unm.php";

// Aturan dari kode lama, TIDAK diubah: fakultas & jurusan hanya untuk mahasiswa dan dosen.
// Di sini dipakai untuk kondisi AWAL saja (form diisi ulang setelah error).
// Saat pengguna memilih tipe, script di bawah halaman yang menampilkan/menyembunyikannya
// dengan aturan yang sama, karena PHP hanya berjalan sekali di server.
$tampilFakultas = ($old["tipe"] === "mahasiswa" || $old["tipe"] === "dosen");
// Input file tidak bisa diisi ulang oleh PHP: kalau validasi gagal, pengguna memilih berkas lagi.

$inp = "w-full rounded-md border border-black/20 bg-white px-3 py-2.5 text-sm text-black placeholder:text-black/40 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30";
$mata = "absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-black/60 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600";
require_once "../header.php";
?>

<!-- Halaman = satu kartu form di tengah layar.
    Di HP: kartu memenuhi layar. Di layar >= 640px (sm): kartu di tengah. -->
<div class="flex min-h-screen items-center justify-center bg-slate-200 sm:p-6">
    <div class="flex min-h-screen w-full flex-col justify-center bg-white p-6 sm:min-h-0 sm:max-w-xl sm:rounded-lg sm:border sm:border-black/10 sm:p-8 sm:shadow-sm">

    <h2 class="text-2xl font-bold text-center">Registrasi Akun</h2>

    <?php if ($error): ?>
    <div role="alert" class="mt-5 flex items-start gap-2 rounded-md border border-red-600/30 bg-red-600/10 px-3 py-2.5 text-sm text-red-700">
    <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
    <span><?= e($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- enctype wajib ada supaya file ikut terkirim -->
    <form method="post" action="register.php" id="formDaftar" enctype="multipart/form-data" class="mt-6 space-y-4">
    <?= csrfField() ?>

    <div>
        <label for="nama" class="mb-1.5 block text-sm font-semibold">Nama lengkap</label>
        <input type="text" id="nama" name="nama" value="<?= e($old["nama"]) ?>" required maxlength="100"
            autocomplete="name" placeholder="Nama sesuai identitas" class="<?= $inp ?>">
    </div>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-semibold">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old["email"]) ?>" required maxlength="100"
            autocomplete="email" placeholder="nama@kampus.ac.id" class="<?= $inp ?>">
    </div>

    <div>
        <label for="tipe" class="mb-1.5 block text-sm font-semibold">Saya adalah</label>
        <select id="tipe" name="tipe" required class="<?= $inp ?>">
        <option value="" disabled <?= $old["tipe"] === "" ? "selected" : "" ?>>Pilih tipe pengguna</option>
        <option value="mahasiswa" <?= $old["tipe"] === "mahasiswa" ? "selected" : "" ?>>Mahasiswa</option>
        <option value="dosen"     <?= $old["tipe"] === "dosen" ? "selected" : "" ?>>Dosen</option>
        <option value="tendik"    <?= $old["tipe"] === "tendik" ? "selected" : "" ?>>Tenaga Kependidikan</option>
        </select>
    </div>

    <!-- Fakultas -> Jurusan (hanya mahasiswa & dosen).
        Blok selalu ada di halaman; script di bawah menampilkannya begitu tipe dipilih.
        Isi dropdown Jurusan menyesuaikan fakultas yang dipilih. -->
    <div id="blokFakultas" class="grid gap-4 sm:grid-cols-2 <?= $tampilFakultas ? "" : "hidden" ?>">
        <div>
        <label for="fakultas" class="mb-1.5 block text-sm font-semibold">Fakultas</label>
        <select id="fakultas" name="fakultas" <?= $tampilFakultas ? "required" : "disabled" ?> class="<?= $inp ?>">
            <option value="" disabled <?= $old["fakultas"] === "" ? "selected" : "" ?>>Pilih fakultas</option>
            <?php foreach ($unm as $namaFakultas => $daftarJurusan): ?>
            <option value="<?= e($namaFakultas) ?>" <?= $old["fakultas"] === $namaFakultas ? "selected" : "" ?>><?= e($namaFakultas) ?></option>
            <?php endforeach; ?>
        </select>
        </div>
        <div>
        <label for="jurusan" class="mb-1.5 block text-sm font-semibold">Jurusan / program studi</label>
        <!-- Opsinya diisi script setelah fakultas dipilih -->
        <select id="jurusan" name="jurusan" disabled class="<?= $inp ?> disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-black/40">
            <option value="" disabled selected>Pilih fakultas dulu</option>
        </select>
        </div>
    </div>

    <!-- Dua kolom ini berganti label mengikuti tipe (diatur auth.js) -->
    <div class="grid gap-4 sm:grid-cols-2">
        <div id="grupInduk" class="<?= $tampilFakultas ? "sm:col-span-2" : "" ?>">
        <label for="no_induk" id="labelInduk" class="mb-1.5 block text-sm font-semibold">Nomor induk</label>
        <input type="text" id="no_induk" name="no_induk" value="<?= e($old["no_induk"]) ?>" required
                pattern="[0-9A-Za-z.\-]{5,30}" title="5-30 karakter: huruf, angka, titik, atau strip"
                autocomplete="off" placeholder="Pilih tipe dulu" class="<?= $inp ?>">
        <p id="hintInduk" class="mt-1 text-xs text-black/50">NIM / NIDN / NIP sesuai tipe.</p>
        </div>
        <!-- Kolom unit hanya untuk tenaga kependidikan; mahasiswa & dosen memakai Fakultas + Jurusan -->
        <div id="grupUnit" class="<?= $tampilFakultas ? "hidden" : "" ?>">
        <label for="unit" id="labelUnit" class="mb-1.5 block text-sm font-semibold">Program studi / unit</label>
        <input type="text" id="unit" name="unit" value="<?= e($old["unit"]) ?>" required maxlength="100" <?= $tampilFakultas ? "disabled" : "" ?>
                list="daftar-unit" autocomplete="off" placeholder="Pilih tipe dulu" class="<?= $inp ?>">
        <datalist id="daftar-unit"></datalist>
        <p id="hintUnit" class="mt-1 text-xs text-black/50">Pilih dari saran atau ketik sendiri.</p>
        </div>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-semibold">SK/Lampiran Pendukung</label>
    
        <div class="w-full" data-file-field>
            <label for="lampiran" class="group relative flex h-36 w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 transition-all hover:bg-gray-100/80">
            <div class="flex flex-col items-center justify-center px-6 text-center">
                <div class="mb-3 rounded-full bg-white p-2.5 shadow-sm transition-transform group-hover:scale-110">
                <i data-lucide="upload-cloud" class="h-6 w-6 text-gray-500"></i>
                </div>
                <p class="mb-1 text-sm font-medium text-gray-700">
                <div class="block font-semibold text-red-600">Klik untuk unggah</div> atau seret file ke sini
                </p>
                <p class="text-xs text-gray-400">PDF atau JPG (maks. 5 MB per file)</p>
            </div>
            <input id="lampiran" name="lampiran[]" type="file" class="sr-only"accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5" multiple>
            </label>
    
            <ul class="mt-3 space-y-2" data-file-list></ul>
            <p class="mt-1 hidden text-xs text-red-600" data-file-error></p>
        </div>
        </div>

    <div>
        <label for="password" class="mb-1.5 block text-sm font-semibold">Password</label>
        <div class="relative">
        <input type="password" id="password" name="password" required minlength="8"
                autocomplete="new-password" placeholder="Minimal 8 karakter" class="<?= $inp ?> pr-11">
        <button type="button" onclick="togglePassword('password', this)" aria-label="Tampilkan password" class="<?= $mata ?>">
            <i data-lucide="eye" class="h-4 w-4"></i>
        </button>
        </div>
    </div>

    <div>
        <label for="konfirmasi" class="mb-1.5 block text-sm font-semibold">Konfirmasi password</label>
        <div class="relative">
        <input type="password" id="konfirmasi" name="konfirmasi" required minlength="8"
                autocomplete="new-password" placeholder="Ulangi password" class="<?= $inp ?> pr-11">
        <button type="button" onclick="togglePassword('konfirmasi', this)" aria-label="Tampilkan konfirmasi password" class="<?= $mata ?>">
            <i data-lucide="eye" class="h-4 w-4"></i>
        </button>
        </div>
        <p id="pesanKonfirmasi" class="mt-1 hidden text-xs font-semibold text-red-700">Konfirmasi password belum sama.</p>
    </div>

    <button type="submit" name="daftar" class="w-full rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2"> Registrasi Akun </button>

    <p class="mt-1 text-sm text-black/60">Sudah punya akun?
        <a href="login.php" class="font-semibold text-red-600 hover:text-red-700">Masuk di sini</a>
    </p>
    </form>

    </div>
</div>

<!-- Label & petunjuk berkas mengikuti tipe pengguna.
    Dibungkus (() => {...})() supaya nama variabelnya tidak bentrok dengan auth.js. -->
<script>
lucide.createIcons();
// Fakultas -> Jurusan UNM. Data berasal dari data-unm.php (satu sumber untuk PHP dan JS).
(() => {
const DAFTAR_JURUSAN = <?= json_encode($unm, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const JURUSAN_LAMA   = <?= json_encode($old["jurusan"], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const TIPE_DENGAN_FAKULTAS = ["mahasiswa", "dosen"];   // aturan yang sama dengan kondisi PHP di atas

const pilihTipe = document.getElementById("tipe");
const blok      = document.getElementById("blokFakultas");
const fakultas  = document.getElementById("fakultas");
const jurusan   = document.getElementById("jurusan");
const grupInduk = document.getElementById("grupInduk");
const grupUnit  = document.getElementById("grupUnit");
const inputUnit = document.getElementById("unit");

// Isi ulang dropdown jurusan sesuai fakultas. "dipilih" = jurusan yang dipilih ulang (opsional).
function isiJurusan(namaFakultas, dipilih = "") {
    const daftar = DAFTAR_JURUSAN[namaFakultas] || [];
    jurusan.innerHTML = "";

    const placeholder = new Option(daftar.length ? "Pilih jurusan" : "Pilih fakultas dulu", "");
    placeholder.disabled = true;
    placeholder.selected = !dipilih;
    jurusan.add(placeholder);

    daftar.forEach(nama => jurusan.add(new Option(nama, nama, false, nama === dipilih)));

    jurusan.disabled = daftar.length === 0;
    jurusan.required = daftar.length > 0;
}

// Tampilkan/sembunyikan blok sesuai tipe pengguna.
function ikutiTipe() {
    const tampil = TIPE_DENGAN_FAKULTAS.includes(pilihTipe.value);

    blok.classList.toggle("hidden", !tampil);
    fakultas.disabled = !tampil;
    fakultas.required = tampil;

    if (!tampil) {                         // tendik: kosongkan supaya tidak ikut terkirim
    fakultas.selectedIndex = 0;
    isiJurusan("");
    }

    // Kolom unit hanya dipakai tendik. Disabled = tidak divalidasi dan tidak terkirim.
    grupUnit.classList.toggle("hidden", tampil);
    inputUnit.disabled = tampil;
    grupInduk.classList.toggle("sm:col-span-2", tampil);
}

pilihTipe.addEventListener("change", ikutiTipe);
fakultas.addEventListener("change", () => isiJurusan(fakultas.value));

isiJurusan(fakultas.value, JURUSAN_LAMA);   // kondisi awal (form diisi ulang setelah error)
ikutiTipe();
})();
</script>

<?php require_once "../footer.php"; ?>