<?php
  $u = userLogin();
  $f = ambilFlash();      // pesan dari setFlash(), tampil sebagai toast
?>
    </main>
<?php if ($u): ?>
  </div><!-- /konten -->
</div><!-- /flex -->
<?php endif; ?>

<script>
  // Ikon Lucide
  lucide.createIcons();

  // Sidebar di HP
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  const btnMenu = document.getElementById("btnMenu");
  function toggleSidebar(buka) {
    sidebar.classList.toggle("-translate-x-full", !buka);
    overlay.classList.toggle("hidden", !buka);
  }
  if (btnMenu) {
    btnMenu.addEventListener("click", () => toggleSidebar(true));
    overlay.addEventListener("click", () => toggleSidebar(false));
  }

  // Toast
  const Toast = Swal.mixin({ toast: true, position: "top-end", showConfirmButton: false, timer: 3500, timerProgressBar: true });
  <?php if ($f): ?>
  Toast.fire({ icon: <?= json_encode($f[0]) ?>, title: <?= json_encode($f[1]) ?> });
  <?php endif; ?>

  // Konfirmasi hapus: <form data-konfirmasi="Hapus laporan ini?"> ... </form>
  document.querySelectorAll("form[data-konfirmasi]").forEach(form => {
    form.addEventListener("submit", e => {
      e.preventDefault();
      Swal.fire({
        title: form.dataset.konfirmasi,
        text: "Tindakan ini tidak bisa dibatalkan.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e11d48",
        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batal"
      }).then(r => { if (r.isConfirmed) form.submit(); });
    });
  });

  // Pencarian tabel langsung: <input data-cari="#idTabel">
  document.querySelectorAll("input[data-cari]").forEach(input => {
    input.addEventListener("input", () => {
      const kata = input.value.toLowerCase();
      document.querySelectorAll(input.dataset.cari + " tbody tr").forEach(tr => {
        tr.classList.toggle("hidden", !tr.textContent.toLowerCase().includes(kata));
      });
    });
  });

  // Angka naik di kartu dashboard: <span data-count="128">0</span>
  const kurangGerak = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  document.querySelectorAll("[data-count]").forEach(el => {
    const target = parseInt(el.dataset.count, 10) || 0;
    if (kurangGerak || target === 0) { el.textContent = target; return; }
    const mulai = performance.now();
    const durasi = 700;
    (function langkah(sekarang) {
      const p = Math.min((sekarang - mulai) / durasi, 1);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(langkah);
    })(mulai);
  });

  // Font default Chart.js (kalau halaman memakai chart)
  if (window.Chart) Chart.defaults.font.family = '"Instrument Sans", system-ui, sans-serif';
</script>
</body>
</html>
