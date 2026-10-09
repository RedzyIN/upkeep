<?php
function userLogin() {                    // null kalau belum login
  return $_SESSION["user"] ?? null;
}

function loginUser(array $row) {          // panggil setelah password_verify() berhasil
  session_regenerate_id(true);
  $_SESSION["user"] = [
    "id"   => (int) $row["id"],
    "nama" => $row["nama"],
    "role" => $row["role"],
  ];
}

function wajibLogin() {
  if (!userLogin()) {
    setFlash("warning", "Silakan login terlebih dahulu.");
    redirect("login.php");
  }
}

function wajibAdmin() {
  wajibLogin();
  if (userLogin()["role"] !== "admin") {
    setFlash("error", "Halaman ini khusus admin.");
    redirect("dashboard.php");
  }
}

function wajibTamu() {                    // untuk login.php & register.php
  if (userLogin()) redirect("dashboard.php");
}

function isAdmin(): bool {
  return (userLogin()["role"] ?? "") === "admin";
}
