
<?php
require "../init.php";

// Hapus seluruh data pengguna dari sesi.
$_SESSION = [];

// Hapus cookie sesi dari browser.
if (ini_get("session.use_cookies")) {
    $parameter = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parameter["path"],
        $parameter["domain"],
        $parameter["secure"],
        $parameter["httponly"]
    );
}

// Hancurkan sesi PHP.
session_destroy();

// Arahkan pengguna ke halaman login.
header("Location: " . BASE_URL . "auth/login.php");
exit;
