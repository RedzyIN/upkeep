// =====================================================================
//  transisi.js - efek halus saat halaman dimuat
//  1. Halaman fade-in saat dibuka
//  2. Elemen "timbul" (naik pelan + memudar masuk) saat terlihat di layar
//  3. Angka teracak lalu berhenti di nilai aslinya
//
//  Cara pakai:
//    - Pasang SATU kali di footer.php:  <script src="<?= BASE_URL ?>assets/js/transisi.js"></script>
//    - Angka  : <span data-angka>128</span>     (juga boleh "92%", "1.250", "4,3", "500+")
//    - Timbul : <div data-muncul>...</div>      (opsional: data-muncul="200" = tunda 200 ms)
//    - Tanpa atribut pun, elemen di dalam <main> dan <section> otomatis timbul.
// =====================================================================
(function () {
  "use strict";

  // Hormati pengguna yang mematikan animasi di perangkatnya
  var kurangGerak = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // ---------- 1. CSS disuntikkan dari sini (tidak perlu file CSS terpisah) ----------
  var css = [
    "html.tr-siap body{animation:trFadeHalaman .5s ease-out both}",
    "@keyframes trFadeHalaman{from{opacity:0}to{opacity:1}}",
    ".tr-sembunyi{opacity:0;transform:translateY(16px)}",
    ".tr-tampil{opacity:1;transform:none;transition:opacity .6s ease-out,transform .6s ease-out}",
    "[data-angka]{font-variant-numeric:tabular-nums}",
    "@media (prefers-reduced-motion:reduce){",
    "  html.tr-siap body{animation:none}",
    "  .tr-sembunyi{opacity:1;transform:none}",
    "}"
  ].join("\n");
  var style = document.createElement("style");
  style.textContent = css;
  document.head.appendChild(style);
  document.documentElement.classList.add("tr-siap");

  // ---------- 2. Elemen timbul saat terlihat ----------
  function siapkanMuncul() {
    // Elemen yang diberi atribut + elemen otomatis di dalam main/section.
    // Footer, navbar, dan modal sengaja tidak ikut.
    var daftar = document.querySelectorAll(
      "[data-muncul], main > *, main section, section > div > *"
    );
    var terpilih = [];
    daftar.forEach(function (el) {
      if (el.closest("nav, header, footer, [role='dialog'], .fixed")) return;
      if (el.hasAttribute("data-tanpa-muncul")) return;
      if (terpilih.indexOf(el) !== -1) return;
      // Jangan animasi anak dari elemen yang sudah dianimasi (supaya tidak dobel)
      for (var i = 0; i < terpilih.length; i++) {
        if (terpilih[i].contains(el) && !el.hasAttribute("data-muncul")) return;
      }
      terpilih.push(el);
    });

    if (kurangGerak || !("IntersectionObserver" in window)) return;

    var pengamat = new IntersectionObserver(function (entri) {
      entri.forEach(function (e) {
        if (!e.isIntersecting) return;
        var el = e.target;
        var tunda = parseInt(el.getAttribute("data-muncul"), 10);
        if (isNaN(tunda)) tunda = 0;
        el.style.transitionDelay = tunda + "ms";
        el.classList.add("tr-tampil");
        el.classList.remove("tr-sembunyi");
        pengamat.unobserve(el);
      });
    }, { threshold: 0.12 });

    terpilih.forEach(function (el, i) {
      // Tunda kecil bertingkat untuk elemen bersaudara agar muncul berurutan
      if (!el.hasAttribute("data-muncul")) {
        el.setAttribute("data-muncul", String((i % 4) * 80));
      }
      el.classList.add("tr-sembunyi");
      pengamat.observe(el);
    });
  }

  // ---------- 3. Angka teracak lalu menjadi nilai asli ----------
  // "1.250" -> acak digitnya, tanda titik/koma/%/+ tetap di tempatnya.
  function acakAngka(el) {
    var asli = el.getAttribute("data-angka-asli") || el.textContent.trim();
    el.setAttribute("data-angka-asli", asli);
    if (kurangGerak || !/\d/.test(asli)) { el.textContent = asli; return; }

    var lama = 1200;                         // total durasi (ms)
    var mulai = performance.now();
    var huruf = asli.split("");

    function langkah(sekarang) {
      var p = Math.min(1, (sekarang - mulai) / lama);
      // Digit di kiri berhenti lebih dulu, yang kanan terus berputar
      var tetapSampai = Math.floor(p * huruf.length);
      var hasil = huruf.map(function (h, i) {
        if (!/\d/.test(h) || i < tetapSampai) return h;
        return String(Math.floor(Math.random() * 10));
      });
      el.textContent = p >= 1 ? asli : hasil.join("");
      if (p < 1) requestAnimationFrame(langkah);
    }
    requestAnimationFrame(langkah);
  }

  function siapkanAngka() {
    var angka = document.querySelectorAll("[data-angka]");
    if (!angka.length) return;

    if (!("IntersectionObserver" in window)) {
      angka.forEach(function (el) { el.textContent = el.textContent; });
      return;
    }
    var pengamat = new IntersectionObserver(function (entri) {
      entri.forEach(function (e) {
        if (!e.isIntersecting) return;
        acakAngka(e.target);
        pengamat.unobserve(e.target);
      });
    }, { threshold: 0.4 });

    angka.forEach(function (el) {
      // Simpan nilai asli dulu, lalu tampilkan digit acak sebelum masuk layar
      var asli = el.textContent.trim();
      el.setAttribute("data-angka-asli", asli);
      if (!kurangGerak) {
        el.textContent = asli.replace(/\d/g, function () {
          return String(Math.floor(Math.random() * 10));
        });
      }
      pengamat.observe(el);
    });
  }

  // Dipanggil dari JS lain bila angka baru muncul setelah fetch (mis. status_api):
  //   Transisi.angka(elemen)
  window.Transisi = { angka: acakAngka };

  function mulai() { siapkanMuncul(); siapkanAngka(); }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mulai);
  } else {
    mulai();
  }
})();