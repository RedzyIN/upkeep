<?php
// ===== Umum =====
function e($teks) {                       // escape output, pengganti htmlspecialchars panjang
  return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}

function redirect(string $url) {
  header("Location: $url");
  exit;
}

// ===== Pesan singkat (flash) yang tampil sebagai toast di footer.php =====
function setFlash(string $tipe, string $pesan) {   // tipe: success | error | warning | info
  $_SESSION["flash"] = [$tipe, $pesan];
}

function ambilFlash() {
  $f = $_SESSION["flash"] ?? null;
  unset($_SESSION["flash"]);
  return $f;
}

// ===== CSRF =====
function csrfField(): string {
  if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(32));
  return '<input type="hidden" name="csrf" value="' . $_SESSION["csrf"] . '">';
}

function csrfCek() {                     // panggil di awal setiap proses POST
  $kirim = $_POST["csrf"] ?? "";
  if (empty($_SESSION["csrf"]) || !hash_equals($_SESSION["csrf"], $kirim)) {
    http_response_code(403);
    die("Token tidak valid. Muat ulang halaman lalu coba lagi.");
  }
}

// ===== Tampilan =====
function tglIndo(?string $tgl): string {
  if (!$tgl) return "-";
  $bulan = ["", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  $t = strtotime($tgl);
  return date("j", $t) . " " . $bulan[(int) date("n", $t)] . " " . date("Y", $t);
}

function badgeStatus(string $s): string {
  $warna = [
    "dilaporkan" => "bg-amber-100 text-amber-800 border border-yellow-300 rounded-md",
    "diproses"   => "bg-sky-100 text-sky-800 border border-blue-300 rounded-md",
    "selesai"    => "bg-emerald-100 text-emerald-800 border border-green-300 rounded-md",
  ][$s] ?? "bg-slate-100 text-slate-700 ring-slate-500/20";
  return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $warna . '">' . e(ucfirst($s)) . '</span>';
}

function badgePrioritas(string $p): string {
  $warna = [
    "rendah" => "text-slate-600",
    "sedang" => "text-amber-700",
    "tinggi" => "text-rose-700",
  ][$p] ?? "text-slate-600";
  return '<span class="text-xs font-semibold ' . $warna . '">' . e(ucfirst($p)) . '</span>';
}
