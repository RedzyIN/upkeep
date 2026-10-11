
<?php
require "../init.php";

// Pastikan pengguna sudah login.
if (!userLogin()) {
    setFlash("warning", "Silakan login terlebih dahulu.");
    redirect("../auth/login.php");
}

// Penghapusan hanya boleh dilakukan melalui POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Metode permintaan tidak diizinkan.");
}

// Validasi token CSRF.
csrfCek();

$u = userLogin();
$admin = isAdmin();

// Validasi ID laporan.
$idInput = $_POST["id"] ?? null;

$id = filter_var(
    is_string($idInput) || is_int($idInput) ? $idInput : null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if ($id === false || $id === null) {
    setFlash("error", "ID laporan tidak valid.");
    redirect("laporan.php");
}

try {
    // Ambil laporan yang akan dihapus.
    $stmt = $pdo->prepare(
        "SELECT id, user_id, status, foto, foto_selesai
           FROM laporan
          WHERE id = :id
          LIMIT 1"
    );

    $stmt->execute(["id" => $id]);

    $laporan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$laporan) {
        setFlash("error", "Laporan tidak ditemukan.");
        redirect("laporan.php");
    }

    // Pelapor hanya boleh menghapus laporan miliknya
    // yang statusnya masih dilaporkan.
    if (
        !$admin &&
        (
            (int) $laporan["user_id"] !== (int) $u["id"] ||
            $laporan["status"] !== "dilaporkan"
        )
    ) {
        setFlash(
            "error",
            "Kamu hanya dapat membatalkan laporan milikmu yang masih dilaporkan."
        );

        redirect("laporan.php");
    }

    // Hapus laporan dengan tetap memeriksa hak akses.
    if ($admin) {
        $hapus = $pdo->prepare(
            "DELETE FROM laporan WHERE id = :id"
        );

        $hapus->execute([
            "id" => $id,
        ]);
    } else {
        $hapus = $pdo->prepare(
            "DELETE FROM laporan
              WHERE id = :id
                AND user_id = :user_id
                AND status = 'dilaporkan'"
        );

        $hapus->execute([
            "id" => $id,
            "user_id" => (int) $u["id"],
        ]);
    }

    // Pastikan tepat satu laporan berhasil dihapus.
    if ($hapus->rowCount() !== 1) {
        setFlash(
            "error",
            "Laporan gagal dihapus. Muat ulang halaman lalu coba lagi."
        );

        redirect("laporan.php");
    }

} catch (Throwable $e) {
    // Catat detail teknis di server, bukan kepada pengguna.
    error_log("Hapus laporan gagal: " . $e->getMessage());

    setFlash(
        "error",
        "Terjadi kesalahan saat menghapus laporan."
    );

    redirect("laporan.php");
}

// Pada tahap ini, laporan sudah berhasil dihapus dari database.
// Bersihkan file foto setelahnya.
$fotoUtama = $laporan["foto"] ?? null;
$fotoSelesai = $laporan["foto_selesai"] ?? null;

if (!hapusFoto($fotoUtama)) {
    error_log(
        "Foto laporan gagal dibersihkan setelah penghapusan laporan ID: " . $id
    );
}

if (!hapusFoto($fotoSelesai)) {
    error_log(
        "Foto penyelesaian gagal dibersihkan setelah penghapusan laporan ID: " . $id
    );
}

// Tampilkan pesan sesuai peran pengguna.
setFlash(
    "success",
    $admin
        ? "Laporan berhasil dihapus."
        : "Laporan berhasil dibatalkan."
);

redirect("laporan.php");