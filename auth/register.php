<?php
require_once "../init.php";
wajibTamu();

$judul       = "Upkeep";
$tanpaNavbar = true;
$jsHalaman   = ["assets/js/auth.js"];

$error = "";

$old = ["nama" => "", "email" => "","tipe" => "", "no_induk" => "", "unit" => "", "fakultas" => "", "jurusan"  => ""];

// Data fakultas dan jurusan UNM.
$unm = require "../data/data_unm.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrfCek();

    // Ambil data dan pertahankan isian jika validasi gagal.
    foreach (array_keys($old) as $kolom) {
        $old[$kolom] = trim((string) ($_POST[$kolom] ?? ""));
    }

    $nama       = $old["nama"];
    $email      = strtolower($old["email"]);
    $tipe       = $old["tipe"];
    $noInduk    = $old["no_induk"];
    $unitInput  = $old["unit"];
    $fakultas   = $old["fakultas"];
    $jurusan    = $old["jurusan"];

    $password   = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi"] ?? "";

    // 1. Validasi nama.
    if ($nama === "" || mb_strlen($nama) > 100) {
        $error = "Nama wajib diisi dan maksimal 100 karakter.";
    }

    // 2. Validasi email.
    elseif (
        strlen($email) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = "Masukkan alamat email yang valid.";
    }

    // 3. Tipe pengguna harus sesuai pilihan yang diizinkan.
    elseif (!in_array($tipe, ["mahasiswa", "dosen", "tendik"], true)) {
        $error = "Pilih tipe pengguna yang valid.";
    }

    // 4. Validasi nomor induk.
    elseif (!preg_match('/^[0-9A-Za-z.-]{5,30}$/', $noInduk)) {
        $error = "Nomor induk harus berisi 5–30 karakter berupa huruf, angka, titik, atau strip.";
    }

    // 5. Mahasiswa dan dosen harus memilih fakultas dan jurusan.
    elseif (in_array($tipe, ["mahasiswa", "dosen"], true)) {
        if (
            $fakultas === "" ||
            !isset($unm[$fakultas]) ||
            !is_array($unm[$fakultas]) ||
            !in_array($jurusan, $unm[$fakultas], true)
        ) {
            $error = "Pilih fakultas dan jurusan yang valid.";
        } else {
            // Database saat ini hanya mempunyai kolom `unit`.
            $unit = $fakultas . " - " . $jurusan;

            if (mb_strlen($unit) > 100) {
                $error = "Gabungan fakultas dan jurusan melebihi 100 karakter.";
            }
        }
    }

    // 6. Tenaga kependidikan mengisi unit kerja.
    if (
        $error === "" &&
        $tipe === "tendik"
    ) {
        if ($unitInput === "" || mb_strlen($unitInput) > 100) {
            $error = "Unit kerja wajib diisi dan maksimal 100 karakter.";
        } else {
            $unit = $unitInput;
        }
    }

    // 7. Validasi password.
    if ($error === "") {
        if (strlen($password) < 8) {
            $error = "Password minimal 8 karakter.";
        } elseif ($password !== $konfirmasi) {
            $error = "Konfirmasi password tidak sama.";
        }
    }

    if ($error === "") {
        try {
            // 8. Periksa apakah email sudah digunakan.
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = :email LIMIT 1"
            );
            $stmt->execute(["email" => $email]);

            if ($stmt->fetch()) {
                $error = "Email sudah terdaftar. Gunakan email lain.";
            } else {
                // 9. Periksa apakah nomor induk sudah digunakan.
                $stmt = $pdo->prepare(
                    "SELECT id FROM users WHERE no_induk = :no_induk LIMIT 1"
                );
                $stmt->execute(["no_induk" => $noInduk]);

                if ($stmt->fetch()) {
                    $error = "Nomor induk sudah terdaftar.";
                }
            }

            if ($error === "") {
                // 10. Hash password sebelum disimpan.
                $hashPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($hashPassword === false) {
                    throw new RuntimeException("Gagal mengamankan password.");
                }

                // 11. Simpan akun baru dengan role user.
                // Role tidak diambil dari input pengguna.
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                        (nama, email, password, role, tipe, no_induk, unit)
                     VALUES
                        (:nama, :email, :password, 'user', :tipe, :no_induk, :unit)"
                );

                $stmt->execute([
                    "nama"     => $nama,
                    "email"    => $email,
                    "password" => $hashPassword,
                    "tipe"     => $tipe,
                    "no_induk" => $noInduk,
                    "unit"     => $unit,
                ]);

                setFlash("success", "Registrasi berhasil. Silakan login.");
                redirect("login.php");
            }
        } catch (PDOException $e) {
            // Catat detail di log server, bukan ditampilkan kepada pengguna.
            error_log("Registrasi Upkeep gagal: " . $e->getMessage());

            if ($e->getCode() === "23000") {
                $error = "Email atau nomor induk sudah digunakan.";
            } else {
                $error = "Terjadi kesalahan saat menyimpan akun. Coba lagi.";
            }
        } catch (RuntimeException $e) {
            error_log("Registrasi Upkeep gagal: " . $e->getMessage());
            $error = "Terjadi kesalahan saat membuat akun. Coba lagi.";
        }
    }

    // Gunakan email yang sudah dinormalisasi pada form jika validasi gagal.
    $old["email"] = $email;
}

$tampilFakultas = in_array(
    $old["tipe"],
    ["mahasiswa", "dosen"],
    true
);

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