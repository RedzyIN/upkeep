// tambah.js - JavaScript khusus halaman Buat Laporan (tambah_laporan.php)
//
// Isinya:
//   1) Validasi form di browser
//   2) Penghitung karakter deskripsi
//   3) Preview foto + cek ukuran maksimal 2 MB
//   4) Peringatan laporan serupa
//   5) Tombol kirim yang tidak bisa diklik dua kali

// Semua kode dibungkus fungsi ini supaya nama variabelnya tidak bentrok dengan app.js
(function () {

  // ==================================================================
  // 0) PENGATURAN (ubah di sini kalau perlu)
  // ==================================================================
  const MAKS_UKURAN   = 2 * 1024 * 1024;                          // 2 MB (satuan byte)
  const JENIS_BOLEH   = ["image/jpeg", "image/png", "image/webp"];
  const MIN_JUDUL     = 5;
  const MIN_DESKRIPSI = 20;

  // Peringatan laporan serupa:
  //   true  -> pakai DATA_DUMMY di bawah (belum butuh backend)
  //   false -> tanya ke server lewat fetch(), alamatnya URL_CEK_SERUPA
  const PAKAI_DUMMY    = true;                                    // DUMMY: ubah ke false kalau endpoint sudah dibuat
  const URL_CEK_SERUPA = "cek_serupa.php";                        // sesuaikan dengan lokasi file endpoint
  const URL_DETAIL     = "detail.php";                            // sesuaikan dengan halaman detail laporan

  // Bentuk jawaban yang diharapkan dari server (sama dengan DATA_DUMMY):
  //   GET cek_serupa.php?kategori_id=3&lokasi_id=3
  //   -> [ {"id": 7, "judul": "...", "status": "dilaporkan", "tanggal": "2026-10-09"}, ... ]
  //   Query-nya: laporan dengan kategori & lokasi yang sama dan status BUKAN 'selesai'.

  // DUMMY: id kategori & lokasi mengikuti data contoh di database.sql
  const DATA_DUMMY = [
    { id: 7, kategori_id: 3, lokasi_id: 3, judul: "AC bocor menetes ke meja",     status: "dilaporkan", tanggal: "2026-10-09" },
    { id: 9, kategori_id: 3, lokasi_id: 3, judul: "AC ruang lab berisik",         status: "diproses",   tanggal: "2026-10-05" },
    { id: 4, kategori_id: 5, lokasi_id: 4, judul: "WiFi perpustakaan putus",      status: "diproses",   tanggal: "2026-09-27" },
    { id: 5, kategori_id: 4, lokasi_id: 1, judul: "Kursi patah",                  status: "diproses",   tanggal: "2026-10-02" },
    { id: 6, kategori_id: 1, lokasi_id: 2, judul: "Lampu ruangan berkedip",       status: "dilaporkan", tanggal: "2026-10-07" },
    { id: 8, kategori_id: 6, lokasi_id: 5, judul: "Tempat sampah penuh",          status: "dilaporkan", tanggal: "2026-10-10" }
  ];


  // ==================================================================
  // 1) AMBIL ELEMEN HTML (berdasarkan id di tambah_laporan.php)
  // ==================================================================
  const form = document.getElementById("formLaporan");
  if (!form) return;                                              // bukan halaman ini, berhenti

  const inputJudul     = document.getElementById("judul");
  const pilihKategori  = document.getElementById("kategori_id");
  const pilihLokasi    = document.getElementById("lokasi_id");
  const inputDeskripsi = document.getElementById("deskripsi");
  const inputFoto      = document.getElementById("foto");

  const errJudul     = document.getElementById("judulError");
  const errKategori  = document.getElementById("kategoriError");
  const errLokasi    = document.getElementById("lokasiError");
  const errDeskripsi = document.getElementById("deskripsiError");
  const errFoto      = document.getElementById("fotoError");

  const hitungKarakter = document.getElementById("hitungKarakter");

  const areaFoto      = document.getElementById("areaFoto");
  const previewFoto   = document.getElementById("previewFoto");
  const gambarPreview = document.getElementById("gambarPreview");
  const namaFoto      = document.getElementById("namaFoto");
  const tombolHapusFoto = document.getElementById("hapusFoto");

  const peringatan     = document.getElementById("peringatanSerupa");
  const daftarSerupaEl = document.getElementById("daftarLaporanSerupa");
  const tombolLihat    = document.getElementById("lihatLaporanSerupa");
  const tombolTetap    = document.getElementById("tetapBuatLaporan");
  const pilihanSerupa  = document.getElementById("pilihanSerupa");

  const btnKirim  = document.getElementById("btnKirim");
  const teksKirim = document.getElementById("teksKirim");
  const pesanForm = document.getElementById("pesanForm");


  // ==================================================================
  // 2) FUNGSI BANTU UNTUK PESAN ERROR
  // ==================================================================
  // Tampilkan pesan error di bawah input + beri garis merah pada input
  function tampilError(input, elemenError, pesan) {
    elemenError.textContent = pesan;
    elemenError.classList.remove("hidden");
    input.setAttribute("aria-invalid", "true");
    input.classList.remove("border-black/20");                    // lepas garis abu-abu
    input.classList.add("border-red-600");                        // pasang garis merah
  }

  // Kebalikannya: sembunyikan pesan error dan kembalikan garis abu-abu
  function hapusError(input, elemenError) {
    elemenError.textContent = "";
    elemenError.classList.add("hidden");
    input.removeAttribute("aria-invalid");
    input.classList.remove("border-red-600");
    input.classList.add("border-black/20");
  }

  // Tiap fungsi cek...() mengembalikan true (benar) atau false (salah)
  function cekJudul() {
    const isi = inputJudul.value.trim();                          // trim = buang spasi di awal/akhir
    if (isi === "") {
      tampilError(inputJudul, errJudul, "Judul laporan wajib diisi.");
      return false;
    }
    if (isi.length < MIN_JUDUL) {
      tampilError(inputJudul, errJudul, "Judul terlalu pendek, minimal " + MIN_JUDUL + " karakter.");
      return false;
    }
    hapusError(inputJudul, errJudul);
    return true;
  }

  function cekKategori() {
    if (pilihKategori.value === "") {
      tampilError(pilihKategori, errKategori, "Pilih kategori terlebih dahulu.");
      return false;
    }
    hapusError(pilihKategori, errKategori);
    return true;
  }

  function cekLokasi() {
    if (pilihLokasi.value === "") {
      tampilError(pilihLokasi, errLokasi, "Pilih lokasi terlebih dahulu.");
      return false;
    }
    hapusError(pilihLokasi, errLokasi);
    return true;
  }

  function cekDeskripsi() {
    const panjang = inputDeskripsi.value.trim().length;
    if (panjang < MIN_DESKRIPSI) {
      tampilError(inputDeskripsi, errDeskripsi, "Deskripsi minimal " + MIN_DESKRIPSI + " karakter (sekarang " + panjang + ").");
      return false;
    }
    hapusError(inputDeskripsi, errDeskripsi);
    return true;
  }

  // Cek saat pengguna selesai mengisi (blur / change)
  inputJudul.addEventListener("blur", cekJudul);
  pilihKategori.addEventListener("change", cekKategori);
  pilihLokasi.addEventListener("change", cekLokasi);
  inputDeskripsi.addEventListener("blur", cekDeskripsi);

  // Kalau error sedang tampil, hilangkan begitu pengguna mulai memperbaiki
  inputJudul.addEventListener("input", () => {
    if (!errJudul.classList.contains("hidden")) cekJudul();
  });
  inputDeskripsi.addEventListener("input", () => {
    if (!errDeskripsi.classList.contains("hidden")) cekDeskripsi();
  });


  // ==================================================================
  // 3) PENGHITUNG KARAKTER DESKRIPSI
  // ==================================================================
  function updateHitung() {
    const jumlah = inputDeskripsi.value.trim().length;
    if (jumlah < MIN_DESKRIPSI) {
      hitungKarakter.textContent = jumlah + " / min. " + MIN_DESKRIPSI + " karakter";
    } else {
      hitungKarakter.textContent = jumlah + " karakter";
    }
  }
  inputDeskripsi.addEventListener("input", updateHitung);
  updateHitung();                                                 // jalankan sekali saat halaman dibuka


  // ==================================================================
  // 4) FOTO: PREVIEW + CEK UKURAN
  // ==================================================================
  let alamatPreview = null;                                       // alamat sementara gambar untuk preview

  function ukuranMB(byte) {
    return (byte / 1024 / 1024).toFixed(1) + " MB";
  }

  function hapusPreview() {
    if (alamatPreview) {
      URL.revokeObjectURL(alamatPreview);                         // bebaskan memori
      alamatPreview = null;
    }
    gambarPreview.src = "";
    namaFoto.textContent = "";
    previewFoto.classList.add("hidden");
  }

  function sembunyikanErrorFoto() {
    errFoto.textContent = "";
    errFoto.classList.add("hidden");
  }

  // Foto ditolak: kosongkan pilihan, hapus preview, tampilkan alasannya
  function tolakFoto(pesan) {
    inputFoto.value = "";
    hapusPreview();
    errFoto.textContent = pesan;
    errFoto.classList.remove("hidden");
  }

  // Dipanggil setiap ada file baru (dipilih lewat klik atau di-drop)
  function prosesFoto(file) {
    sembunyikanErrorFoto();

    if (!file) {                                                  // pengguna membatalkan pilihan file
      hapusPreview();
      return;
    }
    if (!JENIS_BOLEH.includes(file.type)) {
      tolakFoto("Format foto harus JPG, PNG, atau WebP.");
      return;
    }
    if (file.size > MAKS_UKURAN) {
      tolakFoto("Ukuran foto " + ukuranMB(file.size) + " melebihi batas 2 MB.");
      return;
    }

    // Lolos semua pengecekan -> tampilkan preview
    if (alamatPreview) URL.revokeObjectURL(alamatPreview);
    alamatPreview = URL.createObjectURL(file);
    gambarPreview.src = alamatPreview;
    namaFoto.textContent = file.name + " (" + ukuranMB(file.size) + ")";
    previewFoto.classList.remove("hidden");
  }

  inputFoto.addEventListener("change", () => prosesFoto(inputFoto.files[0]));

  tombolHapusFoto.addEventListener("click", () => {
    inputFoto.value = "";
    hapusPreview();
    sembunyikanErrorFoto();
  });

  // Seret & lepas (drag and drop) ke area foto
  function sorotArea(nyala) {
    if (nyala) {
      areaFoto.classList.remove("border-black/20");
      areaFoto.classList.add("border-red-600", "bg-red-50");
    } else {
      areaFoto.classList.remove("border-red-600", "bg-red-50");
      areaFoto.classList.add("border-black/20");
    }
  }

  ["dragenter", "dragover"].forEach((nama) => {
    areaFoto.addEventListener(nama, (e) => {
      e.preventDefault();                                         // wajib, kalau tidak browser membuka filenya
      sorotArea(true);
    });
  });

  areaFoto.addEventListener("dragleave", () => sorotArea(false));

  areaFoto.addEventListener("drop", (e) => {
    e.preventDefault();
    sorotArea(false);
    const file = e.dataTransfer.files[0];
    if (!file) return;

    // Masukkan file hasil drop ke <input type="file"> agar ikut terkirim bersama form
    const wadah = new DataTransfer();
    wadah.items.add(file);
    inputFoto.files = wadah.files;
    prosesFoto(file);
  });


  // ==================================================================
  // 5) PERINGATAN LAPORAN SERUPA
  // ==================================================================
  let daftarSerupa = [];                                          // laporan serupa yang ditemukan
  let sudahPilihTetapBuat = false;                                // sudah klik "Tetap buat laporan baru"?

  const NAMA_BULAN = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

  // "2026-10-09" -> "9 Okt 2026"
  function formatTanggal(teks) {
    const bagian = teks.slice(0, 10).split("-");                  // [tahun, bulan, hari]
    return Number(bagian[2]) + " " + NAMA_BULAN[Number(bagian[1]) - 1] + " " + bagian[0];
  }

  // Mengamankan teks sebelum disisipkan ke HTML (mencegah XSS)
  function amankan(teks) {
    const div = document.createElement("div");
    div.textContent = teks;
    return div.innerHTML;
  }

  function resetTombolSerupa() {
    pilihanSerupa.classList.add("hidden");
    [tombolLihat, tombolTetap].forEach((tombol) => {
      tombol.disabled = false;
      tombol.classList.remove("opacity-60", "cursor-not-allowed");
    });
  }

  function sembunyikanPeringatan() {
    peringatan.classList.add("hidden");
    daftarSerupa = [];
    sudahPilihTetapBuat = false;
    resetTombolSerupa();
  }

  // Tulis daftar laporan serupa (maksimal 3) ke dalam kotak peringatan
  function isiDaftarSerupa(daftar) {
    let html = "";
    daftar.slice(0, 3).forEach((laporan) => {
      const status = laporan.status.charAt(0).toUpperCase() + laporan.status.slice(1);
      html += `
        <div class="flex items-center justify-between gap-3 rounded-md border border-amber-300 bg-white px-3 py-2">
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-800">${amankan(laporan.judul)}</p>
            <p class="text-xs text-slate-500">${amankan(status)} &middot; ${formatTanggal(laporan.tanggal)}</p>
          </div>
          <a href="${URL_DETAIL}?id=${Number(laporan.id)}" target="_blank" rel="noopener"
             class="shrink-0 text-xs font-semibold text-amber-900 underline">Lihat</a>
        </div>`;
    });
    daftarSerupaEl.innerHTML = html;
  }

  // Ambil laporan serupa: dari data contoh, atau dari server
  async function ambilLaporanSerupa(kategoriId, lokasiId) {
    if (PAKAI_DUMMY) {
      return DATA_DUMMY.filter((l) => String(l.kategori_id) === kategoriId && String(l.lokasi_id) === lokasiId);
    }
    const alamat = URL_CEK_SERUPA + "?kategori_id=" + encodeURIComponent(kategoriId) +
                   "&lokasi_id=" + encodeURIComponent(lokasiId);
    const respons = await fetch(alamat);                          // await = tunggu jawaban server
    if (!respons.ok) throw new Error("Server menjawab " + respons.status);
    return await respons.json();
  }

  async function periksaLaporanSerupa() {
    const kategoriId = pilihKategori.value;
    const lokasiId   = pilihLokasi.value;

    if (kategoriId === "" || lokasiId === "") {                   // belum lengkap, tidak perlu cek
      sembunyikanPeringatan();
      return;
    }

    try {
      const hasil = await ambilLaporanSerupa(kategoriId, lokasiId);

      // Kalau selama menunggu pengguna sudah mengganti pilihan, abaikan hasil lama ini
      if (pilihKategori.value !== kategoriId || pilihLokasi.value !== lokasiId) return;

      if (hasil.length === 0) {
        sembunyikanPeringatan();
        return;
      }

      daftarSerupa = hasil;
      sudahPilihTetapBuat = false;
      resetTombolSerupa();
      isiDaftarSerupa(hasil);
      peringatan.classList.remove("hidden");
    } catch (error) {
      // Ini fitur tambahan. Kalau gagal, form tetap bisa dipakai seperti biasa.
      console.error("Gagal memeriksa laporan serupa:", error);
      sembunyikanPeringatan();
    }
  }

  pilihKategori.addEventListener("change", periksaLaporanSerupa);
  pilihLokasi.addEventListener("change", periksaLaporanSerupa);

  // Tombol "Lihat laporan itu": buka di tab baru supaya isian form tidak hilang
  tombolLihat.addEventListener("click", () => {
    if (daftarSerupa.length > 0) {
      window.open(URL_DETAIL + "?id=" + Number(daftarSerupa[0].id), "_blank", "noopener");
    }
  });

  // Tombol "Tetap buat laporan baru"
  tombolTetap.addEventListener("click", () => {
    sudahPilihTetapBuat = true;
    pilihanSerupa.textContent = "Baik, laporanmu akan dikirim sebagai laporan baru.";
    pilihanSerupa.classList.remove("hidden");
    [tombolLihat, tombolTetap].forEach((tombol) => {
      tombol.disabled = true;
      tombol.classList.add("opacity-60", "cursor-not-allowed");
    });
  });


  // ==================================================================
  // 6) KIRIM FORM
  // ==================================================================
  function tampilPesanForm(pesan) {
    pesanForm.textContent = pesan;
    pesanForm.className = "mt-3 rounded-md px-3 py-2 text-sm bg-red-600/10 text-red-700";   // class "hidden" ikut hilang
  }

  form.addEventListener("submit", (e) => {
    // a) Cek semua isian (semua dijalankan agar semua error langsung tampil)
    let valid = true;
    if (!cekJudul())     valid = false;
    if (!cekKategori())  valid = false;
    if (!cekLokasi())    valid = false;
    if (!cekDeskripsi()) valid = false;

    if (!valid) {
      e.preventDefault();                                         // batalkan pengiriman
      tampilPesanForm("Periksa kembali isian yang ditandai merah.");
      const pertama = form.querySelector('[aria-invalid="true"]');
      if (pertama) pertama.focus();                               // arahkan ke isian pertama yang salah
      return;
    }

    // b) Ada laporan serupa tapi pengguna belum memilih -> minta memilih dulu
    const adaPeringatan = !peringatan.classList.contains("hidden");
    if (adaPeringatan && !sudahPilihTetapBuat) {
      e.preventDefault();
      pilihanSerupa.textContent = "Pilih dulu: lihat laporan yang sudah ada, atau tetap buat laporan baru.";
      pilihanSerupa.classList.remove("hidden");
      peringatan.scrollIntoView({ behavior: "smooth", block: "center" });
      return;
    }

    // c) Semua aman -> kunci tombol supaya tidak terkirim dua kali
    pesanForm.classList.add("hidden");
    btnKirim.disabled = true;
    teksKirim.textContent = "Mengirim...";
  });

  // Kalau pengguna menekan tombol "Kembali" di browser, tombol kirim dipulihkan
  window.addEventListener("pageshow", () => {
    btnKirim.disabled = false;
    teksKirim.textContent = "Kirim Laporan";
  });

  // Kalau halaman dibuka dengan kategori & lokasi sudah terpilih, langsung periksa
  periksaLaporanSerupa();

})();