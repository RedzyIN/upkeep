document.addEventListener("DOMContentLoaded", function () {
    const inputFile = document.getElementById("fileExcel");
    const namaFile = document.getElementById("namaFile");
    const pesanUpload = document.getElementById("pesanUpload");
    const pesanPreview = document.getElementById("pesanPreview");
    const jumlahData = document.getElementById("jumlahData");
    const wadahTabel = document.getElementById("wadahTabel");
    const isiTabel = document.getElementById("isiTabel");
    const tombolTemplate = document.getElementById("unduhTemplate");
    const tombolKonfirmasi = document.getElementById("konfirmasiImport");
    const tombolReset = document.getElementById("resetImport");

    if (!inputFile || !tombolKonfirmasi) return;

    const kolomWajib = [ "Judul", "Lokasi", "Kategori", "Prioritas", "Deskripsi"];

    const kategoriValid = [ "Listrik", "Air & Sanitasi", "AC", "Furnitur", "Jaringan & WiFi", "Kebersihan"];

    const prioritasValid = ["rendah", "sedang", "tinggi"];

    let dataImport = [];
    let fileValid = false;

    // Memuat library SheetJS jika belum tersedia.
    function muatSheetJS() {
        if (window.XLSX) return Promise.resolve();

        if (window.sheetJsPromise) return window.sheetJsPromise;

        window.sheetJsPromise = new Promise((resolve, reject) => {
            const script = document.createElement("script");

            script.src =
                "https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js";

            script.onload = resolve;
            script.onerror = function () {
                reject(new Error("Library SheetJS gagal dimuat."));
            };

            document.head.appendChild(script);
        });

        return window.sheetJsPromise;
    }

    function tampilkanPesan(teks, jenis = "info") {
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
        fileValid = false;

        isiTabel.replaceChildren();
        wadahTabel.classList.add("hidden");
        pesanPreview.classList.remove("hidden");
        pesanPreview.textContent = pesan;
        jumlahData.textContent = "0 data";

        tombolKonfirmasi.disabled = true;
    }

    function normalisasiHeader(header) {
        return String(header ?? "").trim().toLowerCase();
    }

    function escapeHTML(nilai) {
        return String(nilai ?? "").replace(/[&<>"']/g, function (karakter) {
            const pengganti = {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            };
            return pengganti[karakter];
        });
    }

    function validasiBaris(baris) {
        const masalah = [];

        if (!baris.Judul) masalah.push("Judul kosong");
        if (!baris.Lokasi) masalah.push("Lokasi kosong");

        if (!kategoriValid.some(kategori => kategori.toLowerCase() === baris.Kategori.toLowerCase())) {
            masalah.push("Kategori tidak valid");
        }

        if (!prioritasValid.includes(baris.Prioritas.toLowerCase())) {
            masalah.push("Prioritas harus rendah, sedang, atau tinggi");
        }
        return masalah;
    }

    function tampilkanTabel(data) {
        isiTabel.replaceChildren();

        data.forEach(function (baris, indeks) {
            const masalah = validasiBaris(baris);
            baris._masalah = masalah;

            const statusValid = masalah.length === 0;

            const tr = document.createElement("tr");

            tr.innerHTML = `
                <td class="whitespace-nowrap px-4 py-3">${indeks + 1}</td>
                <td class="min-w-40 px-4 py-3">${escapeHTML(baris.Judul)}</td>
                <td class="min-w-36 px-4 py-3">${escapeHTML(baris.Lokasi)}</td>
                <td class="whitespace-nowrap px-4 py-3">${escapeHTML(baris.Kategori)}</td>
                <td class="whitespace-nowrap px-4 py-3">${escapeHTML(baris.Prioritas)}</td>
                <td class="min-w-48 px-4 py-3">${escapeHTML(baris.Deskripsi)}</td>
                <td class="min-w-40 px-4 py-3">
                    <span class="${
                        statusValid
                            ? "text-green-700"
                            : "text-red-600"
                    } font-medium">
                        ${statusValid ? "Valid" : escapeHTML(masalah.join(", "))}
                    </span>
                </td>
            `;

            isiTabel.appendChild(tr);
        });

        pesanPreview.classList.add("hidden");
        wadahTabel.classList.remove("hidden");

        const jumlahValid = data.filter(
            baris => baris._masalah.length === 0
        ).length;

        jumlahData.textContent =
            `${data.length} data · ${jumlahValid} valid`;

        fileValid =
            data.length > 0 &&
            data.every(baris => baris._masalah.length === 0);

        tombolKonfirmasi.disabled = !fileValid;
    }

    tombolTemplate.addEventListener("click", async function () {
        try {
            await muatSheetJS();
            const contohData = [kolomWajib, ["AC tidak dingin","Ruang 101","AC","tinggi","AC tidak menghasilkan udara dingin"]];

            const lembar = XLSX.utils.aoa_to_sheet(contohData);
            lembar["!cols"] = [{ wch: 25 },{ wch: 22 },{ wch: 22 },{ wch: 15 },{ wch: 45 }];

            const buku = XLSX.utils.book_new();

            XLSX.utils.book_append_sheet(buku, lembar, "Data Laporan");

            XLSX.writeFile(buku, "template_import_upkeep.xlsx");
        } catch (error) {
            tampilkanPesan(error.message, "error");
        }
    });

    // Baca file Excel.
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

        // Batas sederhana untuk menghindari file yang terlalu besar.
        if (file.size > 5 * 1024 * 1024) {
            tampilkanPesan("Ukuran file maksimal 5 MB.", "error");
            resetPreview("File terlalu besar.");
            inputFile.value = "";
            return;
        }

        try {
            await muatSheetJS();
            const buffer = await file.arrayBuffer();
            const buku = XLSX.read(buffer, {type: "array"});
            const namaLembar = buku.SheetNames[0];

            if (!namaLembar) {
                throw new Error("File Excel tidak memiliki lembar kerja.");
            }

            const lembar = buku.Sheets[namaLembar];
            const matriks = XLSX.utils.sheet_to_json(lembar, {header: 1, defval: "", raw: false});

            if (matriks.length < 2) {
                throw new Error("File tidak memiliki baris data.");
            }

            const headerAsli = matriks[0].map(normalisasiHeader);
            const headerWajib = kolomWajib.map(normalisasiHeader);

            const headerSesuai =
                headerWajib.length === headerAsli.length &&
                headerWajib.every((nama, indeks) => nama === headerAsli[indeks]);

            if (!headerSesuai) {
                throw new Error("Nama dan urutan kolom harus sama persis dengan template.");
            }

            const barisData = matriks.slice(1)
                .filter(baris => baris.some(nilai => String(nilai).trim() !== ""));

            dataImport = barisData.map(function (baris) {
                return {Judul: String(baris[0] ?? "").trim(), Lokasi: String(baris[1] ?? "").trim(), Kategori: String(baris[2] ?? "").trim(), Prioritas: String(baris[3] ?? "").trim(), Deskripsi: String(baris[4] ?? "").trim()};
            });

            if (dataImport.length === 0) {
                throw new Error("Tidak ditemukan baris data untuk diimpor.");
            }

            tampilkanTabel(dataImport);

            if (fileValid) {
                tampilkanPesan(
                    "File berhasil dibaca. Semua baris lolos validasi awal.",
                    "success"
                );
            } else {
                tampilkanPesan(
                    "Preview berhasil dibuat, tetapi masih ada data yang perlu diperbaiki.",
                    "error"
                );
            }
        } catch (error) {
            resetPreview("Preview tidak dapat ditampilkan.");
            tampilkanPesan(error.message || "Gagal membaca file Excel.", "error");
        }
    });

    // Konfirmasi demo: belum menyimpan ke database.
    tombolKonfirmasi.addEventListener("click", function () {
        if (!fileValid || dataImport.length === 0) {
            tampilkanPesan("Periksa kembali data sebelum konfirmasi.", "error");
            return;
        }

        alert(
            "Data lolos validasi awal sebanyak " +
            dataImport.length +
            " baris.\n\n" +
            "Preview berhasil diuji, tetapi data belum disimpan ke database. " +
            "Endpoint PHP untuk proses import perlu dibuat terlebih dahulu."
        );
    });

    // Kosongkan file dan preview.
    tombolReset.addEventListener("click", function () {
        inputFile.value = "";
        namaFile.textContent = "Belum ada file dipilih";
        tampilkanPesan("");
        resetPreview();
    });
});