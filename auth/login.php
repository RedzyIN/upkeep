<?php
require_once "../init.php";
wajibTamu();

$judul     = "Masuk";
$jsHalaman = ["assets/js/auth.js"];

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Pastikan form berasal dari sesi yang benar.
    csrfCek();

    $email = strtolower(trim((string) ($_POST["email"] ?? "")));
    $password = $_POST["password"] ?? "";

    if (
        $email === "" ||
        strlen($email) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = "Masukkan email yang valid.";
    } elseif ($password === "") {
        $error = "Password wajib diisi.";
    } else {
        try {
            // Cari akun berdasarkan email.
            $stmt = $pdo->prepare(
                "SELECT id, nama, email, password, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );

            $stmt->execute(["email" => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Pesan dibuat sama agar tidak membocorkan keberadaan email.
            if (
                !$user ||
                !password_verify($password, $user["password"])
            ) {
                $error = "Email atau password salah.";
            } else {
                // Buat sesi login dan regenerasi ID sesi.
                loginUser($user);

                // Perbarui token CSRF setelah login.
                $_SESSION["csrf"] = bin2hex(random_bytes(32));

                // Arahkan ke dashboard setelah berhasil masuk.
                redirect(BASE_URL . "dashboard/dashboard.php");
            }
        } catch (PDOException $e) {
            error_log("Login Upkeep gagal: " . $e->getMessage());
            $error = "Terjadi kesalahan pada server. Coba lagi.";
        }
    }
}

$inp = "w-full rounded-md border border-black/20 bg-white px-3 py-2.5 text-sm text-black placeholder:text-black/40 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/30";
$mata = "absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-black/60 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600";

require_once "../header.php";
?>


<section class="flex items-center justify-center p-4 sm:p-8">
<div class="w-full max-w-md rounded-lg border border-black/10 bg-white p-6 shadow-sm sm:p-8">
    <h2 class="text-2xl font-bold text-center">Log in</h2>

    <?php if ($error): ?>
    <div role="alert" class="mt-5 flex items-start gap-2 rounded-md border border-red-600/30 bg-red-600/10 px-3 py-2.5 text-sm text-red-700">
    <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
    <div><?= e($error) ?></div>
    </div>
    <?php endif; ?>

    <form method="post" action="login.php" class="mt-6 space-y-4" novalidate>
    <?= csrfField() ?>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-semibold">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus
                autocomplete="username" placeholder="nama@kampus.ac.id" class="<?= $inp ?>">
    </div>

    <div>
        <label for="password" class="mb-1.5 block text-sm font-semibold">Password</label>
        <div class="relative">
        <input type="password" id="password" name="password" required
                autocomplete="current-password" placeholder="Masukkan password" class="<?= $inp ?> pr-11">
        <button type="button" onclick="togglePassword('password', this)" aria-label="Tampilkan password" class="<?= $mata ?>">
            <i data-lucide="eye" class="h-4 w-4"></i>
        </button>
        </div>
    </div>

    <button type="submit" name="masuk" class="w-full rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">Masuk</button>

    <p class="mt-1 text-sm text-black/60">Belum punya akun?
        <a href="register.php" class="font-semibold text-red-600 hover:text-red-700">Daftar di sini</a>
    </p>
    </form>

    <!-- Akun demo (hapus blok ini saat dipakai sungguhan) -->
    <div class="mt-6 border-t border-black/10 pt-5">
    <p class="text-xs font-semibold uppercase tracking-wide text-black/50">Coba akun demo</p>
    <div class="mt-3 grid grid-cols-2 gap-2">
        <button type="button" data-demo-email="admin@kampus.test" data-demo-pass="admin123"
                class="rounded-md border border-black/20 px-3 py-2 text-sm font-semibold hover:bg-slate-200">Isi sebagai Admin</button>
        <button type="button" data-demo-email="mahasiswa@kampus.test" data-demo-pass="user123"
                class="rounded-md border border-black/20 px-3 py-2 text-sm font-semibold hover:bg-slate-200">Isi sebagai User</button>
    </div>
    </div>
</div>
</section>

</div>
<?php require "../footer.php"; ?>