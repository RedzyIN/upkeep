<?php
// dashboard/profil.php
// Halaman profil akun Upkeep.

require "../init.php";

// Pastikan pengguna sudah login.
if (!userLogin()) {
    setFlash("warning", "Silakan login terlebih dahulu.");
    redirect(BASE_URL . "auth/login.php");
}

$u = userLogin();
$judul = "Profil Saya";
$jejak = [["Profil Saya", null]];
$jsHalaman = [];

$pesanError = "";
$pesanSukses = "";

// Kelas input agar konsisten dengan halaman Upkeep lainnya.
$inp = "w-full rounded-md border border-black/20 bg-white px-3 py-2.5 text-sm text-black placeholder:text-black/40 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30";

$tipePengguna = [
    "mahasiswa" => "Mahasiswa",
    "dosen"     => "Dosen",
    "tendik"    => "Tenaga Kependidikan",
];

$rolePengguna = [
    "admin" => "Admin",
    "user"  => "Pelapor",
];

// Proses formulir.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrfCek();

    $aksi = $_POST["aksi"] ?? "";

    try {
        if ($aksi === "update_profil") {
            $nama = trim($_POST["nama"] ?? "");
            $email = trim($_POST["email"] ?? "");

            if ($nama === "" || mb_strlen($nama) > 100) {
                throw new RuntimeException(
                    "Nama wajib diisi dan maksimal 100 karakter."
                );
            }

            if (
                $email === "" ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                mb_strlen($email) > 100
            ) {
                throw new RuntimeException(
                    "Masukkan alamat email yang valid, maksimal 100 karakter."
                );
            }

            // Pastikan email belum dipakai akun lain.
            $cekEmail = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1"
            );
            $cekEmail->execute([$email, (int) $u["id"]]);

            if ($cekEmail->fetch()) {
                throw new RuntimeException(
                    "Email tersebut sudah digunakan akun lain."
                );
            }

            $update = $pdo->prepare(
                "UPDATE users SET nama = ?, email = ? WHERE id = ?"
            );
            $update->execute([$nama, $email, (int) $u["id"]]);

            // Perbarui nama pada session agar topbar ikut berubah.
            $_SESSION["user"]["nama"] = $nama;

            setFlash("success", "Informasi akun berhasil diperbarui.");
            redirect(BASE_URL . "dashboard/profil.php");

        } elseif ($aksi === "ganti_password") {
            $passwordLama = $_POST["password_lama"] ?? "";
            $passwordBaru = $_POST["password_baru"] ?? "";
            $konfirmasi = $_POST["konfirmasi_password"] ?? "";

            if (
                $passwordLama === "" ||
                $passwordBaru === "" ||
                $konfirmasi === ""
            ) {
                throw new RuntimeException(
                    "Semua kolom password wajib diisi."
                );
            }

            if (strlen($passwordBaru) < 8) {
                throw new RuntimeException(
                    "Password baru harus terdiri dari minimal 8 karakter."
                );
            }

            if ($passwordBaru !== $konfirmasi) {
                throw new RuntimeException(
                    "Konfirmasi password baru tidak cocok."
                );
            }

            // Ambil hash password terbaru dari database.
            $stmt = $pdo->prepare(
                "SELECT password FROM users WHERE id = ? LIMIT 1"
            );
            $stmt->execute([(int) $u["id"]]);
            $akunPassword = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$akunPassword) {
                throw new RuntimeException(
                    "Akun tidak ditemukan."
                );
            }

            // Jangan menyimpan password dalam bentuk teks biasa.
            if (!password_verify(
                $passwordLama,
                $akunPassword["password"]
            )) {
                throw new RuntimeException(
                    "Password lama yang kamu masukkan tidak benar."
                );
            }

            if (password_verify(
                $passwordBaru,
                $akunPassword["password"]
            )) {
                throw new RuntimeException(
                    "Password baru harus berbeda dari password lama."
                );
            }

            $hashBaru = password_hash(
                $passwordBaru,
                PASSWORD_DEFAULT
            );

            $updatePassword = $pdo->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );
            $updatePassword->execute([
                $hashBaru,
                (int) $u["id"]
            ]);

            setFlash("success", "Password berhasil diganti.");
            redirect(BASE_URL . "dashboard/profil.php");

        } else {
            throw new RuntimeException(
                "Permintaan tidak dikenali. Silakan coba lagi."
            );
        }

    } catch (RuntimeException $e) {
        $pesanError = $e->getMessage();

    } catch (PDOException $e) {
        // Detail kesalahan database tidak ditampilkan kepada pengguna.
        $pesanError = "Data gagal disimpan. Periksa kembali data kamu.";
    }
}

// Ambil informasi akun terbaru dari database.
$akun = null;

try {
    $stmt = $pdo->prepare(
        "SELECT id, nama, email, role, tipe, no_induk, unit, created_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->execute([(int) $u["id"]]);
    $akun = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $pesanError = "Informasi akun belum bisa dimuat. Periksa koneksi database.";
}

require "../header.php";
?>

<!-- Judul halaman -->
<div class="mb-6">
    <h2 class="text-2xl font-bold tracking-tight">Profil Saya</h2>
    <p class="mt-1 text-sm text-slate-600">
        Kelola informasi akun, identitas kampus, dan keamanan akunmu.
    </p>
</div>

<?php if ($pesanError): ?>
    <div
        role="alert"
        class="mb-5 rounded-md border border-red-600/30 bg-red-600/10 px-4 py-3 text-sm text-red-700"
    >
        <div class="flex items-start gap-2">
            <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <span><?= e($pesanError) ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($akun): ?>

<!-- Ringkasan profil -->
<section class="mb-6 rounded-xl border border-black/10 bg-white p-5 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-red-600 text-2xl font-bold text-white">
            <?= e(strtoupper(mb_substr($akun["nama"], 0, 1))) ?>
        </div>

        <div class="min-w-0 flex-1">
            <h3 class="truncate text-xl font-bold">
                <?= e($akun["nama"]) ?>
            </h3>
            <p class="mt-1 break-all text-sm text-slate-500">
                <?= e($akun["email"]) ?>
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-600/10 px-3 py-1 text-xs font-semibold text-red-700">
                    <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                    <?= e($rolePengguna[$akun["role"]] ?? $akun["role"]) ?>
                </span>

                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    <?= e($tipePengguna[$akun["tipe"]] ?? $akun["tipe"]) ?>
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Tiga bagian utama -->
<div class="space-y-6">

    <!-- BAGIAN 1: INFORMASI AKUN -->
    <section class="overflow-hidden rounded-xl border border-black/10 bg-white">
        <div class="border-b border-black/10 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-600/10 text-red-700">
                    <i data-lucide="user-round" class="h-5 w-5"></i>
                </span>
                <div>
                    <h3 class="font-bold">Informasi Akun</h3>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Perbarui nama dan alamat email akunmu.
                    </p>
                </div>
            </div>
        </div>

        <form method="post" action="profil.php" class="space-y-5 p-5 sm:p-6">
            <?= csrfField() ?>
            <input type="hidden" name="aksi" value="update_profil">

            <div>
                <label for="nama" class="mb-1.5 block text-sm font-semibold">
                    Nama lengkap <span class="text-red-600">*</span>
                </label>
                <input type="text" id="nama" name="nama" maxlength="100" required autocomplete="name" value="<?= e(($_SERVER["REQUEST_METHOD"] === "POST" &&($_POST["aksi"] ?? "") === "update_profil") ? ($_POST["nama"] ?? $akun["nama"]): $akun["nama"]) ?>" placeholder="Masukkan nama lengkap" class="<?= $inp ?>">
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold">
                    Email <span class="text-red-600">*</span>
                </label>
                <input type="email" id="email" name="email" maxlength="100" required autocomplete="email" value="<?= e(($aksi ?? "") === "update_profil" && isset($_POST["email"]) ? $_POST["email"] : $akun["email"]) ?>" placeholder="nama@kampus.ac.id" class="<?= $inp ?>">
                <p class="mt-1.5 text-xs text-slate-500">
                    Gunakan email yang aktif dan dapat kamu akses.
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-black/5 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Kolom bertanda <span class="font-semibold text-red-600">*</span> wajib diisi.
                </p>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                >
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>

    <!-- BAGIAN 2: IDENTITAS KAMPUS -->
    <section class="overflow-hidden rounded-xl border border-black/10 bg-white">
        <div class="border-b border-black/10 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-600/10 text-red-700">
                    <i data-lucide="graduation-cap" class="h-5 w-5"></i>
                </span>
                <div>
                    <h3 class="font-bold">Identitas Kampus</h3>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Informasi identitas yang terdaftar pada akunmu.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                <div>
                    <p class="mb-1.5 text-sm font-semibold text-slate-600">
                        Tipe pengguna
                    </p>
                    <div class="flex min-h-11 items-center gap-2 rounded-md border border-black/10 bg-slate-50 px-3 py-2.5 text-sm">
                        <i data-lucide="users" class="h-4 w-4 text-slate-400"></i>
                        <?= e($tipePengguna[$akun["tipe"]] ?? $akun["tipe"]) ?>
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 text-sm font-semibold text-slate-600">
                        Nomor identitas
                    </p>
                    <div class="flex min-h-11 items-center gap-2 rounded-md border border-black/10 bg-slate-50 px-3 py-2.5 text-sm">
                        <i data-lucide="id-card" class="h-4 w-4 text-slate-400"></i>
                        <span class="break-all">
                            <?= e($akun["no_induk"] ?: "Belum diisi") ?>
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">
                        NIM, NIDN, atau NIP sesuai tipe pengguna.
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <p class="mb-1.5 text-sm font-semibold text-slate-600">
                        Program studi / unit kerja
                    </p>
                    <div class="flex min-h-11 items-center gap-2 rounded-md border border-black/10 bg-slate-50 px-3 py-2.5 text-sm">
                        <i data-lucide="building-2" class="h-4 w-4 shrink-0 text-slate-400"></i>
                        <span class="break-words">
                            <?= e($akun["unit"] ?: "Belum diisi") ?>
                        </span>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <p class="mb-1.5 text-sm font-semibold text-slate-600">
                        Hak akses
                    </p>
                    <div class="flex min-h-11 items-center gap-2 rounded-md border border-black/10 bg-slate-50 px-3 py-2.5 text-sm">
                        <i data-lucide="shield-check" class="h-4 w-4 text-slate-400"></i>
                        <?= e($rolePengguna[$akun["role"]] ?? $akun["role"]) ?>
                        <span class="ml-auto text-xs text-slate-500">Tidak dapat diubah</span>
                    </div>
                </div>

            </div>

            <div class="mt-5 flex items-start gap-2 rounded-lg border border-black/10 bg-slate-50 p-3.5 text-sm text-slate-600">
                <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-slate-500"></i>
                <p>
                    Identitas kampus ditampilkan sesuai data yang tersimpan.
                    Jika ada data yang keliru atau belum terisi, hubungi
                    administrator untuk memperbaruinya.
                </p>
            </div>
        </div>
    </section>

    <!-- BAGIAN 3: KEAMANAN AKUN -->
    <section class="overflow-hidden rounded-xl border border-black/10 bg-white">
        <div class="border-b border-black/10 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-600/10 text-red-700">
                    <i data-lucide="shield-check" class="h-5 w-5"></i>
                </span>
                <div>
                    <h3 class="font-bold">Keamanan Akun</h3>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Jaga akunmu dengan password yang kuat.
                    </p>
                </div>
            </div>
        </div>

        <form method="post" action="profil.php" class="space-y-5 p-5 sm:p-6">
            <?= csrfField() ?>
            <input type="hidden" name="aksi" value="ganti_password">

            <div class="rounded-lg border border-amber-300 bg-amber-50 p-3.5">
                <div class="flex items-start gap-2.5 text-sm text-amber-900">
                    <i data-lucide="lock-keyhole" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <p> Password lama diperlukan untuk memastikan bahwa perubahan dilakukan oleh pemilik akun.</p>
                </div>
            </div>

            <div>
                <label for="password_lama" class="mb-1.5 block text-sm font-semibold">
                    Password lama <span class="text-red-600">*</span>
                </label>
                <input
                    type="password"
                    id="password_lama"
                    name="password_lama"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan password saat ini"
                    class="<?= $inp ?>"
                >
            </div>

            <div>
                <label for="password_baru" class="mb-1.5 block text-sm font-semibold">
                    Password baru <span class="text-red-600">*</span>
                </label>
                <input
                    type="password"
                    id="password_baru"
                    name="password_baru"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    class="<?= $inp ?>"
                >
                <p class="mt-1.5 text-xs text-slate-500">
                    Gunakan password yang panjang dan sulit ditebak.
                </p>
            </div>

            <div>
                <label for="konfirmasi_password" class="mb-1.5 block text-sm font-semibold">
                    Konfirmasi password baru <span class="text-red-600">*</span>
                </label>
                <input
                    type="password"
                    id="konfirmasi_password"
                    name="konfirmasi_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Ulangi password baru"
                    class="<?= $inp ?>"
                >
            </div>

            <div class="flex justify-end border-t border-black/5 pt-5">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                >
                    <i data-lucide="key-round" class="h-4 w-4"></i>
                    Ganti Password
                </button>
            </div>
        </form>
    </section>

</div>

<?php else: ?>

<!-- Jika akun tidak ditemukan -->
<section class="rounded-xl border border-black/10 bg-white p-8 text-center">
    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-600/10 text-red-700">
        <i data-lucide="user-round-x" class="h-6 w-6"></i>
    </span>
    <h3 class="mt-4 font-bold">Informasi akun tidak ditemukan</h3>
    <p class="mt-1 text-sm text-slate-500">
        Pastikan akun tersedia di database dan kamu sudah login dengan benar.
    </p>
    <a
        href="dashboard.php"
        class="mt-5 inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
    >
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Kembali ke Dashboard
    </a>
</section>

<?php endif; ?>

<?php require "../footer.php"; ?>
