// ---------- 2. Tandai menu aktif saat menggulir ----------
const linkMenu = document.querySelectorAll("[data-nav]");

const pengamatMenu = new IntersectionObserver((daftar) => {
  daftar.forEach(item => {
    if (!item.isIntersecting) return;
    linkMenu.forEach(link => {
      const aktif = link.dataset.nav === item.target.id;
      link.classList.toggle("text-red-700", aktif);
      link.classList.toggle("font-semibold", aktif);
    });
  });
}, { rootMargin: "-40% 0px -50% 0px" });   // aktif saat section ada di tengah layar

["cara-kerja", "fitur", "status", "faq"].forEach(id => {
  pengamatMenu.observe(document.getElementById(id));
});


// ---------- 3. FAQ accordion ----------
document.querySelectorAll("[data-faq]").forEach(tombol => {
  tombol.addEventListener("click", () => {
    const jawaban = tombol.nextElementSibling;
    const buka    = jawaban.classList.contains("hidden");   // true = akan dibuka

    jawaban.classList.toggle("hidden", !buka);
    tombol.setAttribute("aria-expanded", buka);
    tombol.querySelector("svg, i").classList.toggle("rotate-180", buka);
  });
});


// ---------- 4. Animasi angka ----------
function animasiAngka(elemen, target) {
  const awal   = parseInt(elemen.textContent) || 0;
  const durasi = 800;               // milidetik
  const mulai  = performance.now();

  function langkah(sekarang) {
    const progres = Math.min((sekarang - mulai) / durasi, 1);
    elemen.textContent = Math.round(awal + (target - awal) * progres);
    if (progres < 1) requestAnimationFrame(langkah);
  }
  requestAnimationFrame(langkah);
}


// ---------- 5. Status layanan (data dari api-status.php) ----------
let dataTerakhir    = null;    // hasil fetch terbaru
let dampakTerlihat  = false;   // true setelah bagian "Dampak" muncul di layar

function waktuRelatif(detik) {
  if (detik < 60)    return "baru saja";
  if (detik < 3600)  return Math.floor(detik / 60) + " menit lalu";
  if (detik < 86400) return Math.floor(detik / 3600) + " jam lalu";
  return Math.floor(detik / 86400) + " hari lalu";
}

// warna badge per status
const warnaStatus = {
  diproses: "bg-amber-100 text-amber-700",
  selesai:  "bg-green-100 text-green-700"
};

function tampilkanStatus(data) {
  // indikator sistem
  document.getElementById("sistemTitik").className =
    "h-3 w-3 rounded-full " + (data.sistem_ok ? "bg-green-500" : "bg-red-700");
  document.getElementById("sistemTeks").textContent =
    data.sistem_ok ? "Beroperasi normal" : "Gangguan koneksi";

  document.getElementById("angkaHariIni").textContent = data.hari_ini;
  document.getElementById("angkaProses").textContent  = data.diproses;

  // daftar aktivitas
  const ul = document.getElementById("daftarAktivitas");
  ul.innerHTML = "";

  if (data.aktivitas.length === 0) {
    ul.innerHTML = '<li class="py-3 text-slate-400">Belum ada aktivitas.</li>';
    return;
  }

  data.aktivitas.forEach(a => {
    const li = document.createElement("li");
    li.className = "flex items-center justify-between gap-3 py-3";

    const kiri = document.createElement("span");
    kiri.textContent = "Laporan " + a.kategori;      // textContent = aman dari XSS

    const kanan = document.createElement("span");
    kanan.className = "flex items-center gap-2 text-xs text-slate-500";

    const badge = document.createElement("span");
    badge.className = "rounded-full px-2 py-0.5 font-semibold " +
                      (warnaStatus[a.status] || "bg-slate-100 text-slate-700");
    badge.textContent = a.status;

    const waktu = document.createElement("span");
    waktu.textContent = waktuRelatif(a.detik_lalu);

    kanan.append(badge, waktu);
    li.append(kiri, kanan);
    ul.appendChild(li);
  });
}

function tampilkanDampak() {
  if (!dataTerakhir) return;
  animasiAngka(document.getElementById("dampakTotal"),   dataTerakhir.total);
  animasiAngka(document.getElementById("dampakSelesai"), dataTerakhir.selesai);
  animasiAngka(document.getElementById("dampakProses"),  dataTerakhir.diproses);
  animasiAngka(document.getElementById("dampakPersen"),  dataTerakhir.persen_selesai);
}

async function muatStatus() {
  try {
    const respon = await fetch("api-status.php", { cache: "no-store" });
    dataTerakhir = await respon.json();
    tampilkanStatus(dataTerakhir);
    if (dampakTerlihat) tampilkanDampak();
  } catch (err) {
    // gagal fetch = server tidak terjangkau
    document.getElementById("sistemTitik").className = "h-3 w-3 rounded-full bg-red-700";
    document.getElementById("sistemTeks").textContent = "Tidak dapat terhubung";
  }
}

muatStatus();
setInterval(muatStatus, 300000);   // ulangi tiap 5 menit


// Animasi angka "Dampak" dijalankan sekali, saat bagiannya pertama kali terlihat
const pengamatDampak = new IntersectionObserver((daftar) => {
  if (!daftar[0].isIntersecting) return;
  dampakTerlihat = true;
  tampilkanDampak();
  pengamatDampak.disconnect();
}, { threshold: 0.3 });

pengamatDampak.observe(document.getElementById("dampak"));