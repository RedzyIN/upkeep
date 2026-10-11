// auth.js - dipakai oleh login.php dan register.php

// 2) Tombol "Isi sebagai Admin/User" di halaman login (hanya untuk demo)
document.querySelectorAll("[data-demo-email]").forEach(tombol => {
  tombol.addEventListener("click", () => {
    document.getElementById("email").value = tombol.dataset.demoEmail;
    document.getElementById("password").value = tombol.dataset.demoPass;
    document.getElementById("password").focus();
  });
});

// 3) Register: label dan saran berganti sesuai tipe pengguna
const KONFIG_TIPE = {
  mahasiswa: {
    induk: "NIM", indukContoh: "Contoh: 2024101001", indukHint: "Nomor Induk Mahasiswa.",
  },
  dosen: {
    induk: "NIDN / NIDK", indukContoh: "Contoh: 0012058801", indukHint: "Nomor Induk Dosen Nasional/Khusus.",
  },
  tendik: {
    induk: "NIP / NIK pegawai", indukContoh: "Contoh: 198501012010011001", indukHint: "Nomor Induk Pegawai.",
  }
};

const pilihTipe = document.getElementById("tipe");
if (pilihTipe) {
  const ubahLabel = () => {
    const k = KONFIG_TIPE[pilihTipe.value];
    if (!k) return;                                  // belum ada tipe dipilih
    document.getElementById("labelInduk").textContent = k.induk;
    document.getElementById("no_induk").placeholder = k.indukContoh;
    document.getElementById("hintInduk").textContent = k.indukHint;
    document.getElementById("labelUnit").textContent = k.unit;
    document.getElementById("unit").placeholder = k.unitContoh;
    document.getElementById("daftar-unit").innerHTML = k.saran.map(s => `<option value="${s}">`).join("");
  };
  pilihTipe.addEventListener("change", ubahLabel);
  ubahLabel();                                       // jalan juga saat halaman dimuat ulang dengan isian lama
}

// 4) Register: cek konfirmasi password sama
const pw = document.getElementById("password");
const kf = document.getElementById("konfirmasi");
const pesanKf = document.getElementById("pesanKonfirmasi");
if (pw && kf && pesanKf) {
  const cekSama = () => {
    const beda = kf.value !== "" && pw.value !== kf.value;
    kf.setCustomValidity(pw.value !== kf.value ? "Konfirmasi password tidak sama." : "");
    pesanKf.classList.toggle("hidden", !beda);
  };
  pw.addEventListener("input", cekSama);
  kf.addEventListener("input", cekSama);
}

// Tombol lihat/sembunyikan password. Pakai di HTML: onclick="togglePassword('idInput', this)"
function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  const sedangTersembunyi = input.type === 'password';

  input.type = sedangTersembunyi ? 'text' : 'password';
  button.innerHTML = sedangTersembunyi
    ? '<i data-lucide="eye-off" class="h-4 w-4"></i>'
    : '<i data-lucide="eye" class="h-4 w-4"></i>';
  button.setAttribute('aria-label', sedangTersembunyi ? 'Sembunyikan password' : 'Tampilkan password');
  lucide.createIcons();
}