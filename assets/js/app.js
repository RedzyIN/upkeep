// app.js - JavaScript yang dipakai di SEMUA halaman (dimuat oleh footer.php)
lucide.createIcons();

// 2) Sidebar di HP (hanya ada di halaman setelah login)
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const btnMenu = document.getElementById("btnMenu");

function bukaSidebar() {
  sidebar.classList.remove("-translate-x-full");   // geser sidebar masuk ke layar
  overlay.classList.remove("hidden");              // tampilkan latar gelap
}

function tutupSidebar() {
  sidebar.classList.add("-translate-x-full");      // geser sidebar keluar layar
  overlay.classList.add("hidden");
}

if (btnMenu) {                                     // di halaman tamu tidak ada tombol menu
  btnMenu.addEventListener("click", bukaSidebar);
  overlay.addEventListener("click", tutupSidebar);
}


// 3) Toast (notifikasi kecil di pojok kanan atas)
const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3500,
  timerProgressBar: true
});

// Pesan dari PHP (setFlash) dititipkan footer.php di elemen #flash
const flash = document.getElementById("flash");
if (flash) {
  Toast.fire({ icon: flash.dataset.tipe, title: flash.dataset.pesan });
}


// 4) Konfirmasi hapus. Pakai di HTML: <form data-konfirmasi="Hapus laporan ini?">
document.querySelectorAll("form[data-konfirmasi]").forEach(form => {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();                            // tahan dulu, jangan langsung terkirim

    const hasil = await Swal.fire({                // await = tunggu sampai pengguna menjawab
      title: form.dataset.konfirmasi,
      text: "Tindakan ini tidak bisa dibatalkan.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc2626",
      confirmButtonText: "Ya, hapus",
      cancelButtonText: "Batal"
    });

    if (hasil.isConfirmed) {
      form.submit();                               // pengguna setuju, kirim form
    }
  });
});


// 5) Pencarian tabel langsung. Pakai di HTML: <input data-cari="#idTabel">
document.querySelectorAll("input[data-cari]").forEach(input => {
  input.addEventListener("input", () => {
    const kata = input.value.toLowerCase();
    const semuaBaris = document.querySelectorAll(input.dataset.cari + " tbody tr");

    semuaBaris.forEach(baris => {
      const teks = baris.textContent.toLowerCase();
      if (teks.includes(kata)) {
        baris.classList.remove("hidden");          // cocok: tampilkan
      } else {
        baris.classList.add("hidden");             // tidak cocok: sembunyikan
      }
    });
  });
});


// 6) Angka naik dari 0 ke tujuan. Pakai di HTML: <span data-count="128">0</span>
document.querySelectorAll("[data-count]").forEach(el => {
  const tujuan = Number(el.dataset.count) || 0;
  const loncat = Math.ceil(tujuan / 30) || 1;      // naik sedikit demi sedikit (30 langkah)
  let angka = 0;

  const timer = setInterval(() => {
    angka = angka + loncat;
    if (angka >= tujuan) {
      angka = tujuan;
      clearInterval(timer);                        // sudah sampai, hentikan
    }
    el.textContent = angka;
  }, 20);                                          // diulang tiap 20 milidetik
});

// Upload File
document.querySelectorAll('[data-file-field]').forEach((wrap) => {
  const input = wrap.querySelector('input[type="file"]');
  const list  = wrap.querySelector('[data-file-list]');
  const errEl = wrap.querySelector('[data-file-error]');
  const maxMb = Number(input.dataset.maxMb || 2);
  const multiple = input.multiple;
  const dt = new DataTransfer();   // penampung file yang valid

  const fmt = (b) => b < 1048576
    ? (b / 1024).toFixed(0) + ' KB'
    : (b / 1048576).toFixed(1) + ' MB';

  function showError(msg) {
    errEl.textContent = msg;
    errEl.classList.toggle('hidden', !msg);
  }

  function render() {
    list.innerHTML = '';
    [...dt.files].forEach((file, i) => {
      const li = document.createElement('li');
      li.className = 'flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-3';

      const left = document.createElement('div');
      left.className = 'min-w-0';
      const nameEl = document.createElement('p');
      nameEl.className = 'truncate text-sm font-medium text-gray-800';
      nameEl.textContent = file.name;            // textContent: aman dari XSS
      const sizeEl = document.createElement('p');
      sizeEl.className = 'text-xs text-gray-500';
      sizeEl.textContent = fmt(file.size);
      left.append(nameEl, sizeEl);

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ml-2 shrink-0 rounded-md p-1.5 text-gray-400 hover:text-red-500';
      btn.setAttribute('aria-label', 'Hapus file');
      btn.textContent = '✕';
      btn.addEventListener('click', () => {
        const next = new DataTransfer();
        [...dt.files].forEach((f, idx) => idx !== i && next.items.add(f));
        dt.items.clear();
        [...next.files].forEach((f) => dt.items.add(f));
        input.files = dt.files;
        render();
      });

      li.append(left, btn);
      list.append(li);
    });
  }

  function addFiles(files) {
    showError('');
    if (!multiple) dt.items.clear();
    for (const f of files) {
      if (f.size > maxMb * 1048576) {
        showError(`"${f.name}" melebihi ${maxMb} MB.`);
        continue;
      }
      dt.items.add(f);
    }
    input.files = dt.files;   // supaya ikut terkirim saat form di-submit
    render();
  }

  input.addEventListener('change', () => addFiles(input.files));

  // drag & drop
  const zone = wrap.querySelector('label');
  ['dragover', 'drop'].forEach((ev) =>
    zone.addEventListener(ev, (e) => {
      e.preventDefault();
      if (ev === 'drop') addFiles(e.dataTransfer.files);
    })
  );
});