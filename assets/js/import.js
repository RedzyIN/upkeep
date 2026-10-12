
document.addEventListener("DOMContentLoaded", function () {
    const inputFile = document.getElementById("fileExcel");
    const namaFile = document.getElementById("namaFile");
    const jenisImport = document.getElementById("jenisImport");
    const pesanUpload = document.getElementById("pesanUpload");
    const pesanPreview = document.getElementById("pesanPreview");
    const jumlahData = document.getElementById("jumlahData");
    const wadahTabel = document.getElementById("wadahTabel");
    const isiTabel = document.getElementById("isiTabel");
    const tombolTemplate = document.getElementById("unduhTemplate");
    const tombolKonfirmasi = document.getElementById("konfirmasiImport");
    const tombolReset = document.getElementById("resetImport");

    if (
        !inputFile ||
        !jenisImport ||
        !tombolKonfirmasi ||
        !tombolReset ||
        !isiTabel
    ) {
        console.error("Elemen halaman import belum lengkap.");
        return;
    }

    const tokenCSRF = document.querySelector('input[name="csrf"]');

    let dataImport = [];
    let sheetJsPromise = null;

    function muatSheetJS() {
        if (window.XLSX) return Promise.resolve();

        if (sheetJsPromise) return sheetJsPromise;

        sheetJsPromise = new Promise((resolve, reject) => {
            const script = document.createElement("script");

            script.src =
                "https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js";

            script.onload = resolve;
            script.onerror = function () {
                sheetJsPromise = null;
                reject(new Error("Library SheetJS gagal dimuat."));
            };

            document.head.appendChild(script);
        });

        return sheetJsPromise;
    }

    function tampilkanPesan(teks, jenis = "info") {
        if (!pesanUpload) return;

        pesanUpload.textContent = teks;
        pesanUpload.className = "mt-3 text-sm";

        if (jenis === "error") {
            pesanUpload.classList.add("text-red-600");
        } else if (jenis === "success") {
            pesanUpload.classList.add("text-green-700");
        } else {
            pesanUpload.classList.add("text-gray-600");
        }
    }

    function resetPreview(pesan = "Belum ada data.") {
        dataImport = [];

        isiTabel.replaceChildren();
        wadahTabel?.classList.add("hidden");
        pesanPreview?.classList.remove("hidden");

        if (pesanPreview) {
            pesanPreview.textContent = pesan;
        }

        if (jumlahData) {
            jumlahData.textContent = "0 data";
        }

        tombolKonfirmasi.disabled = true;
    }

    function validasiNama(nama) {
        const maks = jenisImport.value === "kategori" ? 50 : 100;

        if (!nama) return "Nama wajib diisi.";

        // Array.from menghitung code point Unicode dengan lebih baik
        // daripada menghitung jumlah byte.
        if (Array.from(nama).length > maks) {
            return `Nama maksimal ${maks} karakter.`;
        }

        return "";
    }

    function tampilkanTabel() {
        isiTabel.replaceChildren();

        let semuaValid = dataImport.length > 0;

        dataImport.forEach(function (nama, indeks) {
            const masalah = validasiNama(nama);
            const tr = document.createElement("tr");
            const tdNo = document.createElement("td");
            const tdNama = document.createElement("td");
            const tdValidasi = document.createElement("td");
            const status = document.createElement("span");

            tdNo.className = "px-4 py-3";
            tdNama.className = "px-4 py-3";
            tdValidasi.className = "px-4 py-3";

            tdNo.textContent = indeks + 1;
            tdNama.textContent = nama || "-";

            status.className = masalah
                ? "font-medium text-red-600"
                : "font-medium text-green-700";

            status.textContent = masalah || "Valid";
            tdValidasi.appendChild(status);

            tr.append(tdNo, tdNama, tdValidasi);
            isiTabel.appendChild(tr);

            if (masalah) semuaValid = false;
        });

        pesanPreview?.classList.add("hidden");
        wadahTabel?.classList.remove("hidden");

        if (jumlahData) {
            jumlahData.textContent = `${dataImport.length} data`;
        }

        tombolKonfirmasi.disabled = !semuaValid;
    }

    function resetSemua() {
        inputFile.value = "";
        namaFile.textContent = "Belum ada file dipilih";

        tampilkanPesan("");
        resetPreview();
    }

    // Unduh template Excel dengan satu kolom Nama.
    tombolTemplate?.addEventListener("click", async function () {
        try {
            await muatSheetJS();

            const worksheet = XLSX.utils.aoa_to_sheet([
                ["Nama"],
                [""]
            ]);

            worksheet["!cols"] = [{ wch: 35 }];

            const workbook = XLSX.utils.book_new();

            XLSX.utils.book_append_sheet(
                workbook,
                worksheet,
                jenisImport.value === "kategori" ? "Kategori" : "Lokasi"
            );

            XLSX.writeFile(
                workbook,
                `template-${jenisImport.value}-upkeep.xlsx`
            );

        } catch (error) {
            tampilkanPesan(error.message, "error");
        }
    });

    // Baca file Excel dan tampilkan preview.
    inputFile.addEventListener("change", async function () {
        resetPreview("Sedang membaca file Excel...");
        tampilkanPesan("");

        const file = inputFile.files[0];

        if (!file) {
            namaFile.textContent = "Belum ada file dipilih";
            resetPreview();
            return;
        }

        namaFile.textContent = file.name;

        if (!file.name.toLowerCase().endsWith(".xlsx")) {
            tampilkanPesan("Pilih file dengan ekstensi .xlsx.", "error");
            resetPreview("Format file tidak didukung.");
            inputFile.value = "";
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            tampilkanPesan("Ukuran file maksimal 5 MB.", "error");
            resetPreview("File terlalu besar.");
            inputFile.value = "";
            return;
        }

        try {
            await muatSheetJS();

            const buffer = await file.arrayBuffer();
            const workbook = XLSX.read(buffer, { type: "array" });
            const sheetName = workbook.SheetNames[0];

            if (!sheetName) {
                throw new Error("File tidak memiliki lembar kerja.");
            }

            const worksheet = workbook.Sheets[sheetName];

            const matriks = XLSX.utils.sheet_to_json(worksheet, {
                header: 1,
                defval: "",
                raw: false
            });

            if (matriks.length < 2) {
                throw new Error("File belum memiliki baris data.");
            }

            const header = String(matriks[0][0] ?? "")
                .trim()
                .toLowerCase();

            if (header !== "nama" || matriks[0].length !== 1) {
                throw new Error(
                    "Template harus memiliki satu kolom bernama Nama."
                );
            }

            dataImport = matriks
                .slice(1)
                .filter(baris =>
                    baris.some(nilai => String(nilai).trim() !== "")
                )
                .map(baris => String(baris[0] ?? "").trim());

            if (dataImport.length === 0) {
                throw new Error("Tidak ditemukan data untuk diimpor.");
            }

            if (dataImport.length > 1000) {
                throw new Error("Maksimal 1000 baris per import.");
            }

            tampilkanTabel();

            const valid = dataImport.every(nama => !validasiNama(nama));

            tampilkanPesan(
                valid
                    ? "File berhasil dibaca. Periksa preview sebelum import."
                    : "Ada baris yang perlu diperbaiki sebelum import.",
                valid ? "success" : "error"
            );

        } catch (error) {
            resetPreview("Preview tidak dapat ditampilkan.");
            tampilkanPesan(error.message || "Gagal membaca file.", "error");
        }
    });

    // Jenis data berubah: validasi ulang batas karakter.
    jenisImport.addEventListener("change", function () {
        if (dataImport.length > 0) {
            tampilkanTabel();
            tampilkanPesan("Periksa kembali preview untuk jenis data ini.");
        }
    });

    // Kirim data ke endpoint PHP.
    tombolKonfirmasi.addEventListener("click", async function () {
        if (dataImport.length === 0) {
            tampilkanPesan("Belum ada data untuk diimpor.", "error");
            return;
        }

        if (!tokenCSRF?.value) {
            tampilkanPesan(
                "Token CSRF tidak ditemukan. Muat ulang halaman.",
                "error"
            );
            return;
        }

        const masalah = dataImport.some(nama => validasiNama(nama));

        if (masalah) {
            tampilkanPesan("Perbaiki data yang tidak valid terlebih dahulu.", "error");
            return;
        }

        tombolKonfirmasi.disabled = true;
        tombolKonfirmasi.textContent = "Mengimpor...";

        try {
            const response = await fetch("import.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": tokenCSRF.value
                },
                body: JSON.stringify({
                    jenis: jenisImport.value,
                    baris: dataImport
                })
            });

            const hasil = await response.json();

            if (!response.ok) {
                throw new Error(
                    hasil.error || "Import gagal diproses."
                );
            }

            tampilkanPesan(
                `Import selesai. Berhasil: ${hasil.berhasil}; dilewati: ${hasil.dilewati}.`,
                "success"
            );

            // Data berhasil diproses; kosongkan preview agar tidak terimpor
            // ulang secara tidak sengaja.
            resetPreview("Import selesai. Pilih file lain untuk melanjutkan.");

        } catch (error) {
            tampilkanPesan(
                error.message || "Terjadi kesalahan saat mengirim data.",
                "error"
            );
        } finally {
            tombolKonfirmasi.textContent = "Konfirmasi Import";
            tombolKonfirmasi.disabled = dataImport.length === 0;
        }
    });

    tombolReset.addEventListener("click", resetSemua);
});